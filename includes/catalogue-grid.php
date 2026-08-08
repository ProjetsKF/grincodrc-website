<?php
$catalogueRows = isset($catalogueResult['rows']) ? $catalogueResult['rows'] : array();
$catalogueSearch = isset($catalogueSearch) ? (string) $catalogueSearch : '';
$catalogueShowSearch = !empty($catalogueShowSearch);
$cataloguePagePath = isset($cataloguePagePath) ? (string) $cataloguePagePath : '/catalogue';
$catalogueEmptyTitle = isset($catalogueEmptyTitle) ? (string) $catalogueEmptyTitle : 'Aucun produit disponible';
?>
<?php if ($catalogueShowSearch): ?>
  <form class="catalogue-search" action="<?php echo grinco_url_html($cataloguePagePath); ?>" method="GET" role="search">
    <label class="visually-hidden" for="catalogue-search-input">Rechercher dans le catalogue</label>
    <input id="catalogue-search-input" type="search" name="q" value="<?php echo htmlspecialchars($catalogueSearch, ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" placeholder="Nom, référence, modèle ou marque">
    <button type="submit"><i class="bi bi-search" aria-hidden="true"></i> Rechercher</button>
    <?php if ($catalogueSearch !== ''): ?><a href="<?php echo grinco_url_html($cataloguePagePath); ?>">Effacer</a><?php endif; ?>
  </form>
<?php endif; ?>
<?php if ($catalogueError !== ''): ?>
  <div class="catalogue-alert is-error" role="alert"><?php echo htmlspecialchars($catalogueError, ENT_QUOTES, 'UTF-8'); ?></div>
<?php elseif (empty($catalogueRows)): ?>
  <div class="catalogue-empty"><i class="bi bi-box-seam" aria-hidden="true"></i><h3><?php echo htmlspecialchars($catalogueEmptyTitle, ENT_QUOTES, 'UTF-8'); ?></h3><p>Aucun produit n'est disponible actuellement dans cette catégorie. Contactez notre équipe pour une demande spécifique.</p><a class="catalogue-add-button" href="<?php echo grinco_url_html('/demande-devis'); ?>">Demander un devis</a></div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($catalogueRows as $product): ?>
      <div class="col-md-6 col-xl-4" data-aos="fade-up"><article class="catalogue-product-card">
        <a class="catalogue-product-image" href="<?php echo grinco_url_html('/produit?id=' . (int) $product['id']); ?>" aria-label="Voir les détails de <?php echo htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8'); ?>"><img src="<?php echo htmlspecialchars($product['image_url'] !== '' ? $product['image_url'] : grinco_url('/assets/img/logo.webp'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy"></a>
        <div class="catalogue-product-body">
          <div class="catalogue-product-meta"><span><?php echo htmlspecialchars($product['categorie_nom'], ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($product['marque_nom'], ENT_QUOTES, 'UTF-8'); ?></span></div>
          <small><?php echo htmlspecialchars($product['reference'], ENT_QUOTES, 'UTF-8'); ?></small>
          <h3><a href="<?php echo grinco_url_html('/produit?id=' . (int) $product['id']); ?>"><?php echo htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8'); ?></a></h3>
          <?php if ($product['modele'] !== ''): ?><p class="catalogue-product-model">Modèle : <?php echo htmlspecialchars($product['modele'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
          <?php if ($product['description'] !== ''): ?><p class="catalogue-product-description"><?php echo htmlspecialchars(grinco_catalogue_excerpt($product['description'], 150), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
          <div class="catalogue-product-actions"><a class="catalogue-detail-link" href="<?php echo grinco_url_html('/produit?id=' . (int) $product['id']); ?>">Voir les détails</a><a class="catalogue-add-button" href="<?php echo grinco_url_html('/demande-devis?produit=' . (int) $product['id']); ?>">Demander un devis</a></div>
        </div>
      </article></div>
    <?php endforeach; ?>
  </div>
  <?php if ((int) $catalogueResult['total_pages'] > 1): ?>
    <nav class="catalogue-pagination" aria-label="Pagination du catalogue"><ul>
      <?php for ($cataloguePageNumber = 1; $cataloguePageNumber <= (int) $catalogueResult['total_pages']; $cataloguePageNumber++): ?>
        <?php $catalogueQuery = '?page=' . $cataloguePageNumber . ($catalogueSearch !== '' ? '&amp;q=' . rawurlencode($catalogueSearch) : ''); ?>
        <li><?php if ($cataloguePageNumber === (int) $catalogueResult['page']): ?><span aria-current="page"><?php echo $cataloguePageNumber; ?></span><?php else: ?><a href="<?php echo grinco_url_html($cataloguePagePath); ?><?php echo $catalogueQuery; ?>"><?php echo $cataloguePageNumber; ?></a><?php endif; ?></li>
      <?php endfor; ?>
    </ul></nav>
  <?php endif; ?>
<?php endif; ?>
