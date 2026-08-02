<?php
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin/exchange-rates.php';

grinco_admin_bootstrap();
grinco_admin_require_authentication();

function taux_change_request_value($source, $key, $default)
{
    return isset($source[$key]) && is_scalar($source[$key]) ? (string) $source[$key] : $default;
}

function taux_change_search_term($value)
{
    return grinco_utf8_substr(grinco_normalize_text(strip_tags((string) $value), false), 0, 50);
}

function taux_change_page_number($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 1;
}

function taux_change_record_id($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 0;
}

function taux_change_currency_code($value)
{
    return strtoupper(trim(grinco_normalize_text(strip_tags((string) $value), false)));
}

function taux_change_parse_rate($value)
{
    $value = str_replace(array(' ', "\xc2\xa0", ','), array('', '', '.'), trim((string) $value));
    if (!preg_match('/^\d{1,9}(?:\.\d{1,6})?$/', $value) || (float) $value <= 0) {
        return array('valid' => false, 'value' => '');
    }
    return array('valid' => true, 'value' => number_format((float) $value, 6, '.', ''));
}

function taux_change_validate_input($source)
{
    $errors = array();
    $sourceCurrency = taux_change_currency_code(taux_change_request_value($source, 'devise_source', ''));
    $destinationCurrency = taux_change_currency_code(taux_change_request_value($source, 'devise_destination', ''));
    $rate = taux_change_parse_rate(taux_change_request_value($source, 'taux', ''));

    if (!preg_match('/^[A-Z]{3}$/', $sourceCurrency)) {
        $errors[] = 'La devise source doit contenir exactement trois lettres, par exemple USD.';
    }
    if (!preg_match('/^[A-Z]{3}$/', $destinationCurrency)) {
        $errors[] = 'La devise de destination doit contenir exactement trois lettres, par exemple CNY.';
    }
    if ($sourceCurrency !== '' && $sourceCurrency === $destinationCurrency) {
        $errors[] = 'Les devises source et de destination doivent être différentes.';
    }
    if (!$rate['valid']) {
        $errors[] = 'Le taux doit être strictement supérieur à zéro et comporter au maximum six décimales.';
    }

    return array(
        'valid' => empty($errors),
        'errors' => $errors,
        'source' => $sourceCurrency,
        'destination' => $destinationCurrency,
        'rate' => $rate['value']
    );
}

function taux_change_pair_exists($sourceCurrency, $destinationCurrency, $excludedId)
{
    $sql = 'SELECT id FROM taux_change WHERE devise_source = :source AND devise_destination = :destination';
    $parameters = array(':source' => $sourceCurrency, ':destination' => $destinationCurrency);
    if ($excludedId > 0) {
        $sql .= ' AND id <> :id';
        $parameters[':id'] = (int) $excludedId;
    }
    $sql .= ' LIMIT 1';
    $statement = grinco_database()->prepare($sql);
    $statement->execute($parameters);
    return (bool) $statement->fetchColumn();
}

function taux_change_fetch_page($search, $requestedPage, $perPage)
{
    $connection = grinco_database();
    $where = '';
    $parameters = array();
    if ($search !== '') {
        $where = ' WHERE devise_source LIKE :source OR devise_destination LIKE :destination OR CAST(taux AS CHAR) LIKE :rate';
        $pattern = '%' . strtoupper($search) . '%';
        $parameters = array(':source' => $pattern, ':destination' => $pattern, ':rate' => '%' . $search . '%');
    }

    $count = $connection->prepare('SELECT COUNT(*) FROM taux_change' . $where);
    $count->execute($parameters);
    $total = (int) $count->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, (int) $requestedPage), $totalPages);
    $offset = ($page - 1) * $perPage;

    $statement = $connection->prepare(
        'SELECT id, devise_source, devise_destination, taux FROM taux_change'
        . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset'
    );
    foreach ($parameters as $key => $value) {
        $statement->bindValue($key, $value, PDO::PARAM_STR);
    }
    $statement->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $statement->execute();

    $rows = $statement->fetchAll();
    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['devise_source'] = strtoupper((string) $row['devise_source']);
        $row['devise_destination'] = strtoupper((string) $row['devise_destination']);
        $row['taux'] = number_format((float) $row['taux'], 6, '.', '');
        $row['taux_formatted'] = number_format((float) $row['taux'], 6, ',', ' ');
        $row['conversion_example'] = '1 ' . $row['devise_source'] . ' = ' . $row['taux_formatted'] . ' ' . $row['devise_destination'];
    }
    unset($row);

    return array(
        'rows' => $rows,
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => $offset
    );
}

function taux_change_set_flash($type, $message)
{
    $_SESSION['admin_exchange_rates_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message
    );
}

function taux_change_redirect($search, $page)
{
    $query = array();
    if ($search !== '') { $query['q'] = $search; }
    if ($page > 1) { $query['page'] = $page; }
    $location = grinco_url('/admin/taux-change.php');
    if ($query) { $location .= '?' . http_build_query($query, '', '&'); }
    header('Location: ' . $location);
    exit;
}

function taux_change_pagination_url($page, $search)
{
    $query = array('page' => max(1, (int) $page));
    if ($search !== '') { $query['q'] = $search; }
    return grinco_url_html('/admin/taux-change.php?' . http_build_query($query, '', '&'));
}

function taux_change_pagination_items($currentPage, $totalPages)
{
    if ($totalPages <= 7) { return range(1, max(1, $totalPages)); }
    $items = array(1);
    if ($currentPage > 4) { $items[] = 'ellipsis-start'; }
    for ($page = max(2, $currentPage - 1); $page <= min($totalPages - 1, $currentPage + 1); $page++) { $items[] = $page; }
    if ($currentPage < $totalPages - 3) { $items[] = 'ellipsis-end'; }
    $items[] = $totalPages;
    return $items;
}

$perPage = 10;
$search = taux_change_search_term(taux_change_request_value($_GET, 'q', ''));
$requestedPage = taux_change_page_number(taux_change_request_value($_GET, 'page', '1'));

if (taux_change_request_value($_GET, 'ajax', '') === '1') {
    try {
        header('Content-Type: application/json; charset=UTF-8');
        echo grinco_json_encode(array('success' => true, 'data' => taux_change_fetch_page($search, $requestedPage, $perPage)));
    } catch (PDOException $exception) {
        error_log('[GRINCO admin exchange rates] Unable to load AJAX list.');
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo grinco_json_encode(array('success' => false, 'message' => 'Les taux ne peuvent pas être chargés.'));
    }
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = taux_change_request_value($_POST, 'rate_action', '');
    $csrfToken = taux_change_request_value($_POST, 'csrf_token', '');
    $returnSearch = taux_change_search_term(taux_change_request_value($_POST, 'return_search', ''));
    $returnPage = taux_change_page_number(taux_change_request_value($_POST, 'return_page', '1'));

    if (!grinco_validate_csrf_token('admin_exchange_rates', $csrfToken)) {
        taux_change_set_flash('error', 'Votre session a expiré. Veuillez réessayer.');
        grinco_regenerate_csrf_token('admin_exchange_rates');
        taux_change_redirect($returnSearch, $returnPage);
    }
    if (!grinco_validate_request_origin()) {
        taux_change_set_flash('error', 'La demande n’a pas pu être vérifiée.');
        grinco_regenerate_csrf_token('admin_exchange_rates');
        taux_change_redirect($returnSearch, $returnPage);
    }

    try {
        if ($action === 'create' || $action === 'update') {
            $rateId = $action === 'update'
                ? taux_change_record_id(taux_change_request_value($_POST, 'rate_id', '0'))
                : 0;
            $validation = taux_change_validate_input($_POST);
            if (!$validation['valid']) {
                taux_change_set_flash('error', implode(' ', $validation['errors']));
            } elseif ($action === 'update' && $rateId <= 0) {
                taux_change_set_flash('error', 'Le taux sélectionné n’est pas valide.');
            } elseif (taux_change_pair_exists($validation['source'], $validation['destination'], $rateId)) {
                taux_change_set_flash('error', 'Un taux existe déjà pour ce couple de devises.');
            } elseif ($action === 'create') {
                $statement = grinco_database()->prepare(
                    'INSERT INTO taux_change (devise_source, devise_destination, taux) '
                    . 'VALUES (:source, :destination, :rate)'
                );
                $statement->execute(array(
                    ':source' => $validation['source'],
                    ':destination' => $validation['destination'],
                    ':rate' => $validation['rate']
                ));
                taux_change_set_flash('success', 'Le taux de change a été ajouté avec succès.');
            } else {
                $exists = grinco_database()->prepare('SELECT id FROM taux_change WHERE id = :id LIMIT 1');
                $exists->execute(array(':id' => $rateId));
                if (!$exists->fetchColumn()) {
                    taux_change_set_flash('error', 'Le taux demandé est introuvable.');
                } else {
                    $statement = grinco_database()->prepare(
                        'UPDATE taux_change SET devise_source = :source, devise_destination = :destination, taux = :rate '
                        . 'WHERE id = :id'
                    );
                    $statement->execute(array(
                        ':source' => $validation['source'],
                        ':destination' => $validation['destination'],
                        ':rate' => $validation['rate'],
                        ':id' => $rateId
                    ));
                    taux_change_set_flash('success', 'Le taux de change a été modifié avec succès.');
                }
            }
        } elseif ($action === 'delete') {
            $rateId = taux_change_record_id(taux_change_request_value($_POST, 'rate_id', '0'));
            if ($rateId <= 0) {
                taux_change_set_flash('error', 'Le taux sélectionné n’est pas valide.');
            } else {
                $statement = grinco_database()->prepare('DELETE FROM taux_change WHERE id = :id');
                $statement->execute(array(':id' => $rateId));
                taux_change_set_flash(
                    $statement->rowCount() > 0 ? 'success' : 'error',
                    $statement->rowCount() > 0 ? 'Le taux de change a été supprimé avec succès.' : 'Le taux demandé est introuvable.'
                );
            }
        } else {
            taux_change_set_flash('error', 'L’action demandée n’est pas valide.');
        }
    } catch (PDOException $exception) {
        error_log('[GRINCO admin exchange rates] Database operation failed.');
        taux_change_set_flash('error', 'L’opération ne peut pas être effectuée pour le moment.');
    }
    grinco_regenerate_csrf_token('admin_exchange_rates');
    taux_change_redirect($returnSearch, $returnPage);
}

$rateCsrfToken = grinco_csrf_token('admin_exchange_rates');
$rateFlash = isset($_SESSION['admin_exchange_rates_flash']) && is_array($_SESSION['admin_exchange_rates_flash'])
    ? $_SESSION['admin_exchange_rates_flash'] : null;
unset($_SESSION['admin_exchange_rates_flash']);
$rateResult = array('rows' => array(), 'page' => 1, 'per_page' => 10, 'total' => 0, 'total_pages' => 1, 'offset' => 0);
$rateLoadError = '';
try {
    $rateResult = taux_change_fetch_page($search, $requestedPage, $perPage);
} catch (PDOException $exception) {
    error_log('[GRINCO admin exchange rates] Unable to load initial list.');
    $rateLoadError = 'Les taux de change ne peuvent pas être chargés pour le moment.';
}

$adminPageTitle = 'Gestion des taux de change';
$adminPageDescription = 'Gestion des taux de conversion utilisés dans l’administration GRINCO RDC.';
$adminCurrentPage = 'taux-change';
$logoutCsrfToken = grinco_csrf_token('admin_logout');
$adminPageScripts = array('/assets/js/admin-taux-change.js');
include dirname(__DIR__) . '/includes/admin/head.php';
?>

<div class="admin-layout">
  <?php include dirname(__DIR__) . '/includes/admin/sidebar.php'; ?>
  <div class="admin-main-shell">
    <?php include dirname(__DIR__) . '/includes/admin/header.php'; ?>
    <main class="admin-content" id="admin-main-content" data-exchange-rates-app data-endpoint="<?php echo grinco_url_html('/admin/taux-change.php'); ?>">
      <div class="admin-page-heading admin-page-heading-with-action"><div><span class="admin-eyebrow">Paramètres commerciaux</span><h1>Gestion des taux de change</h1><p>Configurez les conversions utilisées exclusivement dans l’administration.</p></div><button type="button" class="admin-primary-button" data-bs-toggle="modal" data-bs-target="#add-rate-modal"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Ajouter un taux</span></button></div>
      <?php if ($rateFlash): ?><div class="admin-module-alert is-<?php echo grinco_admin_escape($rateFlash['type']); ?>" role="alert"><i class="bi <?php echo $rateFlash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>" aria-hidden="true"></i><span><?php echo grinco_admin_escape($rateFlash['message']); ?></span></div><?php endif; ?>
      <?php if ($rateLoadError !== ''): ?><div class="admin-module-alert is-error" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span><?php echo grinco_admin_escape($rateLoadError); ?></span></div><?php endif; ?>

      <section class="admin-table-card" aria-labelledby="rates-table-title">
        <div class="admin-table-toolbar"><div><h2 id="rates-table-title">Liste des taux</h2><p id="rate-results-summary"><?php echo $rateResult['total'] ? grinco_admin_escape(($rateResult['offset'] + 1) . '–' . min($rateResult['offset'] + $perPage, $rateResult['total']) . ' sur ' . $rateResult['total']) : 'Aucun taux'; ?></p></div><form class="admin-search-field" action="<?php echo grinco_url_html('/admin/taux-change.php'); ?>" method="GET" role="search"><label for="rate-search" class="visually-hidden">Rechercher un taux</label><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="rate-search" name="q" value="<?php echo grinco_admin_escape($search); ?>" placeholder="Devise ou taux" maxlength="50" autocomplete="off"><button type="submit" class="visually-hidden">Rechercher</button><span class="admin-search-spinner" aria-hidden="true"></span></form></div>
        <div id="rate-search-status" class="visually-hidden" role="status" aria-live="polite"></div>
        <div class="admin-table-responsive"><table class="admin-data-table admin-rates-table"><thead><tr><th scope="col">N°</th><th scope="col">Devise source</th><th scope="col">Devise de destination</th><th scope="col">Taux</th><th scope="col">Conversion d’exemple</th><th scope="col" class="admin-actions-column">Actions</th></tr></thead><tbody id="rates-table-body">
          <?php foreach ($rateResult['rows'] as $index => $rate): ?><tr><td data-label="N°"><?php echo grinco_admin_escape($rateResult['offset'] + $index + 1); ?></td><td data-label="Devise source"><span class="admin-currency-badge"><?php echo grinco_admin_escape($rate['devise_source']); ?></span></td><td data-label="Devise de destination"><span class="admin-currency-badge"><?php echo grinco_admin_escape($rate['devise_destination']); ?></span></td><td data-label="Taux"><strong class="admin-rate-value"><?php echo grinco_admin_escape($rate['taux_formatted']); ?></strong></td><td data-label="Conversion d’exemple"><span class="admin-conversion-example"><?php echo grinco_admin_escape($rate['conversion_example']); ?></span></td><td data-label="Actions" class="admin-actions-cell"><button type="button" class="admin-icon-button is-edit" title="Modifier le taux" aria-label="Modifier le taux <?php echo grinco_admin_escape($rate['devise_source'] . ' vers ' . $rate['devise_destination']); ?>" data-rate-edit data-rate-id="<?php echo (int) $rate['id']; ?>" data-rate-source="<?php echo grinco_admin_escape($rate['devise_source']); ?>" data-rate-destination="<?php echo grinco_admin_escape($rate['devise_destination']); ?>" data-rate-value="<?php echo grinco_admin_escape($rate['taux']); ?>" data-bs-toggle="modal" data-bs-target="#edit-rate-modal"><i class="bi bi-pencil-square" aria-hidden="true"></i></button><button type="button" class="admin-icon-button is-delete" title="Supprimer le taux" aria-label="Supprimer le taux <?php echo grinco_admin_escape($rate['devise_source'] . ' vers ' . $rate['devise_destination']); ?>" data-rate-delete data-rate-id="<?php echo (int) $rate['id']; ?>" data-rate-label="<?php echo grinco_admin_escape($rate['devise_source'] . ' → ' . $rate['devise_destination']); ?>" data-bs-toggle="modal" data-bs-target="#delete-rate-modal"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr><?php endforeach; ?>
          <?php if (!$rateResult['rows']): ?><tr class="admin-empty-table-row"><td colspan="6"><i class="bi bi-currency-exchange" aria-hidden="true"></i><strong>Aucun taux trouvé</strong><span>Ajoutez un taux ou modifiez votre recherche.</span></td></tr><?php endif; ?>
        </tbody></table></div>
        <nav class="admin-pagination-wrap" aria-label="Pagination des taux"><ul class="admin-pagination" id="rates-pagination"><li class="<?php echo $rateResult['page'] <= 1 ? 'is-disabled' : ''; ?>"><?php if ($rateResult['page'] <= 1): ?><span>Précédent</span><?php else: ?><a href="<?php echo taux_change_pagination_url($rateResult['page'] - 1, $search); ?>" data-page="<?php echo $rateResult['page'] - 1; ?>">Précédent</a><?php endif; ?></li><?php foreach (taux_change_pagination_items($rateResult['page'], $rateResult['total_pages']) as $item): ?><?php if (is_int($item)): ?><li class="<?php echo $item === $rateResult['page'] ? 'is-active' : ''; ?>"><a href="<?php echo taux_change_pagination_url($item, $search); ?>" data-page="<?php echo $item; ?>"<?php echo $item === $rateResult['page'] ? ' aria-current="page"' : ''; ?>><?php echo $item; ?></a></li><?php else: ?><li class="is-ellipsis"><span aria-hidden="true">…</span></li><?php endif; ?><?php endforeach; ?><li class="<?php echo $rateResult['page'] >= $rateResult['total_pages'] ? 'is-disabled' : ''; ?>"><?php if ($rateResult['page'] >= $rateResult['total_pages']): ?><span>Suivant</span><?php else: ?><a href="<?php echo taux_change_pagination_url($rateResult['page'] + 1, $search); ?>" data-page="<?php echo $rateResult['page'] + 1; ?>">Suivant</a><?php endif; ?></li></ul></nav>
      </section>
    </main>
    <?php include dirname(__DIR__) . '/includes/admin/footer.php'; ?>
  </div>
</div>

<div class="modal fade admin-modal" id="add-rate-modal" tabindex="-1" aria-labelledby="add-rate-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/taux-change.php'); ?>" method="POST"><div class="modal-header"><div><span class="admin-modal-eyebrow">Nouvelle entrée</span><h2 class="modal-title" id="add-rate-title">Ajouter un taux</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div><div class="modal-body"><input type="hidden" name="rate_action" value="create"><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($rateCsrfToken); ?>"><input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>"><input type="hidden" name="return_page" value="<?php echo (int) $rateResult['page']; ?>"><div class="admin-form-grid"><div class="admin-form-field"><label for="add-rate-source">Devise source <span aria-hidden="true">*</span></label><input type="text" id="add-rate-source" name="devise_source" maxlength="3" pattern="[A-Za-z]{3}" placeholder="USD" autocomplete="off" required></div><div class="admin-form-field"><label for="add-rate-destination">Devise de destination <span aria-hidden="true">*</span></label><input type="text" id="add-rate-destination" name="devise_destination" maxlength="3" pattern="[A-Za-z]{3}" placeholder="CNY" autocomplete="off" required></div></div><div class="admin-form-field"><label for="add-rate-value">Taux <span aria-hidden="true">*</span></label><input type="text" id="add-rate-value" name="taux" inputmode="decimal" placeholder="7,180000" required><small>Valeur strictement supérieure à zéro, six décimales maximum.</small></div></div><div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-primary-button">Ajouter le taux</button></div></form></div></div></div>

<div class="modal fade admin-modal" id="edit-rate-modal" tabindex="-1" aria-labelledby="edit-rate-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/taux-change.php'); ?>" method="POST"><div class="modal-header"><div><span class="admin-modal-eyebrow">Mise à jour</span><h2 class="modal-title" id="edit-rate-title">Modifier le taux</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div><div class="modal-body"><input type="hidden" name="rate_action" value="update"><input type="hidden" name="rate_id" id="edit-rate-id" value=""><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($rateCsrfToken); ?>"><input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>"><input type="hidden" name="return_page" value="<?php echo (int) $rateResult['page']; ?>"><div class="admin-form-grid"><div class="admin-form-field"><label for="edit-rate-source">Devise source <span aria-hidden="true">*</span></label><input type="text" id="edit-rate-source" name="devise_source" maxlength="3" pattern="[A-Za-z]{3}" required></div><div class="admin-form-field"><label for="edit-rate-destination">Devise de destination <span aria-hidden="true">*</span></label><input type="text" id="edit-rate-destination" name="devise_destination" maxlength="3" pattern="[A-Za-z]{3}" required></div></div><div class="admin-form-field"><label for="edit-rate-value">Taux <span aria-hidden="true">*</span></label><input type="text" id="edit-rate-value" name="taux" inputmode="decimal" required><small>Valeur strictement supérieure à zéro, six décimales maximum.</small></div></div><div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-primary-button">Enregistrer les modifications</button></div></form></div></div></div>

<div class="modal fade admin-modal" id="delete-rate-modal" tabindex="-1" aria-labelledby="delete-rate-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/taux-change.php'); ?>" method="POST"><div class="modal-header"><h2 class="modal-title" id="delete-rate-title">Supprimer le taux</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div><div class="modal-body admin-delete-confirmation"><input type="hidden" name="rate_action" value="delete"><input type="hidden" name="rate_id" id="delete-rate-id" value=""><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($rateCsrfToken); ?>"><input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>"><input type="hidden" name="return_page" value="<?php echo (int) $rateResult['page']; ?>"><span class="admin-delete-icon" aria-hidden="true"><i class="bi bi-trash"></i></span><p>Confirmez-vous la suppression du taux <strong id="delete-rate-label"></strong> ?</p><small>Les produits resteront enregistrés, mais leur conversion ne sera plus affichée si ce taux est utilisé.</small></div><div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-danger-button">Supprimer</button></div></form></div></div></div>

<?php include dirname(__DIR__) . '/includes/admin/scripts.php'; ?>
