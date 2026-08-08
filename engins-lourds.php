<?php
$catalogueCategoryName = 'Engins lourds';
$cataloguePagePath = '/engins-lourds';
require __DIR__ . '/includes/category-catalogue-loader.php';
$pageTitle = 'Engins lourds';
$pageDescription = 'Catalogue dynamique des engins de chantier et équipements lourds proposés par GRINCO RDC.';
$currentPage = 'engins-lourds';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0">Engins lourds</h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li><li><a href="<?php echo grinco_url_html('/catalogue'); ?>">Catalogue</a></li><li class="current">Engins lourds</li></ol></nav></div></div>
  <section class="catalogue-products section"><div class="container section-title" data-aos="fade-up"><h2>Engins de chantier et équipements lourds</h2><p>Découvrez les engins lourds actuellement disponibles auprès de GRINCO RDC.</p></div><div class="container"><?php include __DIR__ . '/includes/catalogue-grid.php'; ?></div></section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
