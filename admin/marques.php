<?php
require_once dirname(__DIR__) . '/includes/admin-auth.php';

grinco_admin_bootstrap();
grinco_admin_require_authentication();

function marques_request_value($source, $key, $default)
{
    return isset($source[$key]) && is_scalar($source[$key])
        ? (string) $source[$key]
        : $default;
}

function marques_search_term($value)
{
    $value = grinco_normalize_text(strip_tags((string) $value), false);
    return grinco_utf8_substr($value, 0, 100);
}

function marques_page_number($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 1;
}

function marques_record_id($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 0;
}

function marques_fetch_page($search, $requestedPage, $perPage)
{
    $connection = grinco_database();
    $where = '';
    $parameters = array();

    if ($search !== '') {
        $where = ' WHERE nom LIKE :search_name OR COALESCE(description, \'\') LIKE :search_description';
        $parameters[':search_name'] = '%' . $search . '%';
        $parameters[':search_description'] = '%' . $search . '%';
    }

    $countStatement = $connection->prepare('SELECT COUNT(*) FROM marques' . $where);
    $countStatement->execute($parameters);
    $total = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, (int) $requestedPage), $totalPages);
    $offset = ($page - 1) * $perPage;

    $listStatement = $connection->prepare(
        'SELECT id, nom, description FROM marques'
        . $where
        . ' ORDER BY id DESC LIMIT :limit OFFSET :offset'
    );

    if ($search !== '') {
        $listStatement->bindValue(':search_name', '%' . $search . '%', PDO::PARAM_STR);
        $listStatement->bindValue(':search_description', '%' . $search . '%', PDO::PARAM_STR);
    }
    $listStatement->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $listStatement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $listStatement->execute();

    $rows = $listStatement->fetchAll();
    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['description'] = $row['description'] === null ? '' : (string) $row['description'];
    }
    unset($row);

    return array(
        'rows' => $rows,
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => $offset
    );
}

function marques_set_flash($type, $message)
{
    $_SESSION['admin_marques_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message
    );
}

function marques_redirect($search, $page)
{
    $query = array();
    if ($search !== '') {
        $query['q'] = $search;
    }
    if ($page > 1) {
        $query['page'] = $page;
    }

    $location = grinco_url('/admin/marques.php');
    if (!empty($query)) {
        $location .= '?' . http_build_query($query, '', '&');
    }

    header('Location: ' . $location);
    exit;
}

function marques_validate_input($name, $description)
{
    $errors = array();
    $name = grinco_normalize_text(strip_tags($name), false);
    $description = grinco_normalize_text(strip_tags($description), true);

    if ($name === '') {
        $errors[] = 'Le nom de la marque est obligatoire.';
    } elseif (grinco_utf8_length($name) > 100) {
        $errors[] = 'Le nom de la marque ne peut pas dépasser 100 caractères.';
    }

    if (grinco_utf8_length($description) > 5000) {
        $errors[] = 'La description ne peut pas dépasser 5 000 caractères.';
    }

    return array(
        'valid' => empty($errors),
        'errors' => $errors,
        'name' => $name,
        'description' => $description
    );
}

function marques_name_exists($name, $excludedId)
{
    $sql = 'SELECT id FROM marques WHERE nom = :nom';
    $parameters = array(':nom' => $name);

    if ($excludedId > 0) {
        $sql .= ' AND id <> :id';
        $parameters[':id'] = $excludedId;
    }

    $sql .= ' LIMIT 1';
    $statement = grinco_database()->prepare($sql);
    $statement->execute($parameters);
    return (bool) $statement->fetchColumn();
}

function marques_pagination_url($page, $search)
{
    $query = array('page' => max(1, (int) $page));
    if ($search !== '') {
        $query['q'] = $search;
    }

    return htmlspecialchars(
        grinco_url('/admin/marques.php') . '?' . http_build_query($query, '', '&'),
        ENT_QUOTES,
        'UTF-8'
    );
}

function marques_pagination_items($currentPage, $totalPages)
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
$search = marques_search_term(marques_request_value($_GET, 'q', ''));
$requestedPage = marques_page_number(marques_request_value($_GET, 'page', '1'));

if (marques_request_value($_GET, 'ajax', '') === '1') {
    try {
        $ajaxResult = marques_fetch_page($search, $requestedPage, $perPage);
        header('Content-Type: application/json; charset=UTF-8');
        echo grinco_json_encode(array('success' => true, 'data' => $ajaxResult));
    } catch (PDOException $exception) {
        error_log('[GRINCO admin marques] Unable to load AJAX list.');
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo grinco_json_encode(array(
            'success' => false,
            'message' => 'Les marques ne peuvent pas être chargées pour le moment.'
        ));
    }
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = marques_request_value($_POST, 'brand_action', '');
    $csrfToken = marques_request_value($_POST, 'csrf_token', '');
    $returnSearch = marques_search_term(marques_request_value($_POST, 'return_search', ''));
    $returnPage = marques_page_number(marques_request_value($_POST, 'return_page', '1'));

    if (!grinco_validate_csrf_token('admin_marques', $csrfToken)) {
        marques_set_flash('error', 'Votre session a expiré. Veuillez réessayer.');
        grinco_regenerate_csrf_token('admin_marques');
        marques_redirect($returnSearch, $returnPage);
    }

    if (!grinco_validate_request_origin()) {
        marques_set_flash('error', 'La demande n’a pas pu être vérifiée.');
        grinco_regenerate_csrf_token('admin_marques');
        marques_redirect($returnSearch, $returnPage);
    }

    try {
        if ($action === 'create' || $action === 'update') {
            $brandId = marques_record_id(marques_request_value($_POST, 'brand_id', '0'));
            if ($action === 'create') {
                $brandId = 0;
            }

            $validation = marques_validate_input(
                marques_request_value($_POST, 'nom', ''),
                marques_request_value($_POST, 'description', '')
            );

            if (!$validation['valid']) {
                marques_set_flash('error', implode(' ', $validation['errors']));
            } elseif ($action === 'update' && $brandId <= 0) {
                marques_set_flash('error', 'La marque sélectionnée n’est pas valide.');
            } elseif (marques_name_exists($validation['name'], $brandId)) {
                marques_set_flash('error', 'Une marque portant ce nom existe déjà.');
            } elseif ($action === 'create') {
                $statement = grinco_database()->prepare(
                    'INSERT INTO marques (nom, description) VALUES (:nom, :description)'
                );
                $statement->execute(array(
                    ':nom' => $validation['name'],
                    ':description' => $validation['description'] === '' ? null : $validation['description']
                ));
                marques_set_flash('success', 'La marque a été ajoutée avec succès.');
            } else {
                $existsStatement = grinco_database()->prepare('SELECT id FROM marques WHERE id = :id LIMIT 1');
                $existsStatement->execute(array(':id' => $brandId));

                if (!$existsStatement->fetchColumn()) {
                    marques_set_flash('error', 'La marque demandée est introuvable.');
                } else {
                    $statement = grinco_database()->prepare(
                        'UPDATE marques SET nom = :nom, description = :description WHERE id = :id'
                    );
                    $statement->execute(array(
                        ':nom' => $validation['name'],
                        ':description' => $validation['description'] === '' ? null : $validation['description'],
                        ':id' => $brandId
                    ));
                    marques_set_flash('success', 'La marque a été modifiée avec succès.');
                }
            }
        } elseif ($action === 'delete') {
            $brandId = marques_record_id(marques_request_value($_POST, 'brand_id', '0'));

            if ($brandId <= 0) {
                marques_set_flash('error', 'La marque sélectionnée n’est pas valide.');
            } else {
                $brandStatement = grinco_database()->prepare('SELECT id FROM marques WHERE id = :id LIMIT 1');
                $brandStatement->execute(array(':id' => $brandId));

                if (!$brandStatement->fetchColumn()) {
                    marques_set_flash('error', 'La marque demandée est introuvable.');
                } else {
                    $usageStatement = grinco_database()->prepare(
                        'SELECT COUNT(*) FROM produits WHERE marque_id = :brand_id'
                    );
                    $usageStatement->execute(array(':brand_id' => $brandId));

                    if ((int) $usageStatement->fetchColumn() > 0) {
                        marques_set_flash(
                            'error',
                            'Cette marque est utilisée par un ou plusieurs produits et ne peut pas être supprimée.'
                        );
                    } else {
                        $deleteStatement = grinco_database()->prepare('DELETE FROM marques WHERE id = :id');
                        $deleteStatement->execute(array(':id' => $brandId));
                        marques_set_flash('success', 'La marque a été supprimée avec succès.');
                    }
                }
            }
        } else {
            marques_set_flash('error', 'L’action demandée n’est pas valide.');
        }
    } catch (PDOException $exception) {
        error_log('[GRINCO admin marques] Database operation failed.');
        if ((string) $exception->getCode() === '23000' && ($action === 'create' || $action === 'update')) {
            marques_set_flash('error', 'Une marque portant ce nom existe déjà.');
        } elseif ((string) $exception->getCode() === '23000' && $action === 'delete') {
            marques_set_flash('error', 'Cette marque est utilisée et ne peut pas être supprimée.');
        } else {
            marques_set_flash('error', 'L’opération ne peut pas être effectuée pour le moment.');
        }
    }

    grinco_regenerate_csrf_token('admin_marques');
    marques_redirect($returnSearch, $returnPage);
}

$brandCsrfToken = grinco_csrf_token('admin_marques');
$brandFlash = isset($_SESSION['admin_marques_flash']) && is_array($_SESSION['admin_marques_flash'])
    ? $_SESSION['admin_marques_flash']
    : null;
unset($_SESSION['admin_marques_flash']);

$brandResult = array(
    'rows' => array(),
    'page' => 1,
    'per_page' => $perPage,
    'total' => 0,
    'total_pages' => 1,
    'offset' => 0
);
$brandLoadError = '';

try {
    $brandResult = marques_fetch_page($search, $requestedPage, $perPage);
} catch (PDOException $exception) {
    error_log('[GRINCO admin marques] Unable to load initial list.');
    $brandLoadError = 'Les marques ne peuvent pas être chargées pour le moment.';
}

$adminPageTitle = 'Gestion des marques';
$adminPageDescription = 'Ajout, modification et suppression des marques du catalogue GRINCO RDC.';
$adminCurrentPage = 'marques';
$logoutCsrfToken = grinco_csrf_token('admin_logout');
$adminPageScripts = array('/assets/js/admin-marques.js');

include dirname(__DIR__) . '/includes/admin/head.php';
?>

<div class="admin-layout">
  <?php include dirname(__DIR__) . '/includes/admin/sidebar.php'; ?>

  <div class="admin-main-shell">
    <?php include dirname(__DIR__) . '/includes/admin/header.php'; ?>

    <main class="admin-content" id="admin-main-content" data-brands-app data-endpoint="<?php echo grinco_url_html('/admin/marques.php'); ?>">
      <div class="admin-page-heading admin-page-heading-with-action">
        <div>
          <span class="admin-eyebrow">Catalogue</span>
          <h1>Gestion des marques</h1>
          <p>Gérez les constructeurs et marques associés aux produits du catalogue GRINCO RDC.</p>
        </div>
        <button type="button" class="admin-primary-button" data-bs-toggle="modal" data-bs-target="#add-brand-modal">
          <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Ajouter une marque</span>
        </button>
      </div>

      <?php if ($brandFlash): ?>
        <div class="admin-module-alert is-<?php echo grinco_admin_escape($brandFlash['type']); ?>" role="alert">
          <i class="bi <?php echo $brandFlash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>" aria-hidden="true"></i>
          <span><?php echo grinco_admin_escape($brandFlash['message']); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($brandLoadError !== ''): ?>
        <div class="admin-module-alert is-error" role="alert">
          <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
          <span><?php echo grinco_admin_escape($brandLoadError); ?></span>
        </div>
      <?php endif; ?>

      <section class="admin-table-card" aria-labelledby="brands-table-title">
        <div class="admin-table-toolbar">
          <div>
            <h2 id="brands-table-title">Liste des marques</h2>
            <p id="brand-results-summary">
              <?php if ($brandResult['total'] > 0): ?>
                <?php echo grinco_admin_escape(($brandResult['offset'] + 1) . '–' . min($brandResult['offset'] + $perPage, $brandResult['total']) . ' sur ' . $brandResult['total']); ?>
              <?php else: ?>Aucune marque<?php endif; ?>
            </p>
          </div>

          <form class="admin-search-field" action="<?php echo grinco_url_html('/admin/marques.php'); ?>" method="GET" role="search">
            <label for="brand-search" class="visually-hidden">Rechercher une marque</label>
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="brand-search" name="q" value="<?php echo grinco_admin_escape($search); ?>" placeholder="Nom ou description" maxlength="100" autocomplete="off">
            <button type="submit" class="visually-hidden">Rechercher</button>
            <span class="admin-search-spinner" aria-hidden="true"></span>
          </form>
        </div>

        <div id="brand-search-status" class="visually-hidden" role="status" aria-live="polite"></div>

        <div class="admin-table-responsive">
          <table class="admin-data-table">
            <thead><tr><th scope="col">N°</th><th scope="col">Nom</th><th scope="col">Description</th><th scope="col" class="admin-actions-column">Actions</th></tr></thead>
            <tbody id="brands-table-body">
              <?php foreach ($brandResult['rows'] as $index => $brand): ?>
                <tr>
                  <td data-label="N°"><?php echo grinco_admin_escape($brandResult['offset'] + $index + 1); ?></td>
                  <td data-label="Nom"><strong><?php echo grinco_admin_escape($brand['nom']); ?></strong></td>
                  <td data-label="Description"><span class="admin-description-text"><?php echo $brand['description'] === '' ? '—' : grinco_admin_escape($brand['description']); ?></span></td>
                  <td data-label="Actions" class="admin-actions-cell">
                    <button type="button" class="admin-icon-button is-edit" title="Modifier la marque" aria-label="Modifier la marque <?php echo grinco_admin_escape($brand['nom']); ?>" data-brand-edit data-brand-id="<?php echo grinco_admin_escape($brand['id']); ?>" data-brand-name="<?php echo grinco_admin_escape($brand['nom']); ?>" data-brand-description="<?php echo grinco_admin_escape($brand['description']); ?>" data-bs-toggle="modal" data-bs-target="#edit-brand-modal"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                    <button type="button" class="admin-icon-button is-delete" title="Supprimer la marque" aria-label="Supprimer la marque <?php echo grinco_admin_escape($brand['nom']); ?>" data-brand-delete data-brand-id="<?php echo grinco_admin_escape($brand['id']); ?>" data-brand-name="<?php echo grinco_admin_escape($brand['nom']); ?>" data-bs-toggle="modal" data-bs-target="#delete-brand-modal"><i class="bi bi-trash" aria-hidden="true"></i></button>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($brandResult['rows'])): ?>
                <tr class="admin-empty-table-row"><td colspan="4"><i class="bi bi-bookmark-star" aria-hidden="true"></i><strong>Aucune marque trouvée</strong><span>Ajoutez une marque ou modifiez votre recherche.</span></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <nav class="admin-pagination-wrap" aria-label="Pagination des marques">
          <ul class="admin-pagination" id="brands-pagination">
            <li class="<?php echo $brandResult['page'] <= 1 ? 'is-disabled' : ''; ?>">
              <?php if ($brandResult['page'] <= 1): ?><span><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="admin-pagination-text">Précédent</span></span>
              <?php else: ?><a href="<?php echo marques_pagination_url($brandResult['page'] - 1, $search); ?>" data-page="<?php echo $brandResult['page'] - 1; ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="admin-pagination-text">Précédent</span></a><?php endif; ?>
            </li>
            <?php foreach (marques_pagination_items($brandResult['page'], $brandResult['total_pages']) as $paginationItem): ?>
              <?php if (is_int($paginationItem)): ?>
                <li class="<?php echo $paginationItem === $brandResult['page'] ? 'is-active' : ''; ?>"><a href="<?php echo marques_pagination_url($paginationItem, $search); ?>" data-page="<?php echo $paginationItem; ?>" <?php echo $paginationItem === $brandResult['page'] ? 'aria-current="page"' : ''; ?>><?php echo $paginationItem; ?></a></li>
              <?php else: ?><li class="is-ellipsis"><span aria-hidden="true">…</span></li><?php endif; ?>
            <?php endforeach; ?>
            <li class="<?php echo $brandResult['page'] >= $brandResult['total_pages'] ? 'is-disabled' : ''; ?>">
              <?php if ($brandResult['page'] >= $brandResult['total_pages']): ?><span><span class="admin-pagination-text">Suivant</span><i class="bi bi-chevron-right" aria-hidden="true"></i></span>
              <?php else: ?><a href="<?php echo marques_pagination_url($brandResult['page'] + 1, $search); ?>" data-page="<?php echo $brandResult['page'] + 1; ?>"><span class="admin-pagination-text">Suivant</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a><?php endif; ?>
            </li>
          </ul>
        </nav>
      </section>
    </main>

    <?php include dirname(__DIR__) . '/includes/admin/footer.php'; ?>
  </div>
</div>

<div class="modal fade admin-modal" id="add-brand-modal" tabindex="-1" aria-labelledby="add-brand-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/marques.php'); ?>" method="POST">
    <div class="modal-header"><div><span class="admin-modal-eyebrow">Nouvelle entrée</span><h2 class="modal-title" id="add-brand-title">Ajouter une marque</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
    <div class="modal-body">
      <input type="hidden" name="brand_action" value="create"><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($brandCsrfToken); ?>"><input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>"><input type="hidden" name="return_page" value="<?php echo grinco_admin_escape($brandResult['page']); ?>">
      <div class="admin-form-field"><label for="add-brand-name">Nom <span aria-hidden="true">*</span></label><input type="text" id="add-brand-name" name="nom" maxlength="100" required></div>
      <div class="admin-form-field"><label for="add-brand-description">Description</label><textarea id="add-brand-description" name="description" rows="4" maxlength="5000"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-primary-button">Ajouter la marque</button></div>
  </form></div></div>
</div>

<div class="modal fade admin-modal" id="edit-brand-modal" tabindex="-1" aria-labelledby="edit-brand-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/marques.php'); ?>" method="POST">
    <div class="modal-header"><div><span class="admin-modal-eyebrow">Mise à jour</span><h2 class="modal-title" id="edit-brand-title">Modifier la marque</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
    <div class="modal-body">
      <input type="hidden" name="brand_action" value="update"><input type="hidden" name="brand_id" id="edit-brand-id" value=""><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($brandCsrfToken); ?>"><input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>"><input type="hidden" name="return_page" value="<?php echo grinco_admin_escape($brandResult['page']); ?>">
      <div class="admin-form-field"><label for="edit-brand-name">Nom <span aria-hidden="true">*</span></label><input type="text" id="edit-brand-name" name="nom" maxlength="100" required></div>
      <div class="admin-form-field"><label for="edit-brand-description">Description</label><textarea id="edit-brand-description" name="description" rows="4" maxlength="5000"></textarea></div>
    </div>
    <div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-primary-button">Enregistrer les modifications</button></div>
  </form></div></div>
</div>

<div class="modal fade admin-modal" id="delete-brand-modal" tabindex="-1" aria-labelledby="delete-brand-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/marques.php'); ?>" method="POST">
    <div class="modal-header"><h2 class="modal-title" id="delete-brand-title">Supprimer la marque</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
    <div class="modal-body admin-delete-confirmation">
      <input type="hidden" name="brand_action" value="delete"><input type="hidden" name="brand_id" id="delete-brand-id" value=""><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($brandCsrfToken); ?>"><input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>"><input type="hidden" name="return_page" value="<?php echo grinco_admin_escape($brandResult['page']); ?>">
      <span class="admin-delete-icon" aria-hidden="true"><i class="bi bi-trash"></i></span><p>Confirmez-vous la suppression de <strong id="delete-brand-name"></strong> ?</p><small>Cette opération est impossible si des produits utilisent cette marque.</small>
    </div>
    <div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-danger-button">Supprimer</button></div>
  </form></div></div>
</div>

<?php include dirname(__DIR__) . '/includes/admin/scripts.php'; ?>
