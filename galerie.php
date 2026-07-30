<?php
$pageTitle = 'Galerie';
$pageDescription = 'Future galerie des véhicules, équipements, préparations, expéditions et livraisons de GRINCO RDC.';
$currentPage = 'galerie';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0">Galerie</h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li><li class="current">Galerie</li></ol></nav></div></div>
  <section class="starter-section section">
    <div class="container section-title" data-aos="fade-up"><h2>Galeries GRINCO RDC</h2><p>Les futures galeries présenteront les camions, les semi-remorques, les engins de chantier, les véhicules particuliers, les visites d’usine, les préparations de commandes, les expéditions et les livraisons.</p></div>
    <div class="container"><div class="row g-4">
      <div class="col-md-4"><img src="assets/img/about/about-15.webp" class="img-fluid rounded" alt="Emplacement visuel pour les futures galeries GRINCO RDC" loading="lazy"></div>
      <div class="col-md-4"><img src="assets/img/services/services-6.webp" class="img-fluid rounded" alt="Emplacement visuel pour les futures galeries GRINCO RDC" loading="lazy"></div>
      <div class="col-md-4"><img src="assets/img/services/services-8.webp" class="img-fluid rounded" alt="Emplacement visuel pour les futures galeries GRINCO RDC" loading="lazy"></div>
    </div></div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
