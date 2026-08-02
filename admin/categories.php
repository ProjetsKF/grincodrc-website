<?php
require_once dirname(__DIR__) . '/includes/admin-auth.php';

grinco_admin_bootstrap();
grinco_admin_require_authentication();

function categories_request_value($source, $key, $default)
{
    return isset($source[$key]) && is_scalar($source[$key])
        ? (string) $source[$key]
        : $default;
}

function categories_search_term($value)
{
    $value = grinco_normalize_text(strip_tags((string) $value), false);
    return grinco_utf8_substr($value, 0, 100);
}

function categories_page_number($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 1;
}

function categories_record_id($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 0;
}

function categories_fetch_page($search, $requestedPage, $perPage)
{
    $connection = grinco_database();
    $where = '';
    $parameters = array();

    if ($search !== '') {
        $where = ' WHERE nom LIKE :search_name'
            . ' OR COALESCE(description, \'\') LIKE :search_description'
            . ' OR LOWER(statut) = :search_status';
        $parameters[':search_name'] = '%' . $search . '%';
        $parameters[':search_description'] = '%' . $search . '%';
        $parameters[':search_status'] = function_exists('mb_strtolower')
            ? mb_strtolower($search, 'UTF-8')
            : strtolower($search);
    }

    $countStatement = $connection->prepare('SELECT COUNT(*) FROM categories' . $where);
    $countStatement->execute($parameters);
    $total = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, (int) $requestedPage), $totalPages);
    $offset = ($page - 1) * $perPage;

    $listStatement = $connection->prepare(
        'SELECT id, nom, description, statut, date_creation '
        . 'FROM categories'
        . $where
        . ' ORDER BY date_creation DESC, id DESC LIMIT :limit OFFSET :offset'
    );

    if ($search !== '') {
        $listStatement->bindValue(':search_name', '%' . $search . '%', PDO::PARAM_STR);
        $listStatement->bindValue(':search_description', '%' . $search . '%', PDO::PARAM_STR);
        $listStatement->bindValue(
            ':search_status',
            function_exists('mb_strtolower') ? mb_strtolower($search, 'UTF-8') : strtolower($search),
            PDO::PARAM_STR
        );
    }
    $listStatement->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $listStatement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $listStatement->execute();

    $rows = $listStatement->fetchAll();
    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['description'] = $row['description'] === null ? '' : (string) $row['description'];
        $row['date_creation_formatted'] = date('d/m/Y H:i', strtotime($row['date_creation']));
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

function categories_set_flash($type, $message)
{
    $_SESSION['admin_categories_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message
    );
}

function categories_redirect($search, $page)
{
    $query = array();
    if ($search !== '') {
        $query['q'] = $search;
    }
    if ($page > 1) {
        $query['page'] = $page;
    }

    $location = grinco_url('/admin/categories.php');
    if (!empty($query)) {
        $location .= '?' . http_build_query($query, '', '&');
    }

    header('Location: ' . $location);
    exit;
}

function categories_validate_input($name, $description, $status)
{
    $errors = array();
    $name = grinco_normalize_text(strip_tags($name), false);
    $description = grinco_normalize_text(strip_tags($description), true);

    if ($name === '') {
        $errors[] = 'Le nom de la catégorie est obligatoire.';
    } elseif (grinco_utf8_length($name) > 150) {
        $errors[] = 'Le nom de la catégorie ne peut pas dépasser 150 caractères.';
    }

    if (grinco_utf8_length($description) > 5000) {
        $errors[] = 'La description ne peut pas dépasser 5 000 caractères.';
    }

    if (!in_array($status, array('Actif', 'Inactif'), true)) {
        $errors[] = 'Le statut sélectionné n’est pas valide.';
    }

    return array(
        'valid' => empty($errors),
        'errors' => $errors,
        'name' => $name,
        'description' => $description,
        'status' => $status
    );
}

function categories_name_exists($name, $excludedId)
{
    $sql = 'SELECT id FROM categories WHERE nom = :nom';
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

function categories_pagination_url($page, $search)
{
    $query = array('page' => max(1, (int) $page));
    if ($search !== '') {
        $query['q'] = $search;
    }

    return htmlspecialchars(
        grinco_url('/admin/categories.php') . '?' . http_build_query($query, '', '&'),
        ENT_QUOTES,
        'UTF-8'
    );
}

function categories_pagination_items($currentPage, $totalPages)
{
    if ($totalPages <= 7) {
        return range(1, max(1, $totalPages));
    }

    $items = array(1);
    if ($currentPage > 4) {
        $items[] = 'ellipsis-start';
    }

    $start = max(2, $currentPage - 1);
    $end = min($totalPages - 1, $currentPage + 1);
    for ($page = $start; $page <= $end; $page++) {
        $items[] = $page;
    }

    if ($currentPage < $totalPages - 3) {
        $items[] = 'ellipsis-end';
    }
    $items[] = $totalPages;

    return $items;
}

$perPage = 10;
$search = categories_search_term(categories_request_value($_GET, 'q', ''));
$requestedPage = categories_page_number(categories_request_value($_GET, 'page', '1'));

if (categories_request_value($_GET, 'ajax', '') === '1') {
    try {
        $ajaxResult = categories_fetch_page($search, $requestedPage, $perPage);
        header('Content-Type: application/json; charset=UTF-8');
        echo grinco_json_encode(array('success' => true, 'data' => $ajaxResult));
    } catch (PDOException $exception) {
        error_log('[GRINCO admin categories] Unable to load AJAX list.');
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo grinco_json_encode(array(
            'success' => false,
            'message' => 'Les catégories ne peuvent pas être chargées pour le moment.'
        ));
    }
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = categories_request_value($_POST, 'category_action', '');
    $csrfToken = categories_request_value($_POST, 'csrf_token', '');
    $returnSearch = categories_search_term(categories_request_value($_POST, 'return_search', ''));
    $returnPage = categories_page_number(categories_request_value($_POST, 'return_page', '1'));

    if (!grinco_validate_csrf_token('admin_categories', $csrfToken)) {
        categories_set_flash('error', 'Votre session a expiré. Veuillez réessayer.');
        grinco_regenerate_csrf_token('admin_categories');
        categories_redirect($returnSearch, $returnPage);
    }

    if (!grinco_validate_request_origin()) {
        categories_set_flash('error', 'La demande n’a pas pu être vérifiée.');
        grinco_regenerate_csrf_token('admin_categories');
        categories_redirect($returnSearch, $returnPage);
    }

    try {
        if ($action === 'create' || $action === 'update') {
            $categoryId = categories_record_id(categories_request_value($_POST, 'category_id', '0'));
            if ($action === 'create') {
                $categoryId = 0;
            }

            $validation = categories_validate_input(
                categories_request_value($_POST, 'nom', ''),
                categories_request_value($_POST, 'description', ''),
                categories_request_value($_POST, 'statut', 'Actif')
            );

            if (!$validation['valid']) {
                categories_set_flash('error', implode(' ', $validation['errors']));
            } elseif ($action === 'update' && $categoryId <= 0) {
                categories_set_flash('error', 'La catégorie sélectionnée n’est pas valide.');
            } elseif (categories_name_exists($validation['name'], $categoryId)) {
                categories_set_flash('error', 'Une catégorie portant ce nom existe déjà.');
            } elseif ($action === 'create') {
                $statement = grinco_database()->prepare(
                    'INSERT INTO categories (nom, description, statut) VALUES (:nom, :description, :statut)'
                );
                $statement->execute(array(
                    ':nom' => $validation['name'],
                    ':description' => $validation['description'] === '' ? null : $validation['description'],
                    ':statut' => $validation['status']
                ));
                categories_set_flash('success', 'La catégorie a été ajoutée avec succès.');
            } else {
                $existsStatement = grinco_database()->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
                $existsStatement->execute(array(':id' => $categoryId));

                if (!$existsStatement->fetchColumn()) {
                    categories_set_flash('error', 'La catégorie demandée est introuvable.');
                } else {
                    $statement = grinco_database()->prepare(
                        'UPDATE categories SET nom = :nom, description = :description, statut = :statut WHERE id = :id'
                    );
                    $statement->execute(array(
                        ':nom' => $validation['name'],
                        ':description' => $validation['description'] === '' ? null : $validation['description'],
                        ':statut' => $validation['status'],
                        ':id' => $categoryId
                    ));
                    categories_set_flash('success', 'La catégorie a été modifiée avec succès.');
                }
            }
        } elseif ($action === 'delete') {
            $categoryId = categories_record_id(categories_request_value($_POST, 'category_id', '0'));

            if ($categoryId <= 0) {
                categories_set_flash('error', 'La catégorie sélectionnée n’est pas valide.');
            } else {
                $categoryStatement = grinco_database()->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
                $categoryStatement->execute(array(':id' => $categoryId));

                if (!$categoryStatement->fetchColumn()) {
                    categories_set_flash('error', 'La catégorie demandée est introuvable.');
                } else {
                    $usageStatement = grinco_database()->prepare(
                        'SELECT COUNT(*) FROM produits WHERE categorie_id = :category_id'
                    );
                    $usageStatement->execute(array(':category_id' => $categoryId));

                    if ((int) $usageStatement->fetchColumn() > 0) {
                        categories_set_flash(
                            'error',
                            'Cette catégorie est utilisée par un ou plusieurs produits et ne peut pas être supprimée.'
                        );
                    } else {
                        $deleteStatement = grinco_database()->prepare('DELETE FROM categories WHERE id = :id');
                        $deleteStatement->execute(array(':id' => $categoryId));
                        categories_set_flash('success', 'La catégorie a été supprimée avec succès.');
                    }
                }
            }
        } else {
            categories_set_flash('error', 'L’action demandée n’est pas valide.');
        }
    } catch (PDOException $exception) {
        error_log('[GRINCO admin categories] Database operation failed.');
        if ((string) $exception->getCode() === '23000' && ($action === 'create' || $action === 'update')) {
            categories_set_flash('error', 'Une catégorie portant ce nom existe déjà.');
        } elseif ((string) $exception->getCode() === '23000' && $action === 'delete') {
            categories_set_flash('error', 'Cette catégorie est utilisée et ne peut pas être supprimée.');
        } else {
            categories_set_flash('error', 'L’opération ne peut pas être effectuée pour le moment.');
        }
    }

    grinco_regenerate_csrf_token('admin_categories');
    categories_redirect($returnSearch, $returnPage);
}

$categoryCsrfToken = grinco_csrf_token('admin_categories');
$categoryFlash = isset($_SESSION['admin_categories_flash']) && is_array($_SESSION['admin_categories_flash'])
    ? $_SESSION['admin_categories_flash']
    : null;
unset($_SESSION['admin_categories_flash']);

$categoryResult = array(
    'rows' => array(),
    'page' => 1,
    'per_page' => $perPage,
    'total' => 0,
    'total_pages' => 1,
    'offset' => 0
);
$categoryLoadError = '';

try {
    $categoryResult = categories_fetch_page($search, $requestedPage, $perPage);
} catch (PDOException $exception) {
    error_log('[GRINCO admin categories] Unable to load initial list.');
    $categoryLoadError = 'Les catégories ne peuvent pas être chargées pour le moment.';
}

$adminPageTitle = 'Gestion des catégories';
$adminPageDescription = 'Ajout, modification et suppression des catégories du catalogue GRINCO RDC.';
$adminCurrentPage = 'categories';
$logoutCsrfToken = grinco_csrf_token('admin_logout');
$adminPageScripts = array('/assets/js/admin-categories.js');

include dirname(__DIR__) . '/includes/admin/head.php';
?>

<div class="admin-layout">
  <?php include dirname(__DIR__) . '/includes/admin/sidebar.php'; ?>

  <div class="admin-main-shell">
    <?php include dirname(__DIR__) . '/includes/admin/header.php'; ?>

    <main
      class="admin-content"
      id="admin-main-content"
      data-categories-app
      data-endpoint="<?php echo grinco_url_html('/admin/categories.php'); ?>"
    >
      <div class="admin-page-heading admin-page-heading-with-action">
        <div>
          <span class="admin-eyebrow">Catalogue</span>
          <h1>Gestion des catégories</h1>
          <p>Organisez les familles de produits disponibles dans le catalogue GRINCO RDC.</p>
        </div>
        <button type="button" class="admin-primary-button" data-bs-toggle="modal" data-bs-target="#add-category-modal">
          <i class="bi bi-plus-lg" aria-hidden="true"></i>
          <span>Ajouter une catégorie</span>
        </button>
      </div>

      <?php if ($categoryFlash): ?>
        <div class="admin-module-alert is-<?php echo grinco_admin_escape($categoryFlash['type']); ?>" role="alert">
          <i class="bi <?php echo $categoryFlash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>" aria-hidden="true"></i>
          <span><?php echo grinco_admin_escape($categoryFlash['message']); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($categoryLoadError !== ''): ?>
        <div class="admin-module-alert is-error" role="alert">
          <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
          <span><?php echo grinco_admin_escape($categoryLoadError); ?></span>
        </div>
      <?php endif; ?>

      <section class="admin-table-card" aria-labelledby="categories-table-title">
        <div class="admin-table-toolbar">
          <div>
            <h2 id="categories-table-title">Liste des catégories</h2>
            <p id="category-results-summary">
              <?php if ($categoryResult['total'] > 0): ?>
                <?php echo grinco_admin_escape(($categoryResult['offset'] + 1) . '–' . min($categoryResult['offset'] + $perPage, $categoryResult['total']) . ' sur ' . $categoryResult['total']); ?>
              <?php else: ?>
                Aucune catégorie
              <?php endif; ?>
            </p>
          </div>

          <form class="admin-search-field" action="<?php echo grinco_url_html('/admin/categories.php'); ?>" method="GET" role="search">
            <label for="category-search" class="visually-hidden">Rechercher une catégorie</label>
            <i class="bi bi-search" aria-hidden="true"></i>
            <input
              type="search"
              id="category-search"
              name="q"
              value="<?php echo grinco_admin_escape($search); ?>"
              placeholder="Nom, description ou statut"
              maxlength="100"
              autocomplete="off"
            >
            <button type="submit" class="visually-hidden">Rechercher</button>
            <span class="admin-search-spinner" aria-hidden="true"></span>
          </form>
        </div>

        <div id="category-search-status" class="visually-hidden" role="status" aria-live="polite"></div>

        <div class="admin-table-responsive">
          <table class="admin-data-table">
            <thead>
              <tr>
                <th scope="col">N°</th>
                <th scope="col">Nom</th>
                <th scope="col">Description</th>
                <th scope="col">Statut</th>
                <th scope="col">Date de création</th>
                <th scope="col" class="admin-actions-column">Actions</th>
              </tr>
            </thead>
            <tbody id="categories-table-body">
              <?php foreach ($categoryResult['rows'] as $index => $category): ?>
                <tr>
                  <td data-label="N°"><?php echo grinco_admin_escape($categoryResult['offset'] + $index + 1); ?></td>
                  <td data-label="Nom"><strong><?php echo grinco_admin_escape($category['nom']); ?></strong></td>
                  <td data-label="Description">
                    <span class="admin-description-text"><?php echo $category['description'] === '' ? '—' : grinco_admin_escape($category['description']); ?></span>
                  </td>
                  <td data-label="Statut">
                    <span class="admin-status-badge is-<?php echo $category['statut'] === 'Actif' ? 'active' : 'inactive'; ?>">
                      <?php echo grinco_admin_escape($category['statut']); ?>
                    </span>
                  </td>
                  <td data-label="Date de création"><?php echo grinco_admin_escape($category['date_creation_formatted']); ?></td>
                  <td data-label="Actions" class="admin-actions-cell">
                    <button
                      type="button"
                      class="admin-icon-button is-edit"
                      title="Modifier la catégorie"
                      aria-label="Modifier la catégorie <?php echo grinco_admin_escape($category['nom']); ?>"
                      data-category-edit
                      data-category-id="<?php echo grinco_admin_escape($category['id']); ?>"
                      data-category-name="<?php echo grinco_admin_escape($category['nom']); ?>"
                      data-category-description="<?php echo grinco_admin_escape($category['description']); ?>"
                      data-category-status="<?php echo grinco_admin_escape($category['statut']); ?>"
                      data-bs-toggle="modal"
                      data-bs-target="#edit-category-modal"
                    ><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                    <button
                      type="button"
                      class="admin-icon-button is-delete"
                      title="Supprimer la catégorie"
                      aria-label="Supprimer la catégorie <?php echo grinco_admin_escape($category['nom']); ?>"
                      data-category-delete
                      data-category-id="<?php echo grinco_admin_escape($category['id']); ?>"
                      data-category-name="<?php echo grinco_admin_escape($category['nom']); ?>"
                      data-bs-toggle="modal"
                      data-bs-target="#delete-category-modal"
                    ><i class="bi bi-trash" aria-hidden="true"></i></button>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (empty($categoryResult['rows'])): ?>
                <tr class="admin-empty-table-row">
                  <td colspan="6">
                    <i class="bi bi-tags" aria-hidden="true"></i>
                    <strong>Aucune catégorie trouvée</strong>
                    <span>Ajoutez une catégorie ou modifiez votre recherche.</span>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <nav class="admin-pagination-wrap" aria-label="Pagination des catégories">
          <ul class="admin-pagination" id="categories-pagination">
            <li class="<?php echo $categoryResult['page'] <= 1 ? 'is-disabled' : ''; ?>">
              <?php if ($categoryResult['page'] <= 1): ?>
                <span><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="admin-pagination-text">Précédent</span></span>
              <?php else: ?>
                <a href="<?php echo categories_pagination_url($categoryResult['page'] - 1, $search); ?>" data-page="<?php echo $categoryResult['page'] - 1; ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="admin-pagination-text">Précédent</span></a>
              <?php endif; ?>
            </li>

            <?php foreach (categories_pagination_items($categoryResult['page'], $categoryResult['total_pages']) as $paginationItem): ?>
              <?php if (is_int($paginationItem)): ?>
                <li class="<?php echo $paginationItem === $categoryResult['page'] ? 'is-active' : ''; ?>">
                  <a href="<?php echo categories_pagination_url($paginationItem, $search); ?>" data-page="<?php echo $paginationItem; ?>" <?php echo $paginationItem === $categoryResult['page'] ? 'aria-current="page"' : ''; ?>><?php echo $paginationItem; ?></a>
                </li>
              <?php else: ?>
                <li class="is-ellipsis"><span aria-hidden="true">…</span></li>
              <?php endif; ?>
            <?php endforeach; ?>

            <li class="<?php echo $categoryResult['page'] >= $categoryResult['total_pages'] ? 'is-disabled' : ''; ?>">
              <?php if ($categoryResult['page'] >= $categoryResult['total_pages']): ?>
                <span><span class="admin-pagination-text">Suivant</span><i class="bi bi-chevron-right" aria-hidden="true"></i></span>
              <?php else: ?>
                <a href="<?php echo categories_pagination_url($categoryResult['page'] + 1, $search); ?>" data-page="<?php echo $categoryResult['page'] + 1; ?>"><span class="admin-pagination-text">Suivant</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
              <?php endif; ?>
            </li>
          </ul>
        </nav>
      </section>
    </main>

    <?php include dirname(__DIR__) . '/includes/admin/footer.php'; ?>
  </div>
</div>

<div class="modal fade admin-modal" id="add-category-modal" tabindex="-1" aria-labelledby="add-category-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="<?php echo grinco_url_html('/admin/categories.php'); ?>" method="POST">
        <div class="modal-header">
          <div><span class="admin-modal-eyebrow">Nouvelle entrée</span><h2 class="modal-title" id="add-category-title">Ajouter une catégorie</h2></div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="category_action" value="create">
          <input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($categoryCsrfToken); ?>">
          <input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>">
          <input type="hidden" name="return_page" value="<?php echo grinco_admin_escape($categoryResult['page']); ?>">

          <div class="admin-form-field">
            <label for="add-category-name">Nom <span aria-hidden="true">*</span></label>
            <input type="text" id="add-category-name" name="nom" maxlength="150" required>
          </div>
          <div class="admin-form-field">
            <label for="add-category-description">Description</label>
            <textarea id="add-category-description" name="description" rows="4" maxlength="5000"></textarea>
          </div>
          <div class="admin-form-field">
            <label for="add-category-status">Statut</label>
            <select id="add-category-status" name="statut">
              <option value="Actif" selected>Actif</option>
              <option value="Inactif">Inactif</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="admin-primary-button">Ajouter la catégorie</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade admin-modal" id="edit-category-modal" tabindex="-1" aria-labelledby="edit-category-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="<?php echo grinco_url_html('/admin/categories.php'); ?>" method="POST">
        <div class="modal-header">
          <div><span class="admin-modal-eyebrow">Mise à jour</span><h2 class="modal-title" id="edit-category-title">Modifier la catégorie</h2></div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="category_action" value="update">
          <input type="hidden" name="category_id" id="edit-category-id" value="">
          <input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($categoryCsrfToken); ?>">
          <input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>">
          <input type="hidden" name="return_page" value="<?php echo grinco_admin_escape($categoryResult['page']); ?>">

          <div class="admin-form-field">
            <label for="edit-category-name">Nom <span aria-hidden="true">*</span></label>
            <input type="text" id="edit-category-name" name="nom" maxlength="150" required>
          </div>
          <div class="admin-form-field">
            <label for="edit-category-description">Description</label>
            <textarea id="edit-category-description" name="description" rows="4" maxlength="5000"></textarea>
          </div>
          <div class="admin-form-field">
            <label for="edit-category-status">Statut</label>
            <select id="edit-category-status" name="statut">
              <option value="Actif">Actif</option>
              <option value="Inactif">Inactif</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="admin-primary-button">Enregistrer les modifications</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade admin-modal" id="delete-category-modal" tabindex="-1" aria-labelledby="delete-category-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form action="<?php echo grinco_url_html('/admin/categories.php'); ?>" method="POST">
        <div class="modal-header">
          <h2 class="modal-title" id="delete-category-title">Supprimer la catégorie</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body admin-delete-confirmation">
          <input type="hidden" name="category_action" value="delete">
          <input type="hidden" name="category_id" id="delete-category-id" value="">
          <input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($categoryCsrfToken); ?>">
          <input type="hidden" name="return_search" value="<?php echo grinco_admin_escape($search); ?>">
          <input type="hidden" name="return_page" value="<?php echo grinco_admin_escape($categoryResult['page']); ?>">

          <span class="admin-delete-icon" aria-hidden="true"><i class="bi bi-trash"></i></span>
          <p>Confirmez-vous la suppression de <strong id="delete-category-name"></strong> ?</p>
          <small>Cette opération est impossible si des produits utilisent cette catégorie.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="admin-danger-button">Supprimer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/admin/scripts.php'; ?>
