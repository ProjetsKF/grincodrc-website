<?php
require_once __DIR__ . '/includes/devis-panier.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

$catalogueProducts = array();
$catalogueError = '';
try {
    $catalogueProducts = grinco_catalogue_public_products();
} catch (PDOException $exception) {
    error_log('[GRINCO public catalogue] Unable to load products.');
    $catalogueError = 'Le catalogue ne peut pas être chargé pour le moment.';
}
$cartCsrfToken = grinco_csrf_token('quote_cart');
$cartFlash = grinco_quote_cart_take_flash();

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
    <div class="container section-title" data-aos="fade-up"><h2>Nos produits</h2><p>Sélectionnez un ou plusieurs produits pour recevoir une proposition adaptée. Aucun prix n’est affiché dans le catalogue public.</p></div>
    <div class="container">
      <?php if ($cartFlash): ?><div class="catalogue-alert is-<?php echo htmlspecialchars($cartFlash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?php echo htmlspecialchars($cartFlash['message'], ENT_QUOTES, 'UTF-8'); ?><a href="<?php echo grinco_url_html('/panier-devis'); ?>">Voir ma sélection</a></div><?php endif; ?>
      <?php if ($catalogueError !== ''): ?><div class="catalogue-alert is-error" role="alert"><?php echo htmlspecialchars($catalogueError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <?php if (empty($catalogueProducts) && $catalogueError === ''): ?>
        <div class="catalogue-empty"><i class="bi bi-box-seam" aria-hidden="true"></i><h3>Le catalogue est en cours de préparation</h3><p>Les premiers produits seront disponibles prochainement.</p></div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($catalogueProducts as $product): ?>
            <div class="col-md-6 col-xl-4" data-aos="fade-up"><article class="catalogue-product-card">
              <a class="catalogue-product-image" href="<?php echo grinco_url_html('/produit?id=' . (int) $product['id']); ?>" aria-label="Voir <?php echo htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8'); ?>"><?php if ($product['image_url'] !== ''): ?><img src="<?php echo htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy"><?php else: ?><span><i class="bi bi-image" aria-hidden="true"></i> Image à venir</span><?php endif; ?></a>
              <div class="catalogue-product-body"><div class="catalogue-product-meta"><span><?php echo htmlspecialchars($product['categorie_nom'], ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($product['marque_nom'], ENT_QUOTES, 'UTF-8'); ?></span></div><small><?php echo htmlspecialchars($product['reference'], ENT_QUOTES, 'UTF-8'); ?></small><h3><a href="<?php echo grinco_url_html('/produit?id=' . (int) $product['id']); ?>"><?php echo htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8'); ?></a></h3><?php if ($product['modele'] !== ''): ?><p class="catalogue-product-model">Modèle : <?php echo htmlspecialchars($product['modele'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><div class="catalogue-product-actions"><a class="catalogue-detail-link" href="<?php echo grinco_url_html('/produit?id=' . (int) $product['id']); ?>">Voir le produit</a><form action="<?php echo grinco_url_html('/panier-devis'); ?>" method="POST"><input type="hidden" name="cart_action" value="add"><input type="hidden" name="produit_id" value="<?php echo (int) $product['id']; ?>"><input type="hidden" name="quantite" value="1"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cartCsrfToken, ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" class="catalogue-add-button"><i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter au devis</button></form></div></div>
            </article></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
