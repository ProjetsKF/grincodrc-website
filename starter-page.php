<?php
$pageTitle = 'Page de base';
$pageDescription = 'Gabarit interne GRINCO RDC.';
$currentPage = '';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>

<main class="main">
  <div class="page-title light-background">
    <div class="container d-lg-flex justify-content-between align-items-center">
      <h1 class="mb-2 mb-lg-0">Page de base</h1>
      <nav class="breadcrumbs" aria-label="Fil d’Ariane">
        <ol><li><a href="index.php">Accueil</a></li><li class="current">Page de base</li></ol>
      </nav>
    </div>
  </div>
  <section class="starter-section section">
    <div class="container section-title" data-aos="fade-up">
      <h2>Section de départ</h2>
      <p>Ce gabarit sert de base aux nouvelles pages internes GRINCO RDC.</p>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
