<?php
require_once __DIR__ . '/devis-panier.php';
grinco_apply_form_security_headers();
grinco_start_secure_session();

$catalogueRequestedPage = isset($_GET['page']) && ctype_digit((string) $_GET['page']) && (int) $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$catalogueResult = array('rows' => array(), 'page' => 1, 'total_pages' => 1, 'total' => 0);
$catalogueError = '';
$catalogueCategory = null;
try {
    $catalogueCategory = grinco_catalogue_public_category_by_name($catalogueCategoryName);
    if ($catalogueCategory) {
        $catalogueResult = grinco_catalogue_public_page($catalogueRequestedPage, 12, '', $catalogueCategory['id']);
    }
} catch (PDOException $exception) {
    error_log('[GRINCO public category] Unable to load category products.');
    $catalogueError = 'Le catalogue ne peut pas être chargé pour le moment.';
}
$catalogueSearch = '';
$catalogueShowSearch = false;
$catalogueEmptyTitle = 'Aucun produit disponible';
