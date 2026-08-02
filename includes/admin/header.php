<?php
$logoutCsrfToken = isset($logoutCsrfToken)
    ? $logoutCsrfToken
    : grinco_csrf_token('admin_logout');
$adminName = isset($_SESSION['grinco_admin_name']) ? (string) $_SESSION['grinco_admin_name'] : 'Administrateur';
$adminEmail = isset($_SESSION['grinco_admin_email']) ? (string) $_SESSION['grinco_admin_email'] : '';
$adminInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($adminName, 0, 1));
?>

<header class="admin-topbar">
  <div class="admin-topbar-left">
    <button
      type="button"
      class="admin-menu-toggle"
      data-admin-sidebar-toggle
      aria-controls="admin-sidebar"
      aria-expanded="false"
      aria-label="Ouvrir le menu"
    >
      <i class="bi bi-list" aria-hidden="true"></i>
    </button>
    <span class="admin-topbar-context">Administration</span>
  </div>

  <div class="admin-account">
    <div class="admin-account-identity">
      <span class="admin-avatar" aria-hidden="true"><?php echo grinco_admin_escape($adminInitial); ?></span>
      <span class="admin-account-copy">
        <strong><?php echo grinco_admin_escape($adminName); ?></strong>
        <?php if ($adminEmail !== ''): ?>
          <small><?php echo grinco_admin_escape($adminEmail); ?></small>
        <?php endif; ?>
      </span>
    </div>

    <a href="<?php echo grinco_url_html('/admin/changer-mot-de-passe.php'); ?>" class="admin-topbar-action">
      <i class="bi bi-key" aria-hidden="true"></i>
      <span>Mot de passe</span>
    </a>

    <form action="<?php echo grinco_url_html('/admin/deconnexion.php'); ?>" method="POST" class="admin-logout-form">
      <input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($logoutCsrfToken); ?>">
      <button type="submit" class="admin-logout-button">
        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
        <span>Déconnexion</span>
      </button>
    </form>
  </div>
</header>
