<?php
require_once __DIR__ . '/includes/devis-panier.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

function cart_request_id($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 0;
}

function cart_request_quantity($value)
{
    return ctype_digit((string) $value) && (int) $value >= 1 && (int) $value <= grinco_quote_cart_max_quantity() ? (int) $value : 0;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = grinco_post_value('cart_action');
    $productId = cart_request_id(grinco_post_value('produit_id'));
    $quantity = cart_request_quantity(grinco_post_value('quantite'));
    if (!grinco_validate_csrf_token('quote_cart', grinco_post_value('csrf_token'))) {
        grinco_quote_cart_set_flash('error', 'Votre session a expiré. Veuillez réessayer.');
    } elseif (!grinco_validate_request_origin()) {
        grinco_quote_cart_set_flash('error', 'La demande n’a pas pu être vérifiée.');
    } else {
        try {
            if ($action === 'add') {
                if ($productId <= 0 || $quantity <= 0 || !grinco_catalogue_public_product($productId)) {
                    throw new RuntimeException('Le produit ou la quantité sélectionnée n’est pas valide.');
                }
                grinco_quote_cart_add($productId, $quantity);
                grinco_quote_cart_set_flash('success', 'Le produit a été ajouté à votre demande de devis.');
            } elseif ($action === 'update') {
                if ($productId <= 0 || $quantity <= 0 || !grinco_quote_cart_update($productId, $quantity)) {
                    throw new RuntimeException('Le produit ou la quantité sélectionnée n’est pas valide.');
                }
                grinco_quote_cart_set_flash('success', 'La quantité a été mise à jour.');
            } elseif ($action === 'remove') {
                if ($productId <= 0) {
                    throw new RuntimeException('Le produit sélectionné n’est pas valide.');
                }
                grinco_quote_cart_remove($productId);
                grinco_quote_cart_set_flash('success', 'Le produit a été retiré de votre sélection.');
            } elseif ($action === 'clear') {
                grinco_quote_cart_clear();
                grinco_quote_cart_set_flash('success', 'Votre sélection a été vidée.');
            } else {
                throw new RuntimeException('L’action demandée n’est pas valide.');
            }
        } catch (RuntimeException $exception) {
            grinco_quote_cart_set_flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log('[GRINCO quote cart] Database operation failed.');
            grinco_quote_cart_set_flash('error', 'L’opération ne peut pas être effectuée pour le moment.');
        }
    }
    grinco_regenerate_csrf_token('quote_cart');
    header('Location: ' . grinco_url('/panier-devis'));
    exit;
}

$cartData = array('items' => array(), 'missing_ids' => array(), 'cart' => array());
$cartError = '';
try {
    $cartData = grinco_quote_cart_items();
} catch (PDOException $exception) {
    error_log('[GRINCO quote cart] Unable to load products.');
    $cartError = 'Votre sélection ne peut pas être chargée pour le moment.';
}
$cartFlash = grinco_quote_cart_take_flash();
$cartCsrfToken = grinco_csrf_token('quote_cart');
$pageTitle = 'Ma demande de devis';
$pageDescription = 'Gérez les produits et quantités de votre demande de devis GRINCO RDC.';
$currentPage = 'panier-devis';
$bodyClass = 'quote-cart-page';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0">Ma sélection de devis</h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li><li class="current">Sélection devis</li></ol></nav></div></div>
  <section class="quote-cart-section section"><div class="container">
    <?php if ($cartFlash): ?><div class="catalogue-alert is-<?php echo htmlspecialchars($cartFlash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?php echo htmlspecialchars($cartFlash['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($cartError !== ''): ?><div class="catalogue-alert is-error" role="alert"><?php echo htmlspecialchars($cartError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if (!empty($cartData['missing_ids'])): ?><div class="catalogue-alert is-error" role="alert">Un produit de votre sélection n’est plus disponible. Videz la sélection avant de continuer.</div><?php endif; ?>
    <?php if (empty($cartData['items']) && empty($cartData['missing_ids'])): ?>
      <div class="catalogue-empty"><i class="bi bi-clipboard2" aria-hidden="true"></i><h2>Votre sélection est vide</h2><p>Parcourez le catalogue et ajoutez les produits qui vous intéressent.</p><a class="catalogue-add-button" href="<?php echo grinco_url_html('/catalogue'); ?>">Explorer le catalogue</a></div>
    <?php else: ?>
      <div class="quote-cart-card"><div class="table-responsive"><table class="quote-cart-table"><thead><tr><th>Image</th><th>Référence</th><th>Produit</th><th>Catégorie</th><th>Marque</th><th>Quantité</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($cartData['items'] as $item): ?><tr><td data-label="Image"><span class="quote-cart-image"><?php if ($item['image_url'] !== ''): ?><img src="<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['nom'], ENT_QUOTES, 'UTF-8'); ?>"><?php else: ?><i class="bi bi-image" aria-hidden="true"></i><?php endif; ?></span></td><td data-label="Référence"><strong><?php echo htmlspecialchars($item['reference'], ENT_QUOTES, 'UTF-8'); ?></strong></td><td data-label="Produit"><?php echo htmlspecialchars($item['nom'], ENT_QUOTES, 'UTF-8'); ?><?php if ($item['modele'] !== ''): ?><small><?php echo htmlspecialchars($item['modele'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?></td><td data-label="Catégorie"><?php echo htmlspecialchars($item['categorie_nom'], ENT_QUOTES, 'UTF-8'); ?></td><td data-label="Marque"><?php echo htmlspecialchars($item['marque_nom'], ENT_QUOTES, 'UTF-8'); ?></td><td data-label="Quantité"><form class="quote-cart-quantity" action="<?php echo grinco_url_html('/panier-devis'); ?>" method="POST"><input type="hidden" name="cart_action" value="update"><input type="hidden" name="produit_id" value="<?php echo (int) $item['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cartCsrfToken, ENT_QUOTES, 'UTF-8'); ?>"><label class="visually-hidden" for="quantity-<?php echo (int) $item['id']; ?>">Quantité de <?php echo htmlspecialchars($item['nom'], ENT_QUOTES, 'UTF-8'); ?></label><input id="quantity-<?php echo (int) $item['id']; ?>" type="number" name="quantite" value="<?php echo (int) $item['quantite']; ?>" min="1" max="1000" required><button type="submit" title="Mettre à jour" aria-label="Mettre à jour la quantité"><i class="bi bi-check-lg" aria-hidden="true"></i></button></form></td><td data-label="Actions"><form action="<?php echo grinco_url_html('/panier-devis'); ?>" method="POST"><input type="hidden" name="cart_action" value="remove"><input type="hidden" name="produit_id" value="<?php echo (int) $item['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cartCsrfToken, ENT_QUOTES, 'UTF-8'); ?>"><button class="quote-cart-remove" type="submit" title="Retirer" aria-label="Retirer <?php echo htmlspecialchars($item['nom'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-trash" aria-hidden="true"></i></button></form></td></tr><?php endforeach; ?>
      </tbody></table></div><div class="quote-cart-footer"><a class="quote-cart-secondary" href="<?php echo grinco_url_html('/catalogue'); ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Continuer mes recherches</a><form action="<?php echo grinco_url_html('/panier-devis'); ?>" method="POST"><input type="hidden" name="cart_action" value="clear"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cartCsrfToken, ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" class="quote-cart-clear"><i class="bi bi-trash" aria-hidden="true"></i> Vider la sélection</button></form><?php if (empty($cartData['missing_ids'])): ?><a class="catalogue-add-button" href="<?php echo grinco_url_html('/demande-devis'); ?>">Continuer vers la demande <i class="bi bi-arrow-right" aria-hidden="true"></i></a><?php endif; ?></div></div>
    <?php endif; ?>
  </div></section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
