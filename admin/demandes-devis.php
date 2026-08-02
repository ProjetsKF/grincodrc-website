<?php
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin/quotes-repository.php';
grinco_admin_bootstrap();
grinco_admin_require_authentication();

function quotes_search($value)
{
    return grinco_utf8_substr(grinco_normalize_text(strip_tags((string) $value), false), 0, 100);
}
function quotes_page($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 1;
}
function quotes_pagination_url($page, $search)
{
    $query = array('page' => max(1, (int) $page));
    if ($search !== '') { $query['q'] = $search; }
    return grinco_url_html('/admin/demandes-devis.php?' . http_build_query($query, '', '&'));
}
function quotes_pagination_items($current, $total)
{
    if ($total <= 7) { return range(1, max(1, $total)); }
    $items = array(1);
    if ($current > 4) { $items[] = 'ellipsis-start'; }
    for ($page = max(2, $current - 1); $page <= min($total - 1, $current + 1); $page++) { $items[] = $page; }
    if ($current < $total - 3) { $items[] = 'ellipsis-end'; }
    $items[] = $total;
    return $items;
}

$search = quotes_search(isset($_GET['q']) ? $_GET['q'] : '');
$requestedPage = quotes_page(isset($_GET['page']) ? $_GET['page'] : '1');
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    try {
        header('Content-Type: application/json; charset=UTF-8');
        echo grinco_json_encode(array('success' => true, 'data' => grinco_quotes_fetch_page($search, $requestedPage, 10)));
    } catch (PDOException $exception) {
        http_response_code(500);
        echo grinco_json_encode(array('success' => false, 'message' => 'Les demandes ne peuvent pas être chargées.'));
    }
    exit;
}

$result = array('rows' => array(), 'page' => 1, 'per_page' => 10, 'total' => 0, 'total_pages' => 1, 'offset' => 0);
$loadError = '';
try { $result = grinco_quotes_fetch_page($search, $requestedPage, 10); }
catch (PDOException $exception) { error_log('[GRINCO admin quotes] Unable to load requests.'); $loadError = 'Les demandes ne peuvent pas être chargées pour le moment.'; }

$adminPageTitle = 'Demandes de devis';
$adminPageDescription = 'Consultation des demandes de devis multi-produits reçues par GRINCO RDC.';
$adminCurrentPage = 'demandes-devis';
$logoutCsrfToken = grinco_csrf_token('admin_logout');
$adminPageScripts = array('/assets/js/admin-demandes-devis.js');
include dirname(__DIR__) . '/includes/admin/head.php';
?>
<div class="admin-layout"><?php include dirname(__DIR__) . '/includes/admin/sidebar.php'; ?><div class="admin-main-shell"><?php include dirname(__DIR__) . '/includes/admin/header.php'; ?>
<main class="admin-content" id="admin-main-content" data-quotes-app data-endpoint="<?php echo grinco_url_html('/admin/demandes-devis.php'); ?>" data-detail-base="<?php echo grinco_url_html('/admin/demande-devis.php'); ?>"><div class="admin-page-heading"><span class="admin-eyebrow">Suivi commercial</span><h1>Demandes de devis</h1><p>Consultez les demandes multi-produits transmises depuis le catalogue public.</p></div>
<?php if ($loadError !== ''): ?><div class="admin-module-alert is-error" role="alert"><?php echo grinco_admin_escape($loadError); ?></div><?php endif; ?>
<section class="admin-table-card"><div class="admin-table-toolbar"><div><h2>Demandes reçues</h2><p id="quote-results-summary"><?php echo $result['total'] ? grinco_admin_escape(($result['offset'] + 1) . '–' . min($result['offset'] + 10, $result['total']) . ' sur ' . $result['total']) : 'Aucune demande'; ?></p></div><form class="admin-search-field" action="<?php echo grinco_url_html('/admin/demandes-devis.php'); ?>" method="GET" role="search"><label class="visually-hidden" for="quote-search">Rechercher</label><i class="bi bi-search" aria-hidden="true"></i><input id="quote-search" type="search" name="q" value="<?php echo grinco_admin_escape($search); ?>" placeholder="Client, téléphone, produit…" maxlength="100"><button class="visually-hidden" type="submit">Rechercher</button><span class="admin-search-spinner" aria-hidden="true"></span></form></div><div id="quote-search-status" class="visually-hidden" role="status" aria-live="polite"></div>
<div class="admin-table-responsive"><table class="admin-data-table admin-quotes-table"><thead><tr><th>N°</th><th>Client</th><th>Entreprise</th><th>Téléphone</th><th>E-mail</th><th>Nombre de produits</th><th>Date de demande</th><th class="admin-actions-column">Actions</th></tr></thead><tbody id="quotes-table-body"><?php foreach ($result['rows'] as $index => $quote): ?><tr><td data-label="N°"><?php echo $result['offset'] + $index + 1; ?></td><td data-label="Client"><strong><?php echo grinco_admin_escape($quote['nom']); ?></strong></td><td data-label="Entreprise"><?php echo grinco_admin_escape($quote['entreprise'] === '' ? '—' : $quote['entreprise']); ?></td><td data-label="Téléphone"><?php echo grinco_admin_escape($quote['telephone']); ?></td><td data-label="E-mail"><?php echo grinco_admin_escape($quote['email'] === '' ? '—' : $quote['email']); ?></td><td data-label="Nombre de produits"><span class="admin-count-badge"><?php echo (int) $quote['nombre_produits']; ?></span></td><td data-label="Date de demande"><?php echo grinco_admin_escape($quote['date_formatted']); ?></td><td data-label="Actions" class="admin-actions-cell"><a class="admin-icon-button is-edit" href="<?php echo grinco_url_html('/admin/demande-devis.php?id=' . (int) $quote['id']); ?>" title="Voir les détails" aria-label="Voir les détails de la demande de <?php echo grinco_admin_escape($quote['nom']); ?>"><i class="bi bi-eye" aria-hidden="true"></i></a></td></tr><?php endforeach; ?><?php if (!$result['rows']): ?><tr class="admin-empty-table-row"><td colspan="8"><i class="bi bi-file-earmark-text" aria-hidden="true"></i><strong>Aucune demande trouvée</strong><span>Les nouvelles demandes apparaîtront ici.</span></td></tr><?php endif; ?></tbody></table></div>
<nav class="admin-pagination-wrap" aria-label="Pagination des demandes"><ul class="admin-pagination" id="quotes-pagination"><li class="<?php echo $result['page'] <= 1 ? 'is-disabled' : ''; ?>"><?php if ($result['page'] <= 1): ?><span>Précédent</span><?php else: ?><a data-page="<?php echo $result['page'] - 1; ?>" href="<?php echo quotes_pagination_url($result['page'] - 1, $search); ?>">Précédent</a><?php endif; ?></li><?php foreach (quotes_pagination_items($result['page'], $result['total_pages']) as $item): ?><?php if (is_int($item)): ?><li class="<?php echo $item === $result['page'] ? 'is-active' : ''; ?>"><a data-page="<?php echo $item; ?>" href="<?php echo quotes_pagination_url($item, $search); ?>"<?php echo $item === $result['page'] ? ' aria-current="page"' : ''; ?>><?php echo $item; ?></a></li><?php else: ?><li class="is-ellipsis"><span>…</span></li><?php endif; ?><?php endforeach; ?><li class="<?php echo $result['page'] >= $result['total_pages'] ? 'is-disabled' : ''; ?>"><?php if ($result['page'] >= $result['total_pages']): ?><span>Suivant</span><?php else: ?><a data-page="<?php echo $result['page'] + 1; ?>" href="<?php echo quotes_pagination_url($result['page'] + 1, $search); ?>">Suivant</a><?php endif; ?></li></ul></nav></section></main>
<?php include dirname(__DIR__) . '/includes/admin/footer.php'; ?></div></div><?php include dirname(__DIR__) . '/includes/admin/scripts.php'; ?>
