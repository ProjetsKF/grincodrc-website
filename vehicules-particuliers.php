<?php
$pageTitle = 'Véhicules particuliers';
$pageDescription = 'Catalogue dynamique des véhicules particuliers proposés par GRINCO RDC.';
$currentPage = 'vehicules-particuliers';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0">Véhicules particuliers</h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="index.php">Accueil</a></li><li><a href="catalogue.php">Catalogue</a></li><li class="current">Véhicules particuliers</li></ol></nav></div></div>
  <section class="starter-section section"><div class="container section-title" data-aos="fade-up"><h2>Gamme de véhicules particuliers</h2><p>Les marques, modèles, finitions, motorisations et disponibilités seront chargés automatiquement depuis la future base de données.</p></div><div class="container text-center"><i class="bi bi-car-front display-1 text-success"></i><p class="mt-3">Aucun produit n’est affiché en dur. Le catalogue sera alimenté automatiquement à partir de la base de données.</p><a href="demande-devis.php" class="btn btn-primary">Demander un devis</a></div></section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
