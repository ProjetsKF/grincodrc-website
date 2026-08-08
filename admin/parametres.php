<?php
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/parameters.php';

grinco_admin_bootstrap();
grinco_admin_require_authentication();

function parametres_request_value($source, $key)
{
    return isset($source[$key]) && is_scalar($source[$key]) ? (string) $source[$key] : '';
}

function parametres_normalize_address($value)
{
    $value = grinco_normalize_text(strip_tags((string) $value), true);
    $lines = explode("\n", $value);
    foreach ($lines as &$line) { $line = trim($line); }
    unset($line);
    return trim(implode("\n", $lines));
}

function parametres_contains_runtime_metadata($value)
{
    return preg_match('/System\.Object.*mscorlib.*Address\s*\(\s*int\s*\)/i', (string) $value) === 1;
}

function parametres_validate_input($source)
{
    $errors = array();
    $companyNameRaw = parametres_request_value($source, 'nom_entreprise');
    $emailRaw = trim(parametres_request_value($source, 'email'));
    $phoneRaw = parametres_request_value($source, 'telephone');
    $addressRaw = parametres_request_value($source, 'adresse');
    $companyName = grinco_normalize_text(strip_tags($companyNameRaw), false);
    $email = grinco_normalize_text(strip_tags($emailRaw), false);
    $phoneValidation = grinco_validate_phone($phoneRaw, false);
    $phone = $phoneValidation['value'];
    $address = parametres_normalize_address($addressRaw);

    if ($companyName === '') {
        $errors['nom_entreprise'] = 'Le nom de l’entreprise est obligatoire.';
    } elseif (grinco_utf8_length($companyName) > 150) {
        $errors['nom_entreprise'] = 'Le nom de l’entreprise ne peut pas dépasser 150 caractères.';
    } elseif (grinco_has_forbidden_control_characters($companyNameRaw, false)) {
        $errors['nom_entreprise'] = 'Le nom de l’entreprise contient des caractères non autorisés.';
    }

    if ($email !== '' && (grinco_utf8_length($email) > 150 || grinco_detect_header_injection($emailRaw) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errors['email'] = 'L’adresse e-mail n’est pas valide.';
    }
    if (!empty($phoneValidation['errors'])) {
        $errors['telephone'] = $phoneValidation['errors'][0];
    } elseif (grinco_has_forbidden_control_characters($phoneRaw, false)) {
        $errors['telephone'] = 'Le téléphone contient des caractères non autorisés.';
    }
    if (parametres_contains_runtime_metadata($address)) {
        $errors['adresse'] = 'L’adresse reçue est invalide. Veuillez saisir de nouveau l’adresse complète.';
    } elseif (strlen($address) > 65535) {
        $errors['adresse'] = 'L’adresse dépasse la longueur autorisée.';
    } elseif (grinco_has_forbidden_control_characters($addressRaw, true)) {
        $errors['adresse'] = 'L’adresse contient des caractères non autorisés.';
    }

    return array(
        'valid' => empty($errors),
        'errors' => $errors,
        'values' => array(
            'nom_entreprise' => $companyName,
            'email' => $email,
            'telephone' => $phone,
            'adresse' => $address
        )
    );
}

function parametres_save_singleton($values)
{
    $connection = grinco_database();
    $lockStatement = $connection->prepare("SELECT GET_LOCK('grinco_parametres_singleton', 5)");
    $lockStatement->execute();
    if ((int) $lockStatement->fetchColumn() !== 1) {
        throw new RuntimeException('Le verrou des paramètres est indisponible.');
    }

    try {
        $connection->beginTransaction();
        $existing = $connection->prepare('SELECT id FROM parametres ORDER BY id ASC LIMIT 1 FOR UPDATE');
        $existing->execute();
        $parameterId = (int) $existing->fetchColumn();
        if ($parameterId > 0) {
            $statement = $connection->prepare(
                'UPDATE parametres SET nom_entreprise = :company, email = :email, '
                . 'telephone = :phone, adresse = :address WHERE id = :id'
            );
            $statement->execute(array(
                ':company' => $values['nom_entreprise'],
                ':email' => $values['email'] === '' ? null : $values['email'],
                ':phone' => $values['telephone'] === '' ? null : $values['telephone'],
                ':address' => $values['adresse'] === '' ? null : $values['adresse'],
                ':id' => $parameterId
            ));
        } else {
            $statement = $connection->prepare(
                'INSERT INTO parametres (nom_entreprise, email, telephone, adresse) '
                . 'VALUES (:company, :email, :phone, :address)'
            );
            $statement->execute(array(
                ':company' => $values['nom_entreprise'],
                ':email' => $values['email'] === '' ? null : $values['email'],
                ':phone' => $values['telephone'] === '' ? null : $values['telephone'],
                ':address' => $values['adresse'] === '' ? null : $values['adresse']
            ));
            $parameterId = (int) $connection->lastInsertId();
        }
        $connection->commit();
    } catch (Exception $exception) {
        if ($connection->inTransaction()) { $connection->rollBack(); }
        throw $exception;
    } finally {
        try {
            $release = $connection->prepare("SELECT RELEASE_LOCK('grinco_parametres_singleton')");
            $release->execute();
        } catch (Exception $ignored) {
        }
    }
    return $parameterId;
}

$settingsMessage = '';
$settingsMessageType = '';
$settingsFieldErrors = array();
$settings = null;
try {
    $settings = grinco_parameters_get();
} catch (PDOException $exception) {
    error_log('[GRINCO admin parameters] Unable to load parameters.');
    $settingsMessage = 'Les paramètres ne peuvent pas être chargés pour le moment.';
    $settingsMessageType = 'error';
}
$formValues = $settings === null
    ? array('nom_entreprise' => '', 'email' => '', 'telephone' => '', 'adresse' => '')
    : $settings;

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $receivedCsrfToken = parametres_request_value($_POST, 'csrf_token');
    if (!grinco_validate_csrf_token('admin_parameters', $receivedCsrfToken)) {
        $settingsMessage = 'Votre session a expiré. Veuillez réessayer.';
        $settingsMessageType = 'error';
    } elseif (!grinco_validate_request_origin()) {
        $settingsMessage = 'La demande n’a pas pu être vérifiée.';
        $settingsMessageType = 'error';
    } else {
        $validation = parametres_validate_input($_POST);
        $formValues = $validation['values'];
        $settingsFieldErrors = $validation['errors'];
        if (!$validation['valid']) {
            $settingsMessage = 'Certaines informations sont invalides.';
            $settingsMessageType = 'error';
        } else {
            try {
                parametres_save_singleton($validation['values']);
                $_SESSION['admin_parameters_flash'] = 'Les paramètres ont été enregistrés avec succès.';
                grinco_regenerate_csrf_token('admin_parameters');
                header('Location: ' . grinco_url('/admin/parametres.php'));
                exit;
            } catch (Exception $exception) {
                error_log('[GRINCO admin parameters] Save failed: ' . get_class($exception));
                $settingsMessage = 'Les paramètres ne peuvent pas être enregistrés pour le moment.';
                $settingsMessageType = 'error';
            }
        }
    }
    grinco_regenerate_csrf_token('admin_parameters');
}

if (!empty($_SESSION['admin_parameters_flash'])) {
    $settingsMessage = (string) $_SESSION['admin_parameters_flash'];
    $settingsMessageType = 'success';
    unset($_SESSION['admin_parameters_flash']);
}

$settingsExists = $settings !== null;
$settingsCsrfToken = grinco_csrf_token('admin_parameters');
$adminPageTitle = 'Paramètres généraux';
$adminPageDescription = 'Gestion des coordonnées générales de GRINCO RDC.';
$adminCurrentPage = 'parametres';
$logoutCsrfToken = grinco_csrf_token('admin_logout');
include dirname(__DIR__) . '/includes/admin/head.php';

function parametres_escape_value($values, $field)
{
    return grinco_admin_escape(isset($values[$field]) ? $values[$field] : '');
}
function parametres_invalid_attributes($errors, $field)
{
    return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . $field . '-error"' : '';
}
?>

<div class="admin-layout">
  <?php include dirname(__DIR__) . '/includes/admin/sidebar.php'; ?>
  <div class="admin-main-shell">
    <?php include dirname(__DIR__) . '/includes/admin/header.php'; ?>
    <main class="admin-content" id="admin-main-content">
      <div class="admin-page-heading"><span class="admin-eyebrow">Configuration</span><h1>Paramètres généraux</h1><p>Ces informations correspondent aux coordonnées générales de GRINCO utilisées dans l’application.</p></div>
      <?php if ($settingsMessage !== ''): ?><div class="admin-module-alert is-<?php echo grinco_admin_escape($settingsMessageType); ?>" role="alert" aria-live="polite"><i class="bi <?php echo $settingsMessageType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>" aria-hidden="true"></i><span><?php echo grinco_admin_escape($settingsMessage); ?></span></div><?php endif; ?>
      <section class="admin-panel admin-settings-card" aria-labelledby="settings-form-title">
        <div class="admin-panel-header"><div><h2 id="settings-form-title">Coordonnées de GRINCO</h2><p>Les champs facultatifs peuvent être complétés ou modifiés ultérieurement.</p></div><div class="admin-settings-header-actions"><span class="admin-panel-badge"><i class="bi bi-building" aria-hidden="true"></i> Fiche unique</span><?php if ($settingsExists): ?><button type="button" class="admin-secondary-button" data-bs-toggle="modal" data-bs-target="#view-settings-modal"><i class="bi bi-eye" aria-hidden="true"></i><span>Voir les informations</span></button><?php endif; ?></div></div>
        <form action="<?php echo grinco_url_html('/admin/parametres.php'); ?>" method="POST" class="admin-settings-form">
          <input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($settingsCsrfToken); ?>">
          <div class="admin-form-field"><label for="nom_entreprise">Nom de l’entreprise <span aria-hidden="true">*</span></label><input type="text" id="nom_entreprise" name="nom_entreprise" value="<?php echo parametres_escape_value($formValues, 'nom_entreprise'); ?>" maxlength="150" autocomplete="organization" required<?php echo parametres_invalid_attributes($settingsFieldErrors, 'nom_entreprise'); ?>><?php if (isset($settingsFieldErrors['nom_entreprise'])): ?><small class="admin-field-error" id="nom_entreprise-error"><?php echo grinco_admin_escape($settingsFieldErrors['nom_entreprise']); ?></small><?php endif; ?></div>
          <div class="admin-form-grid"><div class="admin-form-field"><label for="email">Adresse e-mail</label><input type="email" id="email" name="email" value="<?php echo parametres_escape_value($formValues, 'email'); ?>" maxlength="150" autocomplete="email"<?php echo parametres_invalid_attributes($settingsFieldErrors, 'email'); ?>><?php if (isset($settingsFieldErrors['email'])): ?><small class="admin-field-error" id="email-error"><?php echo grinco_admin_escape($settingsFieldErrors['email']); ?></small><?php endif; ?></div><div class="admin-form-field"><label for="telephone">Téléphone</label><input type="tel" id="telephone" name="telephone" value="<?php echo parametres_escape_value($formValues, 'telephone'); ?>" maxlength="50" autocomplete="tel"<?php echo parametres_invalid_attributes($settingsFieldErrors, 'telephone'); ?>><?php if (isset($settingsFieldErrors['telephone'])): ?><small class="admin-field-error" id="telephone-error"><?php echo grinco_admin_escape($settingsFieldErrors['telephone']); ?></small><?php endif; ?></div></div>
          <div class="admin-form-field"><label for="adresse">Adresse</label><textarea id="adresse" name="adresse" rows="6" maxlength="65535" autocomplete="street-address"<?php echo parametres_invalid_attributes($settingsFieldErrors, 'adresse'); ?>><?php echo parametres_escape_value($formValues, 'adresse'); ?></textarea><?php if (isset($settingsFieldErrors['adresse'])): ?><small class="admin-field-error" id="adresse-error"><?php echo grinco_admin_escape($settingsFieldErrors['adresse']); ?></small><?php endif; ?></div>
          <div class="admin-settings-actions"><p><span aria-hidden="true">*</span> Champ obligatoire</p><button type="submit" class="admin-primary-button"><i class="bi bi-check-lg" aria-hidden="true"></i><span><?php echo $settingsExists ? 'Enregistrer les modifications' : 'Enregistrer les paramètres'; ?></span></button></div>
        </form>
      </section>
    </main>
    <?php include dirname(__DIR__) . '/includes/admin/footer.php'; ?>
  </div>
</div>

<?php if ($settingsExists): ?>
<div class="modal fade admin-modal" id="view-settings-modal" tabindex="-1" aria-labelledby="view-settings-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header"><div><span class="admin-modal-eyebrow">Fiche de consultation</span><h2 class="modal-title" id="view-settings-title">Informations générales de GRINCO</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
      <div class="modal-body"><dl class="admin-settings-readonly">
        <div><dt>Nom de l’entreprise</dt><dd><?php echo grinco_admin_escape($settings['nom_entreprise']); ?></dd></div>
        <div><dt>Adresse e-mail</dt><dd><?php echo $settings['email'] === '' ? 'Non renseigné' : grinco_admin_escape($settings['email']); ?></dd></div>
        <div><dt>Téléphone</dt><dd><?php echo $settings['telephone'] === '' ? 'Non renseigné' : grinco_admin_escape($settings['telephone']); ?></dd></div>
        <div class="is-full"><dt>Adresse</dt><dd><?php echo $settings['adresse'] === '' ? 'Non renseigné' : nl2br(grinco_admin_escape($settings['adresse'])); ?></dd></div>
      </dl></div>
      <div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Fermer</button></div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php include dirname(__DIR__) . '/includes/admin/scripts.php'; ?>
