<?php
require_once __DIR__ . '/includes/devis-panier.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

$productId = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$product = null;
$productImages = array();
$productDocuments = array();
try {
    $product = $productId > 0 ? grinco_catalogue_public_product($productId) : null;
    if ($product) {
        $productImages = grinco_catalogue_public_product_images($productId);
        $productDocuments = grinco_catalogue_public_product_documents($productId);
    }
} catch (PDOException $exception) {
    error_log('[GRINCO public product] Unable to load product.');
}
if (!$product) {
    http_response_code(404);
}
$cartCsrfToken = grinco_csrf_token('quote_cart');
$cartFlash = grinco_quote_cart_take_flash();

$pageTitle = $product ? $product['nom'] : 'Produit introuvable';
$pageDescription = $product ? 'Découvrez ' . $product['nom'] . ' dans le catalogue GRINCO RDC.' : 'Le produit demandé est introuvable.';
$currentPage = 'produit';
$bodyClass = 'catalogue-page';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li><li><a href="<?php echo grinco_url_html('/catalogue'); ?>">Catalogue</a></li><li class="current">Produit</li></ol></nav></div></div>
  <section class="product-detail-section section"><div class="container">
    <?php if (!$product): ?>
      <div class="catalogue-empty"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><h2>Produit introuvable</h2><p>Ce produit n’existe pas ou n’est plus disponible.</p><a class="catalogue-add-button" href="<?php echo grinco_url_html('/catalogue'); ?>">Retour au catalogue</a></div>
    <?php else: ?>
      <?php if ($cartFlash): ?><div class="catalogue-alert is-<?php echo htmlspecialchars($cartFlash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?php echo htmlspecialchars($cartFlash['message'], ENT_QUOTES, 'UTF-8'); ?><a href="<?php echo grinco_url_html('/panier-devis'); ?>">Voir ma sélection</a></div><?php endif; ?>
      <div class="row g-5 align-items-start">
        <div class="col-lg-6"><div class="product-detail-image"><?php if ($product['image_url'] !== ''): ?><img src="<?php echo htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8'); ?>"><?php else: ?><span><i class="bi bi-image" aria-hidden="true"></i> Image à venir</span><?php endif; ?></div></div>
        <div class="col-lg-6"><div class="product-detail-copy"><span class="product-detail-reference"><?php echo htmlspecialchars($product['reference'], ENT_QUOTES, 'UTF-8'); ?></span><h2><?php echo htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8'); ?></h2><dl><div><dt>Catégorie</dt><dd><?php echo htmlspecialchars($product['categorie_nom'], ENT_QUOTES, 'UTF-8'); ?></dd></div><div><dt>Marque</dt><dd><?php echo htmlspecialchars($product['marque_nom'], ENT_QUOTES, 'UTF-8'); ?></dd></div><?php if ($product['modele'] !== ''): ?><div><dt>Modèle</dt><dd><?php echo htmlspecialchars($product['modele'], ENT_QUOTES, 'UTF-8'); ?></dd></div><?php endif; ?></dl><?php if ($product['description'] !== ''): ?><p><?php echo nl2br(htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?><form class="product-detail-add" action="<?php echo grinco_url_html('/panier-devis'); ?>" method="POST"><input type="hidden" name="cart_action" value="add"><input type="hidden" name="produit_id" value="<?php echo (int) $product['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cartCsrfToken, ENT_QUOTES, 'UTF-8'); ?>"><label for="product-quantity">Quantité</label><input type="number" id="product-quantity" name="quantite" value="1" min="1" max="1000" step="1" required><button type="submit" class="catalogue-add-button"><i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter au devis</button></form></div></div>
      </div>
      <?php if (count($productImages) > 1): ?><section class="product-public-media" aria-labelledby="product-images-title"><h2 id="product-images-title">Images du produit</h2><div class="product-public-image-grid"><?php foreach ($productImages as $index => $image): ?><a href="<?php echo htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" aria-label="Afficher l’image <?php echo $index + 1; ?> de <?php echo htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8'); ?>"><img src="<?php echo htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8'); ?> — vue <?php echo $index + 1; ?>" loading="lazy"></a><?php endforeach; ?></div></section><?php endif; ?>
      <?php if ($productDocuments): ?><section class="product-public-media" aria-labelledby="product-documents-title"><h2 id="product-documents-title">Documents du produit</h2><div class="product-public-documents"><?php foreach ($productDocuments as $document): ?><a href="<?php echo grinco_url_html('/document-produit?id=' . (int) $document['id']); ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i><span><?php echo htmlspecialchars($document['label'], ENT_QUOTES, 'UTF-8'); ?></span><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a><?php endforeach; ?></div></section><?php endif; ?>
    <?php endif; ?>
  </div></section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
