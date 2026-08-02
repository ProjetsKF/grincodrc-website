<?php
require_once dirname(__DIR__) . '/includes/admin-auth.php';

grinco_admin_bootstrap();
grinco_admin_require_authentication();

$passwordMessage = '';
$passwordMessageType = '';
$passwordCsrfToken = grinco_csrf_token('admin_password');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = isset($_POST['current_password']) && is_scalar($_POST['current_password'])
        ? (string) $_POST['current_password']
        : '';
    $newPassword = isset($_POST['new_password']) && is_scalar($_POST['new_password'])
        ? (string) $_POST['new_password']
        : '';
    $confirmPassword = isset($_POST['confirm_password']) && is_scalar($_POST['confirm_password'])
        ? (string) $_POST['confirm_password']
        : '';
    $receivedCsrfToken = isset($_POST['csrf_token']) && is_scalar($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : '';

    if (!grinco_validate_csrf_token('admin_password', $receivedCsrfToken)) {
        $passwordMessage = 'Votre session a expiré. Veuillez réessayer.';
        $passwordMessageType = 'danger';
    } elseif (!grinco_validate_request_origin()) {
        $passwordMessage = 'La demande n’a pas pu être vérifiée.';
        $passwordMessageType = 'danger';
    } elseif ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $passwordMessage = 'Tous les champs sont obligatoires.';
        $passwordMessageType = 'danger';
    } elseif (strlen($newPassword) < 12 || strlen($newPassword) > 128) {
        $passwordMessage = 'Le nouveau mot de passe doit contenir entre 12 et 128 caractères.';
        $passwordMessageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $passwordMessage = 'La confirmation du nouveau mot de passe ne correspond pas.';
        $passwordMessageType = 'danger';
    } else {
        try {
            $statement = grinco_database()->prepare(
                'SELECT mot_de_passe FROM utilisateurs_admin WHERE id = :id LIMIT 1'
            );
            $statement->execute(array(':id' => (int) $_SESSION['grinco_admin_id']));
            $administrator = $statement->fetch();

            if (
                !$administrator
                || !password_verify($currentPassword, (string) $administrator['mot_de_passe'])
                || password_verify($newPassword, (string) $administrator['mot_de_passe'])
            ) {
                $passwordMessage = 'Le mot de passe n’a pas pu être modifié. Vérifiez les informations saisies.';
                $passwordMessageType = 'danger';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStatement = grinco_database()->prepare(
                    'UPDATE utilisateurs_admin SET mot_de_passe = :mot_de_passe WHERE id = :id'
                );
                $updateStatement->execute(array(
                    ':mot_de_passe' => $newHash,
                    ':id' => (int) $_SESSION['grinco_admin_id']
                ));

                session_regenerate_id(true);
                $_SESSION['grinco_admin_last_activity'] = time();
                $passwordMessage = 'Votre mot de passe a été modifié avec succès.';
                $passwordMessageType = 'success';
            }
        } catch (PDOException $exception) {
            error_log('[GRINCO admin password] Database error.');
            $passwordMessage = 'Le mot de passe ne peut pas être modifié pour le moment.';
            $passwordMessageType = 'danger';
        }
    }

    $passwordCsrfToken = grinco_regenerate_csrf_token('admin_password');
    unset($currentPassword, $newPassword, $confirmPassword);
}

$pageTitle = 'Changer le mot de passe';
$pageDescription = 'Modification sécurisée du mot de passe administrateur GRINCO RDC.';
$currentPage = '';
$bodyClass = 'inner-page light-background';

include dirname(__DIR__) . '/includes/head.php';
?>

<header class="bg-white border-bottom">
  <div class="container py-3 d-flex align-items-center justify-content-between gap-3">
    <a href="<?php echo grinco_url_html('/admin/tableau-de-bord.php'); ?>" class="text-decoration-none fw-semibold text-dark">
      <i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Tableau de bord
    </a>
    <span class="text-muted d-none d-sm-inline"><?php echo grinco_admin_escape($_SESSION['grinco_admin_email']); ?></span>
  </div>
</header>

<main class="main">
  <section class="section py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
              <h1 class="h3 mb-3">Changer le mot de passe</h1>
              <p class="text-muted mb-4">Choisissez un mot de passe personnel d’au moins 12 caractères.</p>

              <?php if ($passwordMessage !== ''): ?>
                <div class="alert alert-<?php echo grinco_admin_escape($passwordMessageType); ?>" role="alert" aria-live="polite">
                  <?php echo grinco_admin_escape($passwordMessage); ?>
                </div>
              <?php endif; ?>

              <form action="<?php echo grinco_url_html('/admin/changer-mot-de-passe.php'); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($passwordCsrfToken); ?>">

                <div class="mb-3">
                  <label for="current-password" class="form-label">Mot de passe actuel</label>
                  <input type="password" class="form-control" id="current-password" name="current_password" autocomplete="current-password" required>
                </div>

                <div class="mb-3">
                  <label for="new-password" class="form-label">Nouveau mot de passe</label>
                  <input type="password" class="form-control" id="new-password" name="new_password" minlength="12" maxlength="128" autocomplete="new-password" required>
                </div>

                <div class="mb-4">
                  <label for="confirm-password" class="form-label">Confirmer le nouveau mot de passe</label>
                  <input type="password" class="form-control" id="confirm-password" name="confirm_password" minlength="12" maxlength="128" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn btn-success w-100">Enregistrer le nouveau mot de passe</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include dirname(__DIR__) . '/includes/scripts.php'; ?>
