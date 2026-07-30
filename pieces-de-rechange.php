<?php
$pageTitle = 'Pièces de rechange';
$pageDescription = 'Pièces de rechange et composants pour véhicules et engins proposés par GRINCO RDC.';
$currentPage = 'pieces-de-rechange';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0">Pièces de rechange</h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li><li><a href="<?php echo grinco_url_html('/catalogue'); ?>">Catalogue</a></li><li class="current">Pièces de rechange</li></ol></nav></div></div>
  <section class="starter-section section"><div class="container section-title" data-aos="fade-up"><h2>Pièces et composants</h2><p>Les pièces de rechange seront chargées automatiquement depuis la future base de données et organisées par marque, équipement et référence.</p></div><div class="container text-center"><i class="bi bi-tools display-1 text-success"></i><p class="mt-3">Aucun produit ni prix provisoire n’est affiché en dur. Le catalogue sera alimenté automatiquement à partir de la base de données.</p><a href="<?php echo grinco_url_html('/demande-devis'); ?>" class="btn btn-primary">Demander une pièce</a></div></section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
