<?php
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin/products-repository.php';

grinco_admin_bootstrap();
grinco_admin_require_authentication();

function produits_request_value($source, $key, $default)
{
    return isset($source[$key]) && is_scalar($source[$key]) ? (string) $source[$key] : $default;
}

function produits_search_term($value)
{
    return grinco_utf8_substr(grinco_normalize_text(strip_tags((string) $value), false), 0, 100);
}

function produits_page_number($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 1;
}

function produits_record_id($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 0;
}

function produits_parse_price($value)
{
    $value = str_replace(array(' ', "\xc2\xa0", ','), array('', '', '.'), trim((string) $value));
    if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', $value)) {
        return array('valid' => false, 'value' => '');
    }

    $parts = explode('.', $value, 2);
    $integer = ltrim($parts[0], '0');
    $integer = $integer === '' ? '0' : $integer;
    $decimal = isset($parts[1]) ? str_pad($parts[1], 2, '0') : '00';
    return array('valid' => true, 'value' => $integer . '.' . $decimal);
}

function produits_validate_input($source)
{
    $errors = array();
    $categoryId = produits_record_id(produits_request_value($source, 'categorie_id', '0'));
    $brandId = produits_record_id(produits_request_value($source, 'marque_id', '0'));
    $reference = grinco_normalize_text(strip_tags(produits_request_value($source, 'reference', '')), false);
    $name = grinco_normalize_text(strip_tags(produits_request_value($source, 'nom', '')), false);
    $model = grinco_normalize_text(strip_tags(produits_request_value($source, 'modele', '')), false);
    $description = grinco_normalize_text(strip_tags(produits_request_value($source, 'description', '')), true);
    $price = produits_parse_price(produits_request_value($source, 'prix', ''));

    if ($categoryId <= 0 || !grinco_products_category_exists($categoryId)) {
        $errors[] = 'La catégorie sélectionnée n’est pas valide.';
    }
    if ($brandId <= 0 || !grinco_products_brand_exists($brandId)) {
        $errors[] = 'La marque sélectionnée n’est pas valide.';
    }
    if ($reference === '') {
        $errors[] = 'La référence est obligatoire.';
    } elseif (grinco_utf8_length($reference) > 50) {
        $errors[] = 'La référence ne peut pas dépasser 50 caractères.';
    }
    if ($name === '') {
        $errors[] = 'Le nom du produit est obligatoire.';
    } elseif (grinco_utf8_length($name) > 150) {
        $errors[] = 'Le nom du produit ne peut pas dépasser 150 caractères.';
    }
    if (grinco_utf8_length($model) > 100) {
        $errors[] = 'Le modèle ne peut pas dépasser 100 caractères.';
    }
    if (!$price['valid']) {
        $errors[] = 'Le prix doit être un montant USD valide avec deux décimales au maximum.';
    }
    if (grinco_utf8_length($description) > 10000) {
        $errors[] = 'La description ne peut pas dépasser 10 000 caractères.';
    }

    return array(
        'valid' => empty($errors),
        'errors' => $errors,
        'category_id' => $categoryId,
        'brand_id' => $brandId,
        'reference' => $reference,
        'name' => $name,
        'model' => $model,
        'price' => $price['value'],
        'description' => $description
    );
}

function produits_set_flash($type, $message, $mediaProductId = 0)
{
    $_SESSION['admin_products_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
        'media_product_id' => (int) $mediaProductId
    );
}

function produits_redirect($search, $page)
{
    $query = array();
    if ($search !== '') {
        $query['q'] = $search;
    }
    if ($page > 1) {
        $query['page'] = $page;
    }
    $location = grinco_url('/admin/produits.php') . (empty($query) ? '' : '?' . http_build_query($query, '', '&'));
    header('Location: ' . $location);
    exit;
}

function produits_pagination_url($page, $search)
{
    $query = array('page' => max(1, (int) $page));
    if ($search !== '') {
        $query['q'] = $search;
    }
    return htmlspecialchars(grinco_url('/admin/produits.php') . '?' . http_build_query($query, '', '&'), ENT_QUOTES, 'UTF-8');
}

function produits_pagination_items($currentPage, $totalPages)
{
    if ($totalPages <= 7) {
        return range(1, max(1, $totalPages));
    }
    $items = array(1);
    if ($currentPage > 4) {
        $items[] = 'ellipsis-start';
    }
    for ($page = max(2, $currentPage - 1); $page <= min($totalPages - 1, $currentPage + 1); $page++) {
        $items[] = $page;
    }
    if ($currentPage < $totalPages - 3) {
        $items[] = 'ellipsis-end';
    }
    $items[] = $totalPages;
    return $items;
}

$perPage = 10;
$search = produits_search_term(produits_request_value($_GET, 'q', ''));
$requestedPage = produits_page_number(produits_request_value($_GET, 'page', '1'));

if (produits_request_value($_GET, 'ajax', '') === '1') {
    try {
        header('Content-Type: application/json; charset=UTF-8');
        echo grinco_json_encode(array(
            'success' => true,
            'data' => grinco_products_fetch_page($search, $requestedPage, $perPage)
        ));
    } catch (PDOException $exception) {
        error_log('[GRINCO admin products] Unable to load AJAX list.');
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo grinco_json_encode(array('success' => false, 'message' => 'Les produits ne peuvent pas être chargés.'));
    }
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = produits_request_value($_POST, 'product_action', '');
    $csrfToken = produits_request_value($_POST, 'csrf_token', '');
    $returnSearch = produits_search_term(produits_request_value($_POST, 'return_search', ''));
    $returnPage = produits_page_number(produits_request_value($_POST, 'return_page', '1'));

    if (!grinco_validate_csrf_token('admin_products', $csrfToken)) {
        produits_set_flash('error', 'Votre session a expiré. Veuillez réessayer.', 0);
        grinco_regenerate_csrf_token('admin_products');
        produits_redirect($returnSearch, $returnPage);
    }
    if (!grinco_validate_request_origin()) {
        produits_set_flash('error', 'La demande n’a pas pu être vérifiée.', 0);
        grinco_regenerate_csrf_token('admin_products');
        produits_redirect($returnSearch, $returnPage);
    }

    try {
        if ($action === 'create' || $action === 'update') {
            $productId = produits_record_id(produits_request_value($_POST, 'product_id', '0'));
            if ($action === 'create') {
                $productId = 0;
            }
            $validation = produits_validate_input($_POST);

            if (!$validation['valid']) {
                produits_set_flash('error', implode(' ', $validation['errors']));
            } elseif ($action === 'update' && ($productId <= 0 || !grinco_products_exists($productId))) {
                produits_set_flash('error', 'Le produit demandé est introuvable.');
            } elseif (grinco_products_reference_exists($validation['reference'], $productId)) {
                produits_set_flash('error', 'Un produit portant cette référence existe déjà.');
            } elseif ($action === 'create') {
                $statement = grinco_database()->prepare(
                    'INSERT INTO produits '
                    . '(categorie_id, marque_id, administrateur_id, reference, nom, modele, prix, description) '
                    . 'VALUES (:category_id, :brand_id, :admin_id, :reference, :name, :model, :price, :description)'
                );
                $statement->execute(array(
                    ':category_id' => $validation['category_id'],
                    ':brand_id' => $validation['brand_id'],
                    ':admin_id' => (int) $_SESSION['grinco_admin_id'],
                    ':reference' => $validation['reference'],
                    ':name' => $validation['name'],
                    ':model' => $validation['model'] === '' ? null : $validation['model'],
                    ':price' => $validation['price'],
                    ':description' => $validation['description'] === '' ? null : $validation['description']
                ));
                produits_set_flash(
                    'success',
                    'Le produit a été ajouté avec succès. Vous pouvez maintenant ajouter ses images et documents.',
                    (int) grinco_database()->lastInsertId()
                );
            } else {
                $statement = grinco_database()->prepare(
                    'UPDATE produits SET categorie_id = :category_id, marque_id = :brand_id, '
                    . 'reference = :reference, nom = :name, modele = :model, prix = :price, description = :description '
                    . 'WHERE id = :id'
                );
                $statement->execute(array(
                    ':category_id' => $validation['category_id'],
                    ':brand_id' => $validation['brand_id'],
                    ':reference' => $validation['reference'],
                    ':name' => $validation['name'],
                    ':model' => $validation['model'] === '' ? null : $validation['model'],
                    ':price' => $validation['price'],
                    ':description' => $validation['description'] === '' ? null : $validation['description'],
                    ':id' => $productId
                ));
                produits_set_flash('success', 'Le produit a été modifié avec succès.');
            }
        } elseif ($action === 'delete') {
            $productId = produits_record_id(produits_request_value($_POST, 'product_id', '0'));
            if ($productId <= 0 || !grinco_products_exists($productId)) {
                produits_set_flash('error', 'Le produit demandé est introuvable.');
            } else {
                $dependencies = grinco_products_dependencies($productId);
                $blockedBy = array();
                if ($dependencies['images'] > 0) {
                    $blockedBy[] = 'des images';
                }
                if ($dependencies['documents'] > 0) {
                    $blockedBy[] = 'des documents';
                }
                if ($dependencies['quotes'] > 0) {
                    $blockedBy[] = 'une ou plusieurs demandes de devis';
                }

                if (!empty($blockedBy)) {
                    produits_set_flash(
                        'error',
                        'Ce produit est lié à ' . implode(', ', $blockedBy) . ' et ne peut pas être supprimé.'
                    );
                } else {
                    $statement = grinco_database()->prepare('DELETE FROM produits WHERE id = :id');
                    $statement->execute(array(':id' => $productId));
                    produits_set_flash('success', 'Le produit a été supprimé avec succès.');
                }
            }
        } else {
            produits_set_flash('error', 'L’action demandée n’est pas valide.');
        }
    } catch (PDOException $exception) {
        error_log('[GRINCO admin products] Database operation failed.');
        if ((string) $exception->getCode() === '23000' && ($action === 'create' || $action === 'update')) {
            produits_set_flash('error', 'La référence existe déjà ou une relation sélectionnée n’est plus disponible.');
        } elseif ((string) $exception->getCode() === '23000' && $action === 'delete') {
            produits_set_flash('error', 'Ce produit est utilisé et ne peut pas être supprimé.');
        } else {
            produits_set_flash('error', 'L’opération ne peut pas être effectuée pour le moment.');
        }
    }

    grinco_regenerate_csrf_token('admin_products');
    produits_redirect($returnSearch, $returnPage);
}

$productCsrfToken = grinco_csrf_token('admin_products');
$productFlash = isset($_SESSION['admin_products_flash']) && is_array($_SESSION['admin_products_flash'])
    ? $_SESSION['admin_products_flash']
    : null;
unset($_SESSION['admin_products_flash']);

$productResult = array('rows' => array(), 'page' => 1, 'per_page' => 10, 'total' => 0, 'total_pages' => 1, 'offset' => 0);
$categoryOptions = array();
$brandOptions = array();
$productLoadError = '';
$productHasCreationDate = false;
try {
    $productResult = grinco_products_fetch_page($search, $requestedPage, $perPage);
    $categoryOptions = grinco_products_category_options();
    $brandOptions = grinco_products_brand_options();
    $productHasCreationDate = grinco_products_has_creation_date();
} catch (PDOException $exception) {
    error_log('[GRINCO admin products] Unable to load page data.');
    $productLoadError = 'Les données des produits ne peuvent pas être chargées pour le moment.';
}

$adminPageTitle = 'Gestion des produits';
$adminPageDescription = 'Gestion des informations des produits du catalogue GRINCO RDC.';
$adminCurrentPage = 'produits';
$logoutCsrfToken = grinco_csrf_token('admin_logout');
$adminPageScripts = array('/assets/js/admin-produits.js');

include dirname(__DIR__) . '/includes/admin/head.php';
?>

<div class="admin-layout">
  <?php include dirname(__DIR__) . '/includes/admin/sidebar.php'; ?>
  <div class="admin-main-shell">
    <?php include dirname(__DIR__) . '/includes/admin/header.php'; ?>

    <main class="admin-content" id="admin-main-content" data-products-app data-endpoint="<?php echo grinco_url_html('/admin/produits.php'); ?>">
      <div class="admin-page-heading admin-page-heading-with-action">
        <div><span class="admin-eyebrow">Catalogue</span><h1>Gestion des produits</h1><p>Gérez les informations commerciales des produits du catalogue GRINCO RDC.</p></div>
        <button type="button" class="admin-primary-button" data-bs-toggle="modal" data-bs-target="#add-product-modal"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Ajouter un produit</span></button>
      </div>

      <?php if ($productFlash): ?>
        <div class="admin-module-alert is-<?php echo grinco_admin_escape($productFlash['type']); ?>" role="alert"><i class="bi <?php echo $productFlash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>" aria-hidden="true"></i><span><?php echo grinco_admin_escape($productFlash['message']); ?></span><?php if (!empty($productFlash['media_product_id'])): ?><a class="admin-alert-action" href="<?php echo grinco_url_html('/admin/images-documents.php?produit_id=' . (int) $productFlash['media_product_id']); ?>">Ajouter les médias <i class="bi bi-arrow-right" aria-hidden="true"></i></a><?php endif; ?></div>
      <?php endif; ?>
      <?php if ($productLoadError !== ''): ?>
        <div class="admin-module-alert is-error" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span><?php echo grinco_admin_escape($productLoadError); ?></span></div>
      <?php endif; ?>
      <?php if ($productLoadError === '' && !$productHasCreationDate): ?>
        <div class="admin-module-alert is-info" role="status"><i class="bi bi-info-circle" aria-hidden="true"></i><span>La table produits ne contient pas de date de création. Cette information est indiquée comme non disponible.</span></div>
      <?php endif; ?>

      <section class="admin-table-card" aria-labelledby="products-table-title">
        <div class="admin-table-toolbar">
          <div><h2 id="products-table-title">Liste des produits</h2><p id="product-results-summary"><?php if ($productResult['total'] > 0): ?><?php echo grinco_admin_escape(($productResult['offset'] + 1) . '–' . min($productResult['offset'] + $perPage, $productResult['total']) . ' sur ' . $productResult['total']); ?><?php else: ?>Aucun produit<?php endif; ?></p></div>
          <form class="admin-search-field" action="<?php echo grinco_url_html('/admin/produits.php'); ?>" method="GET" role="search">
            <label for="product-search" class="visually-hidden">Rechercher un produit</label><i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="product-search" name="q" value="<?php echo grinco_admin_escape($search); ?>" placeholder="Référence, produit, modèle…" maxlength="100" autocomplete="off"><button type="submit" class="visually-hidden">Rechercher</button><span class="admin-search-spinner" aria-hidden="true"></span>
          </form>
        </div>
        <div id="product-search-status" class="visually-hidden" role="status" aria-live="polite"></div>

        <div class="admin-table-responsive">
          <table class="admin-data-table admin-products-table">
            <thead><tr><th scope="col">N°</th><th scope="col">Référence</th><th scope="col">Produit</th><th scope="col">Catégorie</th><th scope="col">Marque</th><th scope="col">Prix (USD / CNY)</th><th scope="col">Administrateur</th><th scope="col">Date de création</th><th scope="col" class="admin-actions-column">Actions</th></tr></thead>
            <tbody id="products-table-body">
              <?php foreach ($productResult['rows'] as $index => $product): ?>
                <tr>
                  <td data-label="N°"><?php echo grinco_admin_escape($productResult['offset'] + $index + 1); ?></td>
                  <td data-label="Référence"><span class="admin-reference-value"><?php echo grinco_admin_escape($product['reference']); ?></span></td>
                  <td data-label="Produit"><span class="admin-product-summary"><?php if ($product['image_url'] !== ''): ?><img class="admin-product-thumbnail" src="<?php echo grinco_admin_escape($product['image_url']); ?>" alt="Image principale de <?php echo grinco_admin_escape($product['nom']); ?>" loading="lazy"><?php else: ?><span class="admin-product-thumbnail is-empty" aria-hidden="true"><i class="bi bi-image"></i></span><?php endif; ?><span class="admin-product-name"><strong><?php echo grinco_admin_escape($product['nom']); ?></strong><?php if ($product['modele'] !== ''): ?><small><?php echo grinco_admin_escape($product['modele']); ?></small><?php endif; ?></span></span></td>
                  <td data-label="Catégorie"><?php echo grinco_admin_escape($product['categorie_nom']); ?></td>
                  <td data-label="Marque"><?php echo grinco_admin_escape($product['marque_nom']); ?></td>
                  <td data-label="Prix (USD / CNY)"><span class="admin-price-stack"><strong class="admin-price-value"><?php echo grinco_admin_escape($product['prix_formatted']); ?></strong><small class="<?php echo $product['prix_cny_formatted'] === 'Taux non configuré' ? 'is-missing' : ''; ?>"><?php echo grinco_admin_escape($product['prix_cny_formatted']); ?></small></span></td>
                  <td data-label="Administrateur"><?php echo grinco_admin_escape($product['administrateur_nom']); ?></td>
                  <td data-label="Date de création"><?php echo grinco_admin_escape($product['date_creation_formatted']); ?></td>
                  <td data-label="Actions" class="admin-actions-cell">
                    <button type="button" class="admin-icon-button is-edit" title="Modifier le produit" aria-label="Modifier le produit <?php echo grinco_admin_escape($product['nom']); ?>" data-product-edit data-product-id="<?php echo grinco_admin_escape($product['id']); ?>" data-product-category="<?php echo grinco_admin_escape($product['categorie_id']); ?>" data-product-brand="<?php echo grinco_admin_escape($product['marque_id']); ?>" data-product-reference="<?php echo grinco_admin_escape($product['reference']); ?>" data-product-name="<?php echo grinco_admin_escape($product['nom']); ?>" data-product-model="<?php echo grinco_admin_escape($product['modele']); ?>" data-product-price="<?php echo grinco_admin_escape($product['prix']); ?>" data-product-description="<?php echo grinco_admin_escape($product['description']); ?>" data-bs-toggle="modal" data-bs-target="#edit-product-modal"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                    <button type="button" class="admin-icon-button is-delete" title="Supprimer le produit" aria-label="Supprimer le produit <?php echo grinco_admin_escape($product['nom']); ?>" data-product-delete data-product-id="<?php echo grinco_admin_escape($product['id']); ?>" data-product-name="<?php echo grinco_admin_escape($product['nom']); ?>" data-bs-toggle="modal" data-bs-target="#delete-product-modal"><i class="bi bi-trash" aria-hidden="true"></i></button>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($productResult['rows'])): ?><tr class="admin-empty-table-row"><td colspan="9"><i class="bi bi-box-seam" aria-hidden="true"></i><strong>Aucun produit trouvé</strong><span>Ajoutez un produit ou modifiez votre recherche.</span></td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <nav class="admin-pagination-wrap" aria-label="Pagination des produits"><ul class="admin-pagination" id="products-pagination">
          <li class="<?php echo $productResult['page'] <= 1 ? 'is-disabled' : ''; ?>"><?php if ($productResult['page'] <= 1): ?><span><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="admin-pagination-text">Précédent</span></span><?php else: ?><a href="<?php echo produits_pagination_url($productResult['page'] - 1, $search); ?>" data-page="<?php echo $productResult['page'] - 1; ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="admin-pagination-text">Précédent</span></a><?php endif; ?></li>
          <?php foreach (produits_pagination_items($productResult['page'], $productResult['total_pages']) as $paginationItem): ?><?php if (is_int($paginationItem)): ?><li class="<?php echo $paginationItem === $productResult['page'] ? 'is-active' : ''; ?>"><a href="<?php echo produits_pagination_url($paginationItem, $search); ?>" data-page="<?php echo $paginationItem; ?>" <?php echo $paginationItem === $productResult['page'] ? 'aria-current="page"' : ''; ?>><?php echo $paginationItem; ?></a></li><?php else: ?><li class="is-ellipsis"><span aria-hidden="true">…</span></li><?php endif; ?><?php endforeach; ?>
          <li class="<?php echo $productResult['page'] >= $productResult['total_pages'] ? 'is-disabled' : ''; ?>"><?php if ($productResult['page'] >= $productResult['total_pages']): ?><span><span class="admin-pagination-text">Suivant</span><i class="bi bi-chevron-right" aria-hidden="true"></i></span><?php else: ?><a href="<?php echo produits_pagination_url($productResult['page'] + 1, $search); ?>" data-page="<?php echo $productResult['page'] + 1; ?>"><span class="admin-pagination-text">Suivant</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a><?php endif; ?></li>
        </ul></nav>
      </section>
    </main>
    <?php include dirname(__DIR__) . '/includes/admin/footer.php'; ?>
  </div>
</div>

<?php
function produits_render_select_options($options, $type)
{
    if (empty($options)) {
        echo '<option value="" disabled>Aucune ' . ($type === 'category' ? 'catégorie' : 'marque') . ' disponible</option>';
        return;
    }
    foreach ($options as $option) {
        $label = $option['nom'];
        if ($type === 'category' && isset($option['statut']) && $option['statut'] === 'Inactif') {
            $label .= ' (Inactif)';
        }
        echo '<option value="' . grinco_admin_escape($option['id']) . '">' . grinco_admin_escape($label) . '</option>';
    }
}
?>

<div class="modal fade admin-modal" id="add-product-modal" tabindex="-1" aria-labelledby="add-product-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/produits.php'); ?>" method="POST">
  <div class="modal-header"><div><span class="admin-modal-eyebrow">Nouvelle entrée</span><h2 class="modal-title" id="add-product-title">Ajouter un produit</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
  <div class="modal-body"><input type="hidden" name="product_action" value="create"><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($productCsrfToken); ?>"><input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>"><input type="hidden" name="return_page" value="<?php echo grinco_admin_escape($productResult['page']); ?>">
    <div class="admin-form-grid"><div class="admin-form-field"><label for="add-product-category">Catégorie <span aria-hidden="true">*</span></label><select id="add-product-category" name="categorie_id" required><option value="" selected disabled>Sélectionner</option><?php produits_render_select_options($categoryOptions, 'category'); ?></select></div><div class="admin-form-field"><label for="add-product-brand">Marque <span aria-hidden="true">*</span></label><select id="add-product-brand" name="marque_id" required><option value="" selected disabled>Sélectionner</option><?php produits_render_select_options($brandOptions, 'brand'); ?></select></div></div>
    <div class="admin-form-grid"><div class="admin-form-field"><label for="add-product-reference">Référence <span aria-hidden="true">*</span></label><input type="text" id="add-product-reference" name="reference" maxlength="50" required></div><div class="admin-form-field"><label for="add-product-name">Nom <span aria-hidden="true">*</span></label><input type="text" id="add-product-name" name="nom" maxlength="150" required></div></div>
    <div class="admin-form-grid"><div class="admin-form-field"><label for="add-product-model">Modèle</label><input type="text" id="add-product-model" name="modele" maxlength="100"></div><div class="admin-form-field"><label for="add-product-price">Prix (USD) <span aria-hidden="true">*</span></label><input type="text" id="add-product-price" name="prix" inputmode="decimal" placeholder="18 900,00" required></div></div>
    <div class="admin-form-field"><label for="add-product-description">Description</label><textarea id="add-product-description" name="description" rows="5" maxlength="10000"></textarea></div>
  </div><div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-primary-button">Ajouter le produit</button></div>
</form></div></div></div>

<div class="modal fade admin-modal" id="edit-product-modal" tabindex="-1" aria-labelledby="edit-product-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/produits.php'); ?>" method="POST">
  <div class="modal-header"><div><span class="admin-modal-eyebrow">Mise à jour</span><h2 class="modal-title" id="edit-product-title">Modifier le produit</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
  <div class="modal-body"><input type="hidden" name="product_action" value="update"><input type="hidden" name="product_id" id="edit-product-id" value=""><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($productCsrfToken); ?>"><input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>"><input type="hidden" name="return_page" value="<?php echo grinco_admin_escape($productResult['page']); ?>">
    <div class="admin-form-grid"><div class="admin-form-field"><label for="edit-product-category">Catégorie <span aria-hidden="true">*</span></label><select id="edit-product-category" name="categorie_id" required><?php produits_render_select_options($categoryOptions, 'category'); ?></select></div><div class="admin-form-field"><label for="edit-product-brand">Marque <span aria-hidden="true">*</span></label><select id="edit-product-brand" name="marque_id" required><?php produits_render_select_options($brandOptions, 'brand'); ?></select></div></div>
    <div class="admin-form-grid"><div class="admin-form-field"><label for="edit-product-reference">Référence <span aria-hidden="true">*</span></label><input type="text" id="edit-product-reference" name="reference" maxlength="50" required></div><div class="admin-form-field"><label for="edit-product-name">Nom <span aria-hidden="true">*</span></label><input type="text" id="edit-product-name" name="nom" maxlength="150" required></div></div>
    <div class="admin-form-grid"><div class="admin-form-field"><label for="edit-product-model">Modèle</label><input type="text" id="edit-product-model" name="modele" maxlength="100"></div><div class="admin-form-field"><label for="edit-product-price">Prix (USD) <span aria-hidden="true">*</span></label><input type="text" id="edit-product-price" name="prix" inputmode="decimal" required></div></div>
    <div class="admin-form-field"><label for="edit-product-description">Description</label><textarea id="edit-product-description" name="description" rows="5" maxlength="10000"></textarea></div>
  </div><div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-primary-button">Enregistrer les modifications</button></div>
</form></div></div></div>

<div class="modal fade admin-modal" id="delete-product-modal" tabindex="-1" aria-labelledby="delete-product-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/produits.php'); ?>" method="POST">
  <div class="modal-header"><h2 class="modal-title" id="delete-product-title">Supprimer le produit</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
  <div class="modal-body admin-delete-confirmation"><input type="hidden" name="product_action" value="delete"><input type="hidden" name="product_id" id="delete-product-id" value=""><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($productCsrfToken); ?>"><input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>"><input type="hidden" name="return_page" value="<?php echo grinco_admin_escape($productResult['page']); ?>"><span class="admin-delete-icon" aria-hidden="true"><i class="bi bi-trash"></i></span><p>Confirmez-vous la suppression de <strong id="delete-product-name"></strong> ?</p><small>La suppression sera refusée si des images, documents ou demandes de devis utilisent ce produit.</small></div>
  <div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-danger-button">Supprimer</button></div>
</form></div></div></div>

<?php include dirname(__DIR__) . '/includes/admin/scripts.php'; ?>
