<?php
require_once __DIR__ . '/config/app.php';

$loginEmail = '';
$loginMessage = '';
$loginMessageType = '';
$loginFieldErrors = array();

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Robots-Tag: noindex, nofollow');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginEmail = isset($_POST['email']) && is_scalar($_POST['email'])
        ? trim((string) $_POST['email'])
        : '';
    $loginPassword = isset($_POST['password']) && is_scalar($_POST['password'])
        ? (string) $_POST['password']
        : '';

    if ($loginEmail === '') {
        $loginFieldErrors['email'] = 'Veuillez renseigner votre adresse e-mail.';
    } elseif (strlen($loginEmail) > 190 || !filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
        $loginFieldErrors['email'] = 'Veuillez saisir une adresse e-mail valide.';
    }

    if ($loginPassword === '') {
        $loginFieldErrors['password'] = 'Veuillez renseigner votre mot de passe.';
    }

    if (!empty($loginFieldErrors)) {
        $loginMessage = 'Veuillez compléter correctement les champs indiqués.';
        $loginMessageType = 'error';
    } else {
        $loginMessage = 'L’espace administrateur est en cours de configuration. Aucune authentification n’a été effectuée.';
        $loginMessageType = 'info';
    }

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
              <div class="login-input-wrap">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input
                  type="password"
                  id="admin-password"
                  name="password"
                  autocomplete="current-password"
                  required<?php echo login_invalid_attribute($loginFieldErrors, 'password'); ?>
                >
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

</body>
</html>
