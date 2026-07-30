<?php
$pageTitle = 'Accueil';
$pageDescription = 'GRINCO RDC propose des camions, semi-remorques, engins lourds, véhicules particuliers, pièces de rechange et services d’ingénierie.';
$currentPage = 'accueil';
$bodyClass = 'index-page';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>

<main class="main">
  <section id="hero" class="hero section dark-background">
    <video class="hero-background-video" autoplay muted loop playsinline preload="metadata" poster="assets/img/about/index.jpg" aria-hidden="true">
      <source src="assets/video/hero1.mp4" type="video/mp4">
    </video>
    <div class="hero-video-overlay" aria-hidden="true"></div>

    <div class="container">
      <div class="row align-items-center gy-5">
        <div class="col-lg-7" data-aos="fade-right" data-aos-delay="100">
          <div class="hero-content">
            <p class="hero-eyebrow">Mobilité, équipements et ingénierie en RDC</p>
            <h1>Des solutions fiables pour <span class="highlight">faire avancer vos projets</span></h1>
            <p class="hero-description">GRINCO RDC accompagne les entreprises et les particuliers dans l’acquisition de véhicules, d’engins lourds et de pièces de rechange, en partenariat avec ZW GROUP.</p>
            <div class="hero-actions">
              <a href="<?php echo grinco_url_html('/catalogue'); ?>" class="btn-hero-primary">Explorer le catalogue</a>
              <a href="<?php echo grinco_url_html('/demande-devis'); ?>" class="btn-hero-secondary"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Demander un devis</a>
            </div>
          </div>
        </div>
        <div class="col-lg-5" data-aos="fade-left" data-aos-delay="200">
          <div class="hero-visual">
            <img src="assets/img/about/index.jpg" alt="Présentation des solutions GRINCO RDC" class="img-fluid hero-image">

            <article class="stat-card top-right" data-aos="zoom-in" data-aos-delay="350">
              <i class="bi bi-globe2 stat-icon" aria-hidden="true"></i>
              <strong class="stat-title">Importation directe</strong>
              <span class="stat-subtitle">Depuis la Chine</span>
              <p class="stat-description">Des solutions adaptées<br>aux besoins des entreprises</p>
            </article>

            <article class="stat-card bottom-left" data-aos="zoom-in" data-aos-delay="450">
              <i class="bi bi-truck stat-icon" aria-hidden="true"></i>
              <strong class="stat-title">Nos secteurs</strong>
              <ul class="stat-sectors">
                <li>Mines</li>
                <li>BTP</li>
                <li>Transport</li>
                <li>Industrie</li>
              </ul>
            </article>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="about" class="about section">
    <div class="container">
      <div class="row justify-content-center mb-5">
        <div class="col-lg-8 text-center" data-aos="fade-up">
          <h2 class="section-heading">Notre vision &amp; nos engagements</h2>
          <p class="lead">Une expertise locale, des solutions fiables et un accompagnement durable au service des projets en RDC.</p>
        </div>
      </div>

      <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
          <article class="feature-box">
            <div class="icon-container"><i class="bi bi-rulers" aria-hidden="true"></i></div>
            <h4>Ingénierie</h4>
            <p>Concevoir des solutions techniques fiables et innovantes adaptées aux réalités de la RDC.</p>
          </article>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
          <article class="feature-box">
            <div class="icon-container"><i class="bi bi-patch-check" aria-hidden="true"></i></div>
            <h4>Qualité</h4>
            <p>Garantir des équipements, des matériaux et des prestations répondant aux standards internationaux.</p>
          </article>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
          <article class="feature-box">
            <div class="icon-container"><i class="bi bi-people" aria-hidden="true"></i></div>
            <h4>Partenariat</h4>
            <p>Développer des relations durables avec nos partenaires, notamment ZW GROUP et nos clients.</p>
          </article>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
          <article class="feature-box">
            <div class="icon-container"><i class="bi bi-person-check" aria-hidden="true"></i></div>
            <h4>Satisfaction client</h4>
            <p>Accompagner chaque client depuis l’étude du besoin jusqu’à la livraison du projet.</p>
          </article>
        </div>
      </div>

      <div class="row align-items-center gy-5">
        <div class="col-lg-6" data-aos="fade-right" data-aos-delay="100">
          <div class="about-content-box">
            <h3>Des solutions intégrées pour vos projets</h3>
            <p>GRINCO RDC accompagne les entreprises, les industries, les sociétés minières et les particuliers dans leurs projets d’ingénierie, de construction, d’importation d’équipements, de maintenance industrielle et de logistique grâce à son partenariat avec ZW GROUP.</p>

            <div class="expertise-list" aria-label="Domaines d’expertise">
              <!-- Valeurs provisoires : modifier uniquement les attributs data-progress. -->
              <div class="progress-item" data-progress="90">
                <div class="progress-header">
                  <span class="progress-title" id="progress-engineering-title">Ingénierie et construction</span>
                  <span class="progress-percent" aria-hidden="true">0%</span>
                </div>
                <div class="progress">
                  <div class="progress-bar" role="progressbar" aria-labelledby="progress-engineering-title" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                </div>
              </div>
              <div class="progress-item" data-progress="85">
                <div class="progress-header">
                  <span class="progress-title" id="progress-import-title">Importation d’équipements</span>
                  <span class="progress-percent" aria-hidden="true">0%</span>
                </div>
                <div class="progress">
                  <div class="progress-bar" role="progressbar" aria-labelledby="progress-import-title" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                </div>
              </div>
              <div class="progress-item" data-progress="80">
                <div class="progress-header">
                  <span class="progress-title" id="progress-maintenance-title">Maintenance industrielle</span>
                  <span class="progress-percent" aria-hidden="true">0%</span>
                </div>
                <div class="progress">
                  <div class="progress-bar" role="progressbar" aria-labelledby="progress-maintenance-title" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                </div>
              </div>
            </div>

            <a href="<?php echo grinco_url_html('/a-propos'); ?>" class="btn btn-discover">Découvrir GRINCO</a>
          </div>
        </div>

        <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
          <div class="about-image-grid">
            <img src="assets/img/about/about-15.png" class="img-grid-main" alt="Équipe GRINCO RDC étudiant un projet" loading="lazy">
            <img src="assets/img/about/ingenierie.png" class="img-grid-secondary" alt="Expertise en ingénierie de GRINCO RDC" loading="lazy">
            <img src="assets/img/about/import.png" class="img-grid-tertiary" alt="Solutions d’importation d’équipements GRINCO RDC" loading="lazy">
            <div class="experience-badge partner-badge" aria-label="Partenaire officiel ZW GROUP">
              <span class="partner-label">Partenaire officiel</span>
              <span class="partner-name">ZW GROUP</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="stats" class="stats section light-background">
    <div class="container">
      <div class="row align-items-center gy-4">
        <div class="col-lg-5" data-aos="fade-right">
          <div class="stats-overview">
            <h3>Nos domaines d’intervention</h3>
            <p>GRINCO RDC met à disposition des entreprises, des industries, des sociétés minières et des particuliers une offre intégrée couvrant l’importation d’équipements, l’ingénierie, la construction, la maintenance industrielle et la logistique.</p>
          </div>
        </div>

        <div class="col-lg-7" data-aos="fade-left" data-aos-delay="100">
          <div class="stats-grid">
            <article class="stats-card">
              <div class="stats-icon"><i class="bi bi-globe" aria-hidden="true"></i></div>
              <div class="stats-content">
                <h4>Importation directe</h4>
                <p>Depuis la Chine</p>
              </div>
            </article>

            <article class="stats-card">
              <div class="stats-icon"><i class="bi bi-truck" aria-hidden="true"></i></div>
              <div class="stats-content">
                <h4>Marques proposées</h4>
                <ul class="stats-details">
                  <li>HOWO</li>
                  <li>SHACMAN</li>
                  <li>FAW</li>
                  <li>FOTON</li>
                </ul>
              </div>
            </article>

            <article class="stats-card">
              <div class="stats-icon"><i class="bi bi-buildings" aria-hidden="true"></i></div>
              <div class="stats-content">
                <h4>Secteurs desservis</h4>
                <ul class="stats-details">
                  <li>Mines</li>
                  <li>BTP</li>
                  <li>Transport</li>
                  <li>Industrie</li>
                </ul>
              </div>
            </article>

            <article class="stats-card">
              <div class="stats-icon"><i class="bi bi-gear" aria-hidden="true"></i></div>
              <div class="stats-content">
                <h4>Services</h4>
                <ul class="stats-details">
                  <li>Ingénierie</li>
                  <li>Maintenance</li>
                  <li>Construction</li>
                  <li>Logistique</li>
                </ul>
              </div>
            </article>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="categories" class="services section light-background">
    <div class="container section-title" data-aos="fade-up">
      <h2>Nos catégories principales</h2>
      <p>Le catalogue sera alimenté automatiquement à partir de la future base de données, selon les catégories proposées par GRINCO RDC.</p>
    </div>
    <div class="container">
      <div class="row g-4">
        <div class="col-md-6 col-lg-4" data-aos="fade-up"><div class="service-item"><div class="service-icon"><i class="bi bi-truck"></i></div><h3>Camions</h3><p>Solutions de transport professionnel adaptées aux opérations exigeantes.</p><a href="<?php echo grinco_url_html('/camions'); ?>" class="read-more">Voir la catégorie <i class="bi bi-arrow-right"></i></a></div></div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100"><div class="service-item"><div class="service-icon"><i class="bi bi-box-seam"></i></div><h3>Semi-remorques</h3><p>Équipements destinés au transport de charges et de marchandises.</p><a href="<?php echo grinco_url_html('/semi-remorques'); ?>" class="read-more">Voir la catégorie <i class="bi bi-arrow-right"></i></a></div></div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200"><div class="service-item"><div class="service-icon"><i class="bi bi-gear-wide-connected"></i></div><h3>Engins lourds</h3><p>Engins destinés aux chantiers, aux mines et aux travaux d’infrastructure.</p><a href="<?php echo grinco_url_html('/engins-lourds'); ?>" class="read-more">Voir la catégorie <i class="bi bi-arrow-right"></i></a></div></div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up"><div class="service-item"><div class="service-icon"><i class="bi bi-car-front"></i></div><h3>Véhicules particuliers</h3><p>Véhicules pour les déplacements professionnels et privés.</p><a href="<?php echo grinco_url_html('/vehicules-particuliers'); ?>" class="read-more">Voir la catégorie <i class="bi bi-arrow-right"></i></a></div></div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100"><div class="service-item"><div class="service-icon"><i class="bi bi-tools"></i></div><h3>Pièces de rechange</h3><p>Pièces et composants pour l’entretien de vos équipements.</p><a href="<?php echo grinco_url_html('/pieces-de-rechange'); ?>" class="read-more">Voir la catégorie <i class="bi bi-arrow-right"></i></a></div></div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200"><div class="service-item"><div class="service-icon"><i class="bi bi-diagram-3"></i></div><h3>Ingénierie</h3><p>Accompagnement technique et solutions adaptées aux projets.</p><a href="<?php echo grinco_url_html('/services'); ?>" class="read-more">Découvrir les services <i class="bi bi-arrow-right"></i></a></div></div>
      </div>
    </div>
  </section>

  <section id="featured" class="services section">
    <div class="container section-title" data-aos="fade-up">
      <h2>Accompagnement commercial et technique</h2>
      <p>GRINCO RDC accompagne chaque demande depuis l’analyse du besoin jusqu’au suivi convenu après la livraison.</p>
    </div>
    <div class="container">
      <div class="row align-items-center gy-4">
        <div class="col-lg-6"><img src="assets/img/services/services-12.png" class="img-fluid rounded" alt="Sélection de produits et services GRINCO RDC" loading="lazy"></div>
        <div class="col-lg-6">
          <h3>Une proposition adaptée à votre besoin</h3>
          <p>Notre équipe conseille le client dans le choix des véhicules et équipements, analyse les caractéristiques techniques, prépare une cotation personnalisée et accompagne l’importation, le transport, la commande et la livraison.</p>
          <a href="<?php echo grinco_url_html('/catalogue'); ?>" class="btn btn-primary">Consulter le catalogue</a>
        </div>
      </div>
    </div>
  </section>

 
  <section id="process" class="how-we-work section">
    <div class="container section-title" data-aos="fade-up">
      <h2>Comment commander</h2>
      <p>Un parcours simple, de l’expression du besoin à la livraison.</p>
    </div>
    <div class="container">
      <div class="row g-4">
        <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">01</div><h3>Demande et analyse</h3><p>Le client transmet son besoin ; GRINCO analyse l’utilisation, la capacité, la puissance, les dimensions, la configuration et le terrain.</p></div></div>
        <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">02</div><h3>Proposition</h3><p>Une proposition technique et financière adaptée est préparée selon les caractéristiques demandées.</p></div></div>
        <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">03</div><h3>Validation et préparation</h3><p>Le client valide les spécifications et les conditions, puis le fabricant prépare ou configure l’équipement.</p></div></div>
        <div class="col-md-6 col-lg-3"><div class="step-card"><div class="step-number">04</div><h3>Expédition et livraison</h3><p>L’équipement est expédié depuis la Chine et GRINCO accompagne le client jusqu’à la livraison et au suivi convenu.</p></div></div>
      </div>
      <div class="text-center mt-4"><a href="<?php echo grinco_url_html('/comment-commander'); ?>" class="btn btn-primary">Voir le processus complet</a></div>
    </div>
  </section>

  <section id="call-to-action" class="call-to-action section light-background">
    <div class="container" data-aos="zoom-in">
      <div class="row align-items-center">
        <div class="col-lg-8"><h2>Parlons de votre prochain projet</h2><p>Transmettez votre besoin à l’équipe GRINCO RDC pour recevoir une proposition technique et financière adaptée.</p></div>
        <div class="col-lg-4 text-lg-end"><a href="<?php echo grinco_url_html('/demande-devis'); ?>" class="btn-primary">Demander un devis</a></div>
      </div>
    </div>
  </section>

</main>

<?php
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/includes/scripts.php';
?>
