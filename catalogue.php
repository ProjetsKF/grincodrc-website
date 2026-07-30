<?php
$pageTitle = 'Catalogue';
$pageDescription = 'Parcourez les principales catégories de véhicules, engins et pièces proposées par GRINCO RDC.';
$currentPage = 'catalogue';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0">Catalogue</h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li><li class="current">Catalogue</li></ol></nav></div></div>
  <section class="services section">
    <div class="container section-title" data-aos="fade-up"><h2>Nos catégories</h2><p>Le catalogue sera alimenté automatiquement à partir de la base de données. Les produits, modèles, caractéristiques, disponibilités et filtres ne sont pas encore connectés.</p></div>
    <div class="container"><div class="row g-4">
      <div class="col-md-6 col-lg-4"><div class="service-item"><div class="service-icon"><i class="bi bi-truck"></i></div><h3>Camions</h3><a href="<?php echo grinco_url_html('/camions'); ?>" class="read-more">Découvrir <i class="bi bi-arrow-right"></i></a></div></div>
      <div class="col-md-6 col-lg-4"><div class="service-item"><div class="service-icon"><i class="bi bi-box-seam"></i></div><h3>Semi-remorques</h3><a href="<?php echo grinco_url_html('/semi-remorques'); ?>" class="read-more">Découvrir <i class="bi bi-arrow-right"></i></a></div></div>
      <div class="col-md-6 col-lg-4"><div class="service-item"><div class="service-icon"><i class="bi bi-gear-wide-connected"></i></div><h3>Engins lourds</h3><a href="<?php echo grinco_url_html('/engins-lourds'); ?>" class="read-more">Découvrir <i class="bi bi-arrow-right"></i></a></div></div>
      <div class="col-md-6 col-lg-4"><div class="service-item"><div class="service-icon"><i class="bi bi-car-front"></i></div><h3>Véhicules particuliers</h3><a href="<?php echo grinco_url_html('/vehicules-particuliers'); ?>" class="read-more">Découvrir <i class="bi bi-arrow-right"></i></a></div></div>
      <div class="col-md-6 col-lg-4"><div class="service-item"><div class="service-icon"><i class="bi bi-tools"></i></div><h3>Pièces de rechange</h3><a href="<?php echo grinco_url_html('/pieces-de-rechange'); ?>" class="read-more">Découvrir <i class="bi bi-arrow-right"></i></a></div></div>
      <div class="col-md-6 col-lg-4"><div class="service-item"><div class="service-icon"><i class="bi bi-file-earmark-text"></i></div><h3>Besoin spécifique ?</h3><a href="<?php echo grinco_url_html('/demande-devis'); ?>" class="read-more">Demander un devis <i class="bi bi-arrow-right"></i></a></div></div>
    </div></div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
