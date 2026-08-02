<?php
require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/catalogue-files.php';
require_once dirname(__DIR__) . '/includes/admin/media-repository.php';

grinco_admin_bootstrap();
grinco_admin_require_authentication();

function media_request_value($source, $key, $default)
{
    return isset($source[$key]) && is_scalar($source[$key]) ? (string) $source[$key] : $default;
}

function media_record_id($value)
{
    return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 0;
}

function media_active_tab($value)
{
    return $value === 'documents' ? 'documents' : 'images';
}

function media_set_flash($type, $message)
{
    $_SESSION['admin_media_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message
    );
}

function media_redirect($productId, $tab)
{
    $query = array('tab' => media_active_tab($tab));
    if ($productId > 0) {
        $query['produit_id'] = (int) $productId;
    }
    header('Location: ' . grinco_url('/admin/images-documents.php') . '?' . http_build_query($query, '', '&'));
    exit;
}

function media_validate_upload_collection($files, $type, $maximumCount)
{
    if (empty($files)) {
        throw new RuntimeException('Sélectionnez au moins un fichier.');
    }
    if (count($files) > $maximumCount) {
        throw new RuntimeException('Vous pouvez envoyer au maximum ' . $maximumCount . ' fichiers à la fois.');
    }

    $validated = array();
    foreach ($files as $index => $file) {
        $validation = grinco_catalogue_validate_upload($file, $type);
        if (!$validation['valid']) {
            throw new RuntimeException('Fichier ' . ($index + 1) . ' : ' . $validation['message']);
        }
        $validated[] = $validation;
    }
    return $validated;
}

function media_lock_product($connection, $productId)
{
    $lock = $connection->prepare('SELECT id FROM produits WHERE id = :id FOR UPDATE');
    $lock->execute(array(':id' => (int) $productId));
    if (!$lock->fetchColumn()) {
        throw new RuntimeException('Le produit sélectionné est introuvable.');
    }
}

function media_upload_images($productId)
{
    $files = grinco_catalogue_normalize_uploads(isset($_FILES['images']) ? $_FILES['images'] : array());
    $validated = media_validate_upload_collection($files, 'image', 10);
    $principalValue = media_request_value($_POST, 'principal_index', '');
    $principalIndex = null;
    if ($principalValue !== '') {
        if (!ctype_digit($principalValue) || (int) $principalValue >= count($files)) {
            throw new RuntimeException('L’image principale sélectionnée n’est pas valide.');
        }
        $principalIndex = (int) $principalValue;
    }

    $connection = grinco_database();
    $storedFiles = array();
    $connection->beginTransaction();
    try {
        media_lock_product($connection, $productId);
        if ($principalIndex === null && !grinco_media_product_has_primary_image($productId)) {
            $principalIndex = 0;
        }
        if ($principalIndex !== null) {
            $reset = $connection->prepare(
                'UPDATE produit_images SET image_principale = 0 WHERE produit_id = :product_id'
            );
            $reset->execute(array(':product_id' => $productId));
        }

        $insert = $connection->prepare(
            'INSERT INTO produit_images (produit_id, image, image_principale) '
            . 'VALUES (:product_id, :image, :is_primary)'
        );
        foreach ($files as $index => $file) {
            $stored = grinco_catalogue_store_upload($file, 'image', $validated[$index]);
            $storedFiles[] = $stored;
            $insert->execute(array(
                ':product_id' => $productId,
                ':image' => $stored['stored_path'],
                ':is_primary' => $principalIndex !== null && $principalIndex === $index ? 1 : 0
            ));
        }
        $connection->commit();
    } catch (Exception $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        foreach ($storedFiles as $stored) {
            if (is_file($stored['absolute_path'])) {
                @unlink($stored['absolute_path']);
            }
        }
        throw $exception;
    }

    return count($files);
}

function media_upload_documents($productId)
{
    $files = grinco_catalogue_normalize_uploads(isset($_FILES['documents']) ? $_FILES['documents'] : array());
    $validated = media_validate_upload_collection($files, 'document', 10);
    $connection = grinco_database();
    $storedFiles = array();
    $connection->beginTransaction();
    try {
        media_lock_product($connection, $productId);
        $insert = $connection->prepare(
            'INSERT INTO produit_documents (produit_id, document) VALUES (:product_id, :document)'
        );
        foreach ($files as $index => $file) {
            $stored = grinco_catalogue_store_upload($file, 'document', $validated[$index]);
            $storedFiles[] = $stored;
            $insert->execute(array(
                ':product_id' => $productId,
                ':document' => $stored['stored_path']
            ));
        }
        $connection->commit();
    } catch (Exception $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        foreach ($storedFiles as $stored) {
            if (is_file($stored['absolute_path'])) {
                @unlink($stored['absolute_path']);
            }
        }
        throw $exception;
    }

    return count($files);
}

function media_set_primary_image($productId, $imageId)
{
    if (!grinco_media_find_image($imageId, $productId)) {
        throw new RuntimeException('L’image demandée est introuvable.');
    }

    $connection = grinco_database();
    $connection->beginTransaction();
    try {
        media_lock_product($connection, $productId);
        $reset = $connection->prepare(
            'UPDATE produit_images SET image_principale = 0 WHERE produit_id = :product_id'
        );
        $reset->execute(array(':product_id' => $productId));
        $set = $connection->prepare(
            'UPDATE produit_images SET image_principale = 1 '
            . 'WHERE id = :id AND produit_id = :product_id'
        );
        $set->execute(array(':id' => $imageId, ':product_id' => $productId));
        $connection->commit();
    } catch (Exception $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
}

function media_delete_image($productId, $imageId)
{
    $image = grinco_media_find_image($imageId, $productId);
    if (!$image) {
        throw new RuntimeException('L’image demandée est introuvable.');
    }

    $staged = grinco_catalogue_stage_file_deletion($image['image'], 'image');
    $connection = grinco_database();
    $connection->beginTransaction();
    try {
        media_lock_product($connection, $productId);
        $delete = $connection->prepare(
            'DELETE FROM produit_images WHERE id = :id AND produit_id = :product_id'
        );
        $delete->execute(array(':id' => $imageId, ':product_id' => $productId));

        if ((int) $image['image_principale'] === 1) {
            $next = $connection->prepare(
                'SELECT id FROM produit_images WHERE produit_id = :product_id ORDER BY id ASC LIMIT 1'
            );
            $next->execute(array(':product_id' => $productId));
            $nextImageId = (int) $next->fetchColumn();
            if ($nextImageId > 0) {
                $promote = $connection->prepare(
                    'UPDATE produit_images SET image_principale = 1 '
                    . 'WHERE id = :id AND produit_id = :product_id'
                );
                $promote->execute(array(':id' => $nextImageId, ':product_id' => $productId));
            }
        }
        $connection->commit();
    } catch (Exception $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        grinco_catalogue_restore_staged_file($staged);
        throw $exception;
    }

    if (!grinco_catalogue_finalize_staged_file($staged)) {
        error_log('[GRINCO admin media] Image database row deleted but staged file cleanup failed.');
        throw new RuntimeException('L’image a été retirée, mais le nettoyage du fichier physique a échoué.');
    }
}

function media_delete_document($productId, $documentId)
{
    $document = grinco_media_find_document($documentId, $productId);
    if (!$document) {
        throw new RuntimeException('Le document demandé est introuvable.');
    }

    $staged = grinco_catalogue_stage_file_deletion($document['document'], 'document');
    $connection = grinco_database();
    $connection->beginTransaction();
    try {
        media_lock_product($connection, $productId);
        $delete = $connection->prepare(
            'DELETE FROM produit_documents WHERE id = :id AND produit_id = :product_id'
        );
        $delete->execute(array(':id' => $documentId, ':product_id' => $productId));
        $connection->commit();
    } catch (Exception $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        grinco_catalogue_restore_staged_file($staged);
        throw $exception;
    }

    if (!grinco_catalogue_finalize_staged_file($staged)) {
        error_log('[GRINCO admin media] Document database row deleted but staged file cleanup failed.');
        throw new RuntimeException('Le document a été retiré, mais le nettoyage du fichier physique a échoué.');
    }
}

$selectedProductId = media_record_id(media_request_value($_GET, 'produit_id', '0'));
$activeTab = media_active_tab(media_request_value($_GET, 'tab', 'images'));

$downloadDocumentId = media_record_id(media_request_value($_GET, 'telecharger_document', '0'));
if ($downloadDocumentId > 0) {
    $document = grinco_media_find_document_by_id($downloadDocumentId);
    $resolvedDocument = $document
        ? grinco_catalogue_resolve_stored_file($document['document'], 'document')
        : false;
    if (!$document || $resolvedDocument === false || !$resolvedDocument['exists']) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Document introuvable.';
        exit;
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="document-' . (int) $document['id'] . '.pdf"');
    header('Content-Length: ' . filesize($resolvedDocument['absolute_path']));
    header('X-Content-Type-Options: nosniff');
    readfile($resolvedDocument['absolute_path']);
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postMaximum = grinco_catalogue_post_maximum_bytes();
    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    if ($postMaximum > 0 && $contentLength > $postMaximum && empty($_POST) && empty($_FILES)) {
        media_set_flash('error', 'L’envoi dépasse la limite totale autorisée par le serveur. Réduisez le nombre ou la taille des fichiers.');
        media_redirect($selectedProductId, $activeTab);
    }

    $productId = media_record_id(media_request_value($_POST, 'produit_id', (string) $selectedProductId));
    $action = media_request_value($_POST, 'media_action', '');
    $tab = media_active_tab(media_request_value($_POST, 'return_tab', $activeTab));
    $csrfToken = media_request_value($_POST, 'csrf_token', '');

    if (!grinco_validate_csrf_token('admin_catalogue_media', $csrfToken)) {
        media_set_flash('error', 'Votre session a expiré. Veuillez réessayer.');
        grinco_regenerate_csrf_token('admin_catalogue_media');
        media_redirect($productId, $tab);
    }
    if (!grinco_validate_request_origin()) {
        media_set_flash('error', 'La demande n’a pas pu être vérifiée.');
        grinco_regenerate_csrf_token('admin_catalogue_media');
        media_redirect($productId, $tab);
    }

    try {
        if ($productId <= 0 || !grinco_media_find_product($productId)) {
            throw new RuntimeException('Le produit sélectionné est introuvable.');
        }

        if ($action === 'upload_images') {
            $count = media_upload_images($productId);
            media_set_flash('success', $count . ' image' . ($count > 1 ? 's ont' : ' a') . ' été ajoutée' . ($count > 1 ? 's' : '') . ' avec succès.');
            $tab = 'images';
        } elseif ($action === 'set_primary_image') {
            media_set_primary_image($productId, media_record_id(media_request_value($_POST, 'image_id', '0')));
            media_set_flash('success', 'L’image principale a été mise à jour.');
            $tab = 'images';
        } elseif ($action === 'delete_image') {
            media_delete_image($productId, media_record_id(media_request_value($_POST, 'image_id', '0')));
            media_set_flash('success', 'L’image et son fichier physique ont été supprimés.');
            $tab = 'images';
        } elseif ($action === 'upload_documents') {
            $count = media_upload_documents($productId);
            media_set_flash('success', $count . ' document' . ($count > 1 ? 's ont' : ' a') . ' été ajouté' . ($count > 1 ? 's' : '') . ' avec succès.');
            $tab = 'documents';
        } elseif ($action === 'delete_document') {
            media_delete_document($productId, media_record_id(media_request_value($_POST, 'document_id', '0')));
            media_set_flash('success', 'Le document et son fichier physique ont été supprimés.');
            $tab = 'documents';
        } else {
            throw new RuntimeException('L’action demandée n’est pas valide.');
        }
    } catch (RuntimeException $exception) {
        media_set_flash('error', $exception->getMessage());
    } catch (PDOException $exception) {
        error_log('[GRINCO admin media] Database operation failed.');
        media_set_flash('error', 'L’opération ne peut pas être effectuée pour le moment.');
    } catch (Exception $exception) {
        error_log('[GRINCO admin media] File operation failed.');
        media_set_flash('error', 'L’opération ne peut pas être effectuée pour le moment.');
    }

    grinco_regenerate_csrf_token('admin_catalogue_media');
    media_redirect($productId, $tab);
}

$mediaCsrfToken = grinco_csrf_token('admin_catalogue_media');
$mediaFlash = isset($_SESSION['admin_media_flash']) && is_array($_SESSION['admin_media_flash'])
    ? $_SESSION['admin_media_flash']
    : null;
unset($_SESSION['admin_media_flash']);

$productOptions = array();
$selectedProduct = null;
$images = array();
$documents = array();
$pageError = '';
$selectionError = '';
try {
    $productOptions = grinco_media_product_options();
    if ($selectedProductId > 0) {
        $selectedProduct = grinco_media_find_product($selectedProductId);
        if (!$selectedProduct) {
            $selectionError = 'Le produit demandé n’existe pas ou n’est plus disponible.';
            $selectedProductId = 0;
        } else {
            $images = grinco_media_images($selectedProductId);
            $documents = grinco_media_documents($selectedProductId);
        }
    }
} catch (PDOException $exception) {
    error_log('[GRINCO admin media] Unable to load page data.');
    $pageError = 'Les images et documents ne peuvent pas être chargés pour le moment.';
}

$adminPageTitle = 'Gestion des images et documents';
$adminPageDescription = 'Gestion sécurisée des images et documents des produits GRINCO RDC.';
$adminCurrentPage = 'media';
$logoutCsrfToken = grinco_csrf_token('admin_logout');
$adminPageScripts = array('/assets/js/admin-images-documents.js');

include dirname(__DIR__) . '/includes/admin/head.php';
?>

<div class="admin-layout">
  <?php include dirname(__DIR__) . '/includes/admin/sidebar.php'; ?>
  <div class="admin-main-shell">
    <?php include dirname(__DIR__) . '/includes/admin/header.php'; ?>

    <main class="admin-content" id="admin-main-content" data-media-app>
      <div class="admin-page-heading">
        <span class="admin-eyebrow">Catalogue</span>
        <h1>Gestion des images et documents</h1>
        <p>Sélectionnez un produit pour gérer séparément ses images et ses documents PDF.</p>
      </div>

      <?php if ($mediaFlash): ?>
        <div class="admin-module-alert is-<?php echo grinco_admin_escape($mediaFlash['type']); ?>" role="alert"><i class="bi <?php echo $mediaFlash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>" aria-hidden="true"></i><span><?php echo grinco_admin_escape($mediaFlash['message']); ?></span></div>
      <?php endif; ?>
      <?php if ($selectionError !== ''): ?>
        <div class="admin-module-alert is-error" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span><?php echo grinco_admin_escape($selectionError); ?></span></div>
      <?php endif; ?>
      <?php if ($pageError !== ''): ?>
        <div class="admin-module-alert is-error" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span><?php echo grinco_admin_escape($pageError); ?></span></div>
      <?php endif; ?>

      <section class="admin-media-selector-card" aria-labelledby="media-product-title">
        <div>
          <h2 id="media-product-title">Produit concerné</h2>
          <p>Les médias affichés appartiennent uniquement au produit sélectionné.</p>
        </div>
        <form action="<?php echo grinco_url_html('/admin/images-documents.php'); ?>" method="GET" class="admin-product-selector" data-product-selector-form>
          <label for="media-product-select">Sélectionner un produit</label>
          <select id="media-product-select" name="produit_id" data-product-selector>
            <option value="">Choisir un produit</option>
            <?php foreach ($productOptions as $productOption): ?>
              <option value="<?php echo grinco_admin_escape($productOption['id']); ?>" <?php echo (int) $productOption['id'] === $selectedProductId ? 'selected' : ''; ?>><?php echo grinco_admin_escape($productOption['reference'] . ' — ' . $productOption['nom'] . ($productOption['modele'] ? ' ' . $productOption['modele'] : '')); ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="tab" value="<?php echo grinco_admin_escape($activeTab); ?>">
          <button type="submit" class="admin-primary-button">Afficher</button>
        </form>
      </section>

      <section class="admin-media-workspace" aria-label="Médias du produit">
        <ul class="nav admin-media-tabs" role="tablist">
          <li class="nav-item" role="presentation"><button class="nav-link<?php echo $activeTab === 'images' ? ' active' : ''; ?>" id="images-tab" data-bs-toggle="tab" data-bs-target="#images-panel" type="button" role="tab" aria-controls="images-panel" aria-selected="<?php echo $activeTab === 'images' ? 'true' : 'false'; ?>"><i class="bi bi-images" aria-hidden="true"></i> Images <span><?php echo count($images); ?></span></button></li>
          <li class="nav-item" role="presentation"><button class="nav-link<?php echo $activeTab === 'documents' ? ' active' : ''; ?>" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents-panel" type="button" role="tab" aria-controls="documents-panel" aria-selected="<?php echo $activeTab === 'documents' ? 'true' : 'false'; ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Documents <span><?php echo count($documents); ?></span></button></li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade<?php echo $activeTab === 'images' ? ' show active' : ''; ?>" id="images-panel" role="tabpanel" aria-labelledby="images-tab" tabindex="0">
            <div class="admin-media-panel-heading"><div><h2>Images du produit</h2><p>JPG, JPEG, PNG ou WEBP — 2 Mo maximum par fichier, 10 images par envoi.</p></div><?php if ($selectedProduct): ?><button type="button" class="admin-primary-button" data-bs-toggle="modal" data-bs-target="#upload-images-modal"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Ajouter des images</button><?php endif; ?></div>
            <?php if (!$selectedProduct): ?>
              <div class="admin-media-empty"><i class="bi bi-box-seam" aria-hidden="true"></i><strong>Sélectionnez d’abord un produit</strong><span>Ses images apparaîtront dans cet onglet.</span></div>
            <?php elseif (empty($images)): ?>
              <div class="admin-media-empty"><i class="bi bi-images" aria-hidden="true"></i><strong>Aucune image enregistrée</strong><span>Ajoutez les premières images de ce produit.</span></div>
            <?php else: ?>
              <div class="admin-image-grid">
                <?php foreach ($images as $image): ?><?php $imageUrl = grinco_catalogue_file_url($image['image'], 'image'); ?>
                  <article class="admin-image-card">
                    <div class="admin-image-preview">
                      <?php if ($imageUrl !== ''): ?><img src="<?php echo grinco_admin_escape($imageUrl); ?>" alt="Image du produit <?php echo grinco_admin_escape($selectedProduct['reference']); ?>" loading="lazy"><?php else: ?><span class="admin-file-missing"><i class="bi bi-image" aria-hidden="true"></i> Fichier indisponible</span><?php endif; ?>
                      <?php if ((int) $image['image_principale'] === 1): ?><span class="admin-primary-image-badge"><i class="bi bi-star-fill" aria-hidden="true"></i> Image principale</span><?php endif; ?>
                    </div>
                    <div class="admin-image-card-body"><strong title="<?php echo grinco_admin_escape(basename($image['image'])); ?>"><?php echo grinco_admin_escape(basename($image['image'])); ?></strong><div class="admin-image-actions">
                      <?php if ((int) $image['image_principale'] !== 1): ?><form action="<?php echo grinco_url_html('/admin/images-documents.php?produit_id=' . $selectedProductId . '&tab=images'); ?>" method="POST"><input type="hidden" name="media_action" value="set_primary_image"><input type="hidden" name="produit_id" value="<?php echo $selectedProductId; ?>"><input type="hidden" name="image_id" value="<?php echo (int) $image['id']; ?>"><input type="hidden" name="return_tab" value="images"><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($mediaCsrfToken); ?>"><button type="submit" class="admin-icon-button is-primary" title="Définir comme image principale" aria-label="Définir cette image comme image principale"><i class="bi bi-star" aria-hidden="true"></i></button></form><?php endif; ?>
                      <button type="button" class="admin-icon-button is-delete" title="Supprimer l’image" aria-label="Supprimer l’image <?php echo grinco_admin_escape(basename($image['image'])); ?>" data-image-delete data-image-id="<?php echo (int) $image['id']; ?>" data-image-name="<?php echo grinco_admin_escape(basename($image['image'])); ?>" data-bs-toggle="modal" data-bs-target="#delete-image-modal"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </div></div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="tab-pane fade<?php echo $activeTab === 'documents' ? ' show active' : ''; ?>" id="documents-panel" role="tabpanel" aria-labelledby="documents-tab" tabindex="0">
            <div class="admin-media-panel-heading"><div><h2>Documents du produit</h2><p>PDF uniquement — 2 Mo maximum par fichier, 10 documents par envoi.</p></div><?php if ($selectedProduct): ?><button type="button" class="admin-primary-button" data-bs-toggle="modal" data-bs-target="#upload-documents-modal"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Ajouter un document</button><?php endif; ?></div>
            <?php if (!$selectedProduct): ?>
              <div class="admin-media-empty"><i class="bi bi-box-seam" aria-hidden="true"></i><strong>Sélectionnez d’abord un produit</strong><span>Ses documents apparaîtront dans cet onglet.</span></div>
            <?php elseif (empty($documents)): ?>
              <div class="admin-media-empty"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i><strong>Aucun document enregistré</strong><span>Ajoutez le premier document PDF de ce produit.</span></div>
            <?php else: ?>
              <div class="admin-table-responsive"><table class="admin-data-table admin-documents-table"><thead><tr><th scope="col">N°</th><th scope="col">Nom du document</th><th scope="col">Produit</th><th scope="col">Type</th><th scope="col" class="admin-actions-column">Actions</th></tr></thead><tbody>
                <?php foreach ($documents as $index => $document): ?><tr><td data-label="N°"><?php echo $index + 1; ?></td><td data-label="Nom du document"><span class="admin-document-name"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i><strong><?php echo grinco_admin_escape(basename($document['document'])); ?></strong></span></td><td data-label="Produit"><?php echo grinco_admin_escape($document['reference'] . ' — ' . $document['produit_nom']); ?></td><td data-label="Type"><span class="admin-file-type-badge">PDF</span></td><td data-label="Actions" class="admin-actions-cell"><a class="admin-icon-button is-download" href="<?php echo grinco_url_html('/admin/images-documents.php?telecharger_document=' . (int) $document['id']); ?>" title="Télécharger le document" aria-label="Télécharger le document <?php echo grinco_admin_escape(basename($document['document'])); ?>"><i class="bi bi-download" aria-hidden="true"></i></a><button type="button" class="admin-icon-button is-delete" title="Supprimer le document" aria-label="Supprimer le document <?php echo grinco_admin_escape(basename($document['document'])); ?>" data-document-delete data-document-id="<?php echo (int) $document['id']; ?>" data-document-name="<?php echo grinco_admin_escape(basename($document['document'])); ?>" data-bs-toggle="modal" data-bs-target="#delete-document-modal"><i class="bi bi-trash" aria-hidden="true"></i></button></td></tr><?php endforeach; ?>
              </tbody></table></div>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </main>
    <?php include dirname(__DIR__) . '/includes/admin/footer.php'; ?>
  </div>
</div>

<?php if ($selectedProduct): ?>
<div class="modal fade admin-modal" id="upload-images-modal" tabindex="-1" aria-labelledby="upload-images-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/images-documents.php?produit_id=' . $selectedProductId . '&tab=images'); ?>" method="POST" enctype="multipart/form-data">
  <div class="modal-header"><div><span class="admin-modal-eyebrow">Images produit</span><h2 class="modal-title" id="upload-images-title">Ajouter des images</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
  <div class="modal-body"><input type="hidden" name="media_action" value="upload_images"><input type="hidden" name="produit_id" value="<?php echo $selectedProductId; ?>"><input type="hidden" name="return_tab" value="images"><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($mediaCsrfToken); ?>"><input type="hidden" name="MAX_FILE_SIZE" value="2097152">
    <div class="admin-selected-product"><i class="bi bi-box-seam" aria-hidden="true"></i><span><small>Produit sélectionné</small><strong><?php echo grinco_admin_escape($selectedProduct['reference'] . ' — ' . $selectedProduct['nom']); ?></strong></span></div>
    <div class="admin-form-field"><label for="media-images-input">Fichiers images <span aria-hidden="true">*</span></label><input type="file" id="media-images-input" name="images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple required data-image-files><small>Maximum 10 images, 2 Mo par fichier.</small></div>
    <div class="admin-form-field"><label for="media-primary-index">Image principale</label><select id="media-primary-index" name="principal_index" data-primary-image-select><option value="">Automatique</option></select><small>La première image devient automatiquement principale si le produit n’en possède aucune.</small></div>
  </div><div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-primary-button">Enregistrer les images</button></div>
</form></div></div></div>

<div class="modal fade admin-modal" id="upload-documents-modal" tabindex="-1" aria-labelledby="upload-documents-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/images-documents.php?produit_id=' . $selectedProductId . '&tab=documents'); ?>" method="POST" enctype="multipart/form-data">
  <div class="modal-header"><div><span class="admin-modal-eyebrow">Documents produit</span><h2 class="modal-title" id="upload-documents-title">Ajouter un document</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
  <div class="modal-body"><input type="hidden" name="media_action" value="upload_documents"><input type="hidden" name="produit_id" value="<?php echo $selectedProductId; ?>"><input type="hidden" name="return_tab" value="documents"><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($mediaCsrfToken); ?>"><input type="hidden" name="MAX_FILE_SIZE" value="2097152">
    <div class="admin-selected-product"><i class="bi bi-box-seam" aria-hidden="true"></i><span><small>Produit sélectionné</small><strong><?php echo grinco_admin_escape($selectedProduct['reference'] . ' — ' . $selectedProduct['nom']); ?></strong></span></div>
    <div class="admin-form-field"><label for="media-documents-input">Documents PDF <span aria-hidden="true">*</span></label><input type="file" id="media-documents-input" name="documents[]" accept=".pdf,application/pdf" multiple required><small>PDF uniquement, maximum 10 documents et 2 Mo par fichier.</small></div>
  </div><div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-primary-button">Enregistrer les documents</button></div>
</form></div></div></div>

<div class="modal fade admin-modal" id="delete-image-modal" tabindex="-1" aria-labelledby="delete-image-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/images-documents.php?produit_id=' . $selectedProductId . '&tab=images'); ?>" method="POST"><div class="modal-header"><h2 class="modal-title" id="delete-image-title">Supprimer l’image</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div><div class="modal-body admin-delete-confirmation"><input type="hidden" name="media_action" value="delete_image"><input type="hidden" name="produit_id" value="<?php echo $selectedProductId; ?>"><input type="hidden" name="image_id" id="delete-image-id" value=""><input type="hidden" name="return_tab" value="images"><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($mediaCsrfToken); ?>"><span class="admin-delete-icon" aria-hidden="true"><i class="bi bi-trash"></i></span><p>Supprimer définitivement <strong id="delete-image-name"></strong> ?</p><small>Le fichier physique sera également supprimé.</small></div><div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-danger-button">Supprimer</button></div></form></div></div></div>

<div class="modal fade admin-modal" id="delete-document-modal" tabindex="-1" aria-labelledby="delete-document-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><form action="<?php echo grinco_url_html('/admin/images-documents.php?produit_id=' . $selectedProductId . '&tab=documents'); ?>" method="POST"><div class="modal-header"><h2 class="modal-title" id="delete-document-title">Supprimer le document</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div><div class="modal-body admin-delete-confirmation"><input type="hidden" name="media_action" value="delete_document"><input type="hidden" name="produit_id" value="<?php echo $selectedProductId; ?>"><input type="hidden" name="document_id" id="delete-document-id" value=""><input type="hidden" name="return_tab" value="documents"><input type="hidden" name="csrf_token" value="<?php echo grinco_admin_escape($mediaCsrfToken); ?>"><span class="admin-delete-icon" aria-hidden="true"><i class="bi bi-trash"></i></span><p>Supprimer définitivement <strong id="delete-document-name"></strong> ?</p><small>Le fichier physique sera également supprimé.</small></div><div class="modal-footer"><button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Annuler</button><button type="submit" class="admin-danger-button">Supprimer</button></div></form></div></div></div>
<?php endif; ?>

<?php include dirname(__DIR__) . '/includes/admin/scripts.php'; ?>
