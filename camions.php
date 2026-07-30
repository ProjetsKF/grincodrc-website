<?php
$pageTitle = 'Camions';
$pageDescription = 'Catalogue dynamique des camions proposés par GRINCO RDC.';
$currentPage = 'camions';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0">Camions</h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li><li><a href="<?php echo grinco_url_html('/catalogue'); ?>">Catalogue</a></li><li class="current">Camions</li></ol></nav></div></div>
  <section class="starter-section section"><div class="container section-title" data-aos="fade-up"><h2>Catalogue de camions</h2><p>Les camions, leurs marques, modèles et caractéristiques seront chargés automatiquement depuis la future base de données.</p></div><div class="container text-center"><i class="bi bi-truck display-1 text-success"></i><p class="mt-3">Aucun produit n’est affiché en dur. Le catalogue sera alimenté automatiquement à partir de la base de données.</p><a href="<?php echo grinco_url_html('/demande-devis'); ?>" class="btn btn-primary">Demander un devis</a></div></section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
