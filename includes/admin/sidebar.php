<?php
$adminCurrentPage = isset($adminCurrentPage) ? $adminCurrentPage : '';
$adminNavigationItems = array(
    array('key' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'bi-grid-1x2-fill', 'url' => '/admin/tableau-de-bord.php', 'enabled' => true),
    array('key' => 'categories', 'label' => 'Catégories', 'icon' => 'bi-tags', 'url' => '/admin/categories.php', 'enabled' => true),
    array('key' => 'marques', 'label' => 'Marques', 'icon' => 'bi-bookmark-star', 'url' => '/admin/marques.php', 'enabled' => true),
    array('key' => 'produits', 'label' => 'Produits', 'icon' => 'bi-box-seam', 'url' => '/admin/produits.php', 'enabled' => true),
    array('key' => 'media', 'label' => 'Images et documents', 'icon' => 'bi-images', 'url' => '/admin/images-documents.php', 'enabled' => true),
    array('key' => 'demandes-devis', 'label' => 'Demandes de devis', 'icon' => 'bi-file-earmark-text', 'url' => '/admin/demandes-devis.php', 'enabled' => true),
    array('key' => 'taux-change', 'label' => 'Taux de change', 'icon' => 'bi-currency-exchange', 'url' => '/admin/taux-change.php', 'enabled' => true),
    array('key' => 'settings', 'label' => 'Paramètres', 'icon' => 'bi-gear', 'url' => '/admin/parametres.php', 'enabled' => false)
);
?>

<aside class="admin-sidebar" id="admin-sidebar" aria-label="Navigation de l’administration">
  <div class="admin-sidebar-brand">
    <a href="<?php echo grinco_url_html('/admin/tableau-de-bord.php'); ?>" aria-label="Administration GRINCO RDC">
      <img src="<?php echo grinco_url_html('/assets/img/logo%20grinco.png'); ?>" alt="Logo GRINCO RDC">
    </a>
    <button type="button" class="admin-sidebar-close" data-admin-sidebar-close aria-label="Fermer le menu">
      <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
  </div>

  <div class="admin-sidebar-label">Gestion du catalogue</div>

  <nav class="admin-sidebar-nav" aria-label="Menu principal">
    <ul>
      <?php foreach ($adminNavigationItems as $item): ?>
        <li>
          <?php if ($item['enabled']): ?>
            <a
              href="<?php echo grinco_url_html($item['url']); ?>"
              class="admin-nav-link<?php echo $adminCurrentPage === $item['key'] ? ' is-active' : ''; ?>"
              <?php echo $adminCurrentPage === $item['key'] ? 'aria-current="page"' : ''; ?>
            >
              <i class="bi <?php echo grinco_admin_escape($item['icon']); ?>" aria-hidden="true"></i>
              <span><?php echo grinco_admin_escape($item['label']); ?></span>
            </a>
          <?php else: ?>
            <span class="admin-nav-link is-disabled" aria-disabled="true" title="Module disponible prochainement">
              <i class="bi <?php echo grinco_admin_escape($item['icon']); ?>" aria-hidden="true"></i>
              <span><?php echo grinco_admin_escape($item['label']); ?></span>
            </span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div class="admin-sidebar-footer">
    <span class="admin-sidebar-status"><i class="bi bi-shield-check" aria-hidden="true"></i> Session sécurisée</span>
    <small>GRINCO RDC</small>
  </div>
</aside>

<button type="button" class="admin-sidebar-overlay" data-admin-sidebar-close aria-label="Fermer le menu latéral" tabindex="-1"></button>
