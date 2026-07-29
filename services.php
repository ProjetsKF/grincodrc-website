<?php
$pageTitle = 'Services';
$pageDescription = 'Découvrez les neuf domaines d’activité de GRINCO RDC : ingénierie, maintenance, énergie, logistique, conseil, formation et solutions techniques.';
$currentPage = 'services';

$services = array(
  array(
    'slug' => 'ingenierie-construction',
    'title' => 'Ingénierie et construction',
    'image' => 'assets/img/about/ingenierie.png',
    'alt' => 'Équipe d’ingénierie et chantier de construction GRINCO RDC',
    'description' => 'Des solutions d’étude, de conception et de réalisation adaptées aux projets de construction.',
    'points' => array('Génie civil', 'Construction industrielle', 'Routes et topographie')
  ),
  array(
    'slug' => 'maintenance-industrielle',
    'title' => 'Maintenance industrielle',
    'image' => 'assets/img/about/ChatGPT%20Image%2029%20juil.%202026%2C%2011_07_31.png',
    'alt' => 'Techniciens GRINCO RDC intervenant sur un équipement industriel',
    'description' => 'Une approche multidisciplinaire pour maintenir les équipements et systèmes techniques en état de fonctionnement.',
    'points' => array('Maintenance mécanique', 'Maintenance électrique', 'Automatisation industrielle')
  ),
  array(
    'slug' => 'energie-installations-electriques',
    'title' => 'Énergie et installations électriques',
    'image' => 'assets/img/about/electricite.png',
    'alt' => 'Installation électrique et solutions énergétiques',
    'description' => 'Étude et mise en œuvre d’installations électriques industrielles, domestiques et énergétiques.',
    'points' => array('Courant fort', 'Courant faible', 'Énergies renouvelables')
  ),
  array(
    'slug' => 'commerce-import-export-logistique',
    'title' => 'Commerce, import-export et logistique',
    'image' => 'assets/img/about/import.png',
    'alt' => 'Opérations de commerce international, importation et logistique',
    'description' => 'Un accompagnement structuré pour l’approvisionnement, l’importation, le transport et la livraison.',
    'points' => array('Import-export', 'Transport et logistique', 'Fourniture d’équipements')
  ),
  array(
    'slug' => 'conseil-expertise',
    'title' => 'Conseil et expertise',
    'image' => 'assets/img/about/conseil_expertise.png',
    'alt' => 'Réunion de conseil et d’expertise technique',
    'description' => 'Des analyses et recommandations techniques pour sécuriser les décisions et optimiser les systèmes.',
    'points' => array('Études techniques', 'Gestion des risques', 'Accompagnement stratégique')
  ),
  array(
    'slug' => 'formation-technique',
    'title' => 'Formation technique',
    'image' => 'assets/img/about/formation.png',
    'alt' => 'Session de formation technique professionnelle',
    'description' => 'Des formations pratiques destinées au développement et au renforcement des compétences techniques.',
    'points' => array('Maintenance industrielle', 'Sécurité', 'Électricité et automatisation')
  ),
  array(
    'slug' => 'agro-industrie',
    'title' => 'Agro-industrie et activités agropastorales',
    'image' => 'assets/img/about/agriculture.png',
    'alt' => 'Activités agricoles, agropastorales et agro-industrielles',
    'description' => 'Un accompagnement des activités de production, de transformation et de valorisation des ressources.',
    'points' => array('Production', 'Transformation', 'Activités agropastorales')
  ),
  array(
    'slug' => 'telecommunications',
    'title' => 'Télécommunications',
    'image' => 'assets/img/about/telecommunication.png',
    'alt' => 'Infrastructure de télécommunications et solutions de connectivité',
    'description' => 'Des solutions pour les réseaux, les installations techniques et la maintenance des équipements.',
    'points' => array('Réseaux', 'Téléphonie mobile', 'Solutions de connectivité')
  ),
  array(
    'slug' => 'evaluation-suivi-projets',
    'title' => 'Évaluation et suivi de projets',
    'image' => 'assets/img/about/consultance.png',
    'alt' => 'Équipe GRINCO RDC étudiant et suivant un projet technique',
    'description' => 'Une démarche méthodique pour analyser, planifier, suivre et évaluer l’exécution des projets.',
    'points' => array('Étude de faisabilité', 'Suivi d’exécution', 'Évaluation des résultats')
  )
);

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<main class="main">
  <div class="page-title light-background">
    <div class="container d-lg-flex justify-content-between align-items-center">
      <h1 class="mb-2 mb-lg-0">Services</h1>
      <nav class="breadcrumbs" aria-label="Fil d’Ariane">
        <ol>
          <li><a href="index.php">Accueil</a></li>
          <li class="current">Services</li>
        </ol>
      </nav>
    </div>
  </div>

  <section id="services" class="services section">
    <div class="container section-title" data-aos="fade-up">
      <h2>Nos domaines d’activité</h2>
      <p>GRINCO RDC mobilise des compétences techniques et opérationnelles pour accompagner les entreprises, les institutions et les porteurs de projets.</p>
    </div>

    <div class="container">
      <div class="row g-4">
        <?php foreach ($services as $index => $service) { ?>
          <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo ($index % 3) * 100; ?>">
            <article class="service-item service-activity-card">
              <a href="service-details.php?service=<?php echo htmlspecialchars($service['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="service-activity-image" aria-label="Découvrir le service <?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($service['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($service['alt'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
              </a>
              <div class="service-activity-body">
                <h3><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                <ul class="service-points">
                  <?php foreach ($service['points'] as $point) { ?>
                    <li><i class="bi bi-check-circle" aria-hidden="true"></i><?php echo htmlspecialchars($point, ENT_QUOTES, 'UTF-8'); ?></li>
                  <?php } ?>
                </ul>
                <a href="service-details.php?service=<?php echo htmlspecialchars($service['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="read-more">
                  En savoir plus <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
              </div>
            </article>
          </div>
        <?php } ?>
      </div>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
