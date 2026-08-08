<?php
require_once __DIR__ . '/includes/devis-panier.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

$catalogueSearch = isset($_GET['q']) && is_scalar($_GET['q']) ? trim((string) $_GET['q']) : '';
if (function_exists('mb_substr')) {
    $catalogueSearch = mb_substr($catalogueSearch, 0, 100, 'UTF-8');
} else {
    $catalogueSearch = substr($catalogueSearch, 0, 100);
}
$catalogueRequestedPage = isset($_GET['page']) && ctype_digit((string) $_GET['page']) && (int) $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$catalogueResult = array('rows' => array(), 'page' => 1, 'total_pages' => 1, 'total' => 0);
$catalogueError = '';
try {
    $catalogueResult = grinco_catalogue_public_page($catalogueRequestedPage, 12, $catalogueSearch, 0);
} catch (PDOException $exception) {
    error_log('[GRINCO public catalogue] Unable to load products.');
    $catalogueError = 'Le catalogue ne peut pas être chargé pour le moment.';
}
$cartFlash = grinco_quote_cart_take_flash();
$catalogueShowSearch = true;
$cataloguePagePath = '/catalogue';
$catalogueEmptyTitle = $catalogueSearch === '' ? 'Aucun produit disponible' : 'Aucun résultat';

$pageTitle = 'Catalogue';
$pageDescription = 'Consultez les véhicules et engins GRINCO RDC et composez votre demande de devis multi-produits.';
$currentPage = 'catalogue';
$bodyClass = 'catalogue-page';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0">Catalogue</h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li><li class="current">Catalogue</li></ol></nav></div></div>
  <section class="catalogue-products section">
    <div class="container section-title" data-aos="fade-up"><h2>Nos produits</h2><p>Sélectionnez un produit pour recevoir une proposition adaptée. Aucun prix n’est affiché dans le catalogue public.</p></div>
    <div class="container">
      <?php if ($cartFlash): ?><div class="catalogue-alert is-<?php echo htmlspecialchars($cartFlash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?php echo htmlspecialchars($cartFlash['message'], ENT_QUOTES, 'UTF-8'); ?><a href="<?php echo grinco_url_html('/panier-devis'); ?>">Voir ma sélection</a></div><?php endif; ?>
      <?php include __DIR__ . '/includes/catalogue-grid.php'; ?>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
