<?php
$pageTitle = 'Comment commander';
$pageDescription = 'Découvrez les étapes prévues pour commander un véhicule, un engin ou une pièce auprès de GRINCO RDC.';
$currentPage = 'comment-commander';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background"><div class="container d-lg-flex justify-content-between align-items-center"><h1 class="mb-2 mb-lg-0">Comment commander</h1><nav class="breadcrumbs" aria-label="Fil d’Ariane"><ol><li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li><li class="current">Comment commander</li></ol></nav></div></div>
  <section id="how-we-work" class="how-we-work section">
    <div class="container section-title" data-aos="fade-up"><h2>Notre processus d’achat</h2><p>GRINCO accompagne le client depuis la demande de devis jusqu’à la livraison et au suivi convenu.</p></div>
    <div class="container"><div class="row g-4">
      <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">01</div><h3>Demande de devis</h3><p>Le client transmet son besoin et les informations principales sur le produit recherché.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">02</div><h3>Analyse du besoin</h3><p>L’équipe GRINCO analyse l’utilisation prévue, la capacité, la puissance, les dimensions, la configuration et les conditions du terrain.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">03</div><h3>Proposition technique et financière</h3><p>Une offre adaptée est préparée selon les caractéristiques demandées.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">04</div><h3>Validation</h3><p>Le client valide le modèle, les spécifications, le prix et les conditions de commande.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">05</div><h3>Fabrication ou préparation</h3><p>Le fabricant prépare ou configure le véhicule ou l’équipement selon la commande.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">06</div><h3>Expédition</h3><p>L’équipement est expédié depuis la Chine selon les conditions logistiques convenues.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">07</div><h3>Livraison et suivi</h3><p>GRINCO accompagne le client jusqu’à la livraison et assure le suivi convenu.</p></div></div>
    </div></div>
  </section>
  <section id="faq" class="faq section light-background">
    <div class="container section-title" data-aos="fade-up"><h2>Questions fréquentes</h2><p>Les caractéristiques et les conditions de chaque commande sont définies à partir du besoin validé avec le client.</p></div>
    <div class="container"><div class="faq-container">
      <div class="faq-item faq-active"><h3>Comment transmettre une demande ?</h3><div class="faq-content"><p>La page de demande de devis présente les principales informations à communiquer à l’équipe GRINCO.</p></div><i class="faq-toggle bi bi-chevron-right"></i></div>
      <div class="faq-item"><h3>Quels produits peuvent être demandés ?</h3><div class="faq-content"><p>Camions, semi-remorques, engins lourds, véhicules particuliers et pièces de rechange.</p></div><i class="faq-toggle bi bi-chevron-right"></i></div>
      <div class="faq-item"><h3>Comment les caractéristiques sont-elles définies ?</h3><div class="faq-content"><p>La capacité, la puissance, les dimensions, la configuration, l’utilisation prévue et les conditions du terrain sont analysées avant la préparation de l’offre.</p></div><i class="faq-toggle bi bi-chevron-right"></i></div>
    </div></div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
