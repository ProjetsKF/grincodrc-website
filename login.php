<?php
require_once __DIR__ . '/includes/admin-auth.php';

grinco_admin_bootstrap();

if (grinco_admin_is_authenticated()) {
    header('Location: ' . grinco_url('/admin/tableau-de-bord.php'));
    exit;
}

$loginEmail = '';
$loginMessage = '';
$loginMessageType = '';
$loginFieldErrors = array();
$loginRequestId = substr(sha1(uniqid('', true)), 0, 12);
$loginCsrfToken = grinco_csrf_token('admin_login');

if (!empty($_SESSION['grinco_admin_login_notice'])) {
    $loginMessage = (string) $_SESSION['grinco_admin_login_notice'];
    $loginMessageType = 'info';
    unset($_SESSION['grinco_admin_login_notice']);
}

if (!headers_sent()) {
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginEmail = isset($_POST['email']) && is_scalar($_POST['email'])
        ? trim((string) $_POST['email'])
        : '';
    $loginPassword = isset($_POST['password']) && is_scalar($_POST['password'])
        ? (string) $_POST['password']
        : '';
    $receivedCsrfToken = isset($_POST['csrf_token']) && is_scalar($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : '';

    if ($loginEmail === '') {
        $loginFieldErrors['email'] = 'Veuillez renseigner votre adresse e-mail.';
    } elseif (strlen($loginEmail) > 190 || !filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
        $loginFieldErrors['email'] = 'Veuillez saisir une adresse e-mail valide.';
    }

    if ($loginPassword === '') {
        $loginFieldErrors['password'] = 'Veuillez renseigner votre mot de passe.';
    }

    if (!grinco_validate_csrf_token('admin_login', $receivedCsrfToken)) {
        $loginMessage = 'Votre session a expiré. Veuillez recharger la page et réessayer.';
        $loginMessageType = 'error';
        grinco_security_log('admin_login', 'rejected', 'invalid_csrf', 0, 0, $loginEmail, $loginRequestId);
    } elseif (!grinco_validate_request_origin()) {
        $loginMessage = 'La connexion n’a pas pu être effectuée. Veuillez réessayer.';
        $loginMessageType = 'error';
        grinco_security_log('admin_login', 'rejected', 'invalid_origin', 0, 0, $loginEmail, $loginRequestId);
    } elseif (!empty($loginFieldErrors)) {
        $loginMessage = 'Veuillez compléter correctement les champs indiqués.';
        $loginMessageType = 'error';
    } else {
        $rateResult = grinco_check_rate_limit('admin_login', grinco_client_ip(), strtolower($loginEmail));

        if (!$rateResult['allowed']) {
            $loginMessage = 'Identifiants invalides ou accès temporairement indisponible.';
            $loginMessageType = 'error';
            grinco_security_log('admin_login', 'rejected', 'login_rate_limited', 0, 0, $loginEmail, $loginRequestId);
        } else {
            try {
                $statement = grinco_database()->prepare(
                    'SELECT id, nom, email, mot_de_passe FROM utilisateurs_admin WHERE email = :email LIMIT 1'
                );
                $statement->execute(array(':email' => $loginEmail));
                $administrator = $statement->fetch();

                $dummyHash = '$2y$10$asALHufvcdjIoVKKM7Cume3SsSFoeS2HW143Noweh1hW0rpmgxWNC';
                $storedHash = $administrator && isset($administrator['mot_de_passe'])
                    ? (string) $administrator['mot_de_passe']
                    : $dummyHash;
                $passwordIsValid = password_verify($loginPassword, $storedHash);

                if ($administrator && $passwordIsValid) {
                    if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                        $updatedHash = password_hash($loginPassword, PASSWORD_DEFAULT);
                        $updateStatement = grinco_database()->prepare(
                            'UPDATE utilisateurs_admin SET mot_de_passe = :mot_de_passe WHERE id = :id'
                        );
                        $updateStatement->execute(array(
                            ':mot_de_passe' => $updatedHash,
                            ':id' => (int) $administrator['id']
                        ));
                    }

                    grinco_admin_login($administrator);
                    grinco_security_log('admin_login', 'accepted', 'authenticated', 0, 0, $loginEmail, $loginRequestId);
                    unset($loginPassword);
                    header('Location: ' . grinco_url('/admin/tableau-de-bord.php'));
                    exit;
                }

                $loginMessage = 'Identifiants invalides ou accès temporairement indisponible.';
                $loginMessageType = 'error';
                grinco_security_log('admin_login', 'rejected', 'invalid_credentials', 0, 0, $loginEmail, $loginRequestId);
            } catch (PDOException $exception) {
                error_log('[GRINCO admin login][' . $loginRequestId . '] Database error.');
                $loginMessage = 'La connexion est temporairement indisponible. Veuillez réessayer plus tard.';
                $loginMessageType = 'error';
                grinco_security_log('admin_login', 'error', 'database_unavailable', 0, 0, $loginEmail, $loginRequestId);
            }
        }
    }

    $loginCsrfToken = grinco_regenerate_csrf_token('admin_login');
    unset($loginPassword);
}

function login_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function login_invalid_attribute($fieldErrors, $field)
{
    return isset($fieldErrors[$field]) ? ' aria-invalid="true"' : '';
}

function login_field_error($fieldErrors, $field)
{
    if (!isset($fieldErrors[$field])) {
        return '';
    }

    return '<div class="login-field-error">'
        . login_escape($fieldErrors[$field])
        . '</div>';
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Accès à l’espace administrateur de GRINCO RDC.">
  <meta name="robots" content="noindex, nofollow">
  <title>Accès administrateur | GRINCO RDC</title>

  <link href="assets/img/ico.png" rel="icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@500;600;700&family=Raleway:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="login-page">

<main class="main">
  <section class="login-section" aria-labelledby="login-title">
    <div class="container">
      <div class="login-shell">
        <div class="login-card">
          <div class="login-card-header">
            <a href="<?php echo grinco_url_html('/'); ?>" class="login-logo" aria-label="Retour à l’accueil GRINCO RDC">
              <img src="assets/img/logo%20grinco.png" alt="Logo GRINCO RDC">
            </a>
            <h1 id="login-title">Accès administrateur</h1>
          </div>

          <?php if ($loginMessage !== ''): ?>
            <div class="login-alert login-alert-<?php echo login_escape($loginMessageType); ?>" role="alert">
              <i class="bi <?php echo $loginMessageType === 'error' ? 'bi-exclamation-circle' : 'bi-info-circle'; ?>" aria-hidden="true"></i>
              <span><?php echo login_escape($loginMessage); ?></span>
            </div>
          <?php endif; ?>

          <form action="<?php echo grinco_url_html('/connexion'); ?>" method="POST" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo login_escape($loginCsrfToken); ?>">

            <div class="login-field">
              <label for="admin-email">Adresse e-mail</label>
              <div class="login-input-wrap">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input
                  type="email"
                  id="admin-email"
                  name="email"
                  value="<?php echo login_escape($loginEmail); ?>"
                  maxlength="190"
                  autocomplete="username"
                  required<?php echo login_invalid_attribute($loginFieldErrors, 'email'); ?>
                >
              </div>
              <?php echo login_field_error($loginFieldErrors, 'email'); ?>
            </div>

            <div class="login-field">
              <label for="admin-password">Mot de passe</label>
              <div class="login-input-wrap has-password-toggle">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input
                  type="password"
                  id="admin-password"
                  name="password"
                  autocomplete="current-password"
                  required<?php echo login_invalid_attribute($loginFieldErrors, 'password'); ?>
                >
                <button
                  type="button"
                  class="login-password-toggle"
                  aria-label="Afficher le mot de passe"
                  aria-controls="admin-password"
                  aria-pressed="false"
                >
                  <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
              </div>
              <?php echo login_field_error($loginFieldErrors, 'password'); ?>
            </div>

            <button type="submit" class="login-submit">
              <span>Se connecter</span>
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </button>
          </form>

          <div class="login-back">
            <a href="<?php echo grinco_url_html('/'); ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Retour au site</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<script>
(function () {
  'use strict';

  var passwordField = document.getElementById('admin-password');
  var toggleButton = document.querySelector('.login-password-toggle');

  if (!passwordField || !toggleButton) {
    return;
  }

  toggleButton.addEventListener('click', function () {
    var passwordIsVisible = passwordField.type === 'text';
    var icon = toggleButton.querySelector('i');

    passwordField.type = passwordIsVisible ? 'password' : 'text';
    toggleButton.setAttribute('aria-pressed', passwordIsVisible ? 'false' : 'true');
    toggleButton.setAttribute('aria-label', passwordIsVisible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');

    if (icon) {
      icon.classList.toggle('bi-eye', passwordIsVisible);
      icon.classList.toggle('bi-eye-slash', !passwordIsVisible);
    }
  });
}());
</script>

</body>
</html>
