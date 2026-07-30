<?php
$pageTitle = 'Partenariat GRINCO RDC et ZW GROUP';
$pageDescription = 'Découvrez le partenariat entre GRINCO RDC et ZW GROUP pour l’accès aux véhicules industriels, semi-remorques, engins et solutions de transport en République démocratique du Congo.';
$currentPage = 'partenaires';
$bodyClass = 'partner-page';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>

<main class="main">
  <section id="partner-hero" class="partner-hero">
    <img src="assets/img/services/services-12.png" class="partner-hero-image" alt="Équipe technique GRINCO RDC dans un atelier de véhicules industriels">
    <div class="partner-hero-overlay" aria-hidden="true"></div>

    <div class="container position-relative">
      <nav class="partner-breadcrumbs" aria-label="Fil d’Ariane" data-aos="fade-down">
        <ol>
          <li><a href="<?php echo grinco_url_html('/'); ?>">Accueil</a></li>
          <li aria-current="page">Partenariat</li>
        </ol>
      </nav>

      <div class="row">
        <div class="col-lg-8 col-xl-7">
          <div class="partner-hero-content" data-aos="fade-up" data-aos-delay="100">
            <span class="partner-eyebrow">Partenariat international</span>
            <h1>GRINCO RDC et <span>ZW GROUP</span></h1>
            <p>GRINCO RDC collabore avec ZW GROUP afin de faciliter l’accès aux véhicules industriels, semi-remorques, engins et solutions de transport pour les clients de la République démocratique du Congo.</p>
            <div class="partner-hero-actions">
              <a href="<?php echo grinco_url_html('/catalogue'); ?>" class="partner-btn partner-btn-primary">Découvrir les solutions <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
              <a href="<?php echo grinco_url_html('/demande-devis'); ?>" class="partner-btn partner-btn-outline">Demander un devis</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="zw-presentation" class="partner-intro section">
    <div class="container">
      <div class="row align-items-center gy-5">
        <div class="col-lg-6" data-aos="fade-right">
          <div class="partner-intro-visual">
            <img src="assets/img/about/import.png" alt="Opérations logistiques et transport d’équipements GRINCO RDC" loading="lazy">
            <div class="partner-intro-badge">
              <span>Depuis</span>
              <strong>2014</strong>
              <small>ZW GROUP</small>
            </div>
          </div>
        </div>

        <div class="col-lg-6" data-aos="fade-left" data-aos-delay="100">
          <div class="partner-intro-content">
            <span class="partner-section-label">Présentation de ZW GROUP</span>
            <h2>Un partenaire industriel international</h2>
            <p>Shandong ZW Vehicle Group Co., Ltd. est une entreprise créée en 2014 et basée à Jinan, dans la province du Shandong, en Chine. Son activité de production de semi-remorques s’appuie notamment sur le pôle industriel de Liangshan, dans la région de Jining.</p>
            <p>ZW GROUP est spécialisé dans la production, la vente et l’exportation de véhicules spéciaux, de véhicules commerciaux, de semi-remorques et de solutions de transport adaptées à plusieurs secteurs.</p>

            <ul class="partner-check-list">
              <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Production et exportation de véhicules spécialisés</span></li>
              <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Personnalisation selon les besoins du client</span></li>
              <li><i class="bi bi-check2-circle" aria-hidden="true"></i><span>Accompagnement technique, pièces et service après-vente</span></li>
            </ul>

            <div class="partner-relation-note">
              <i class="bi bi-globe2" aria-hidden="true"></i>
              <div>
                <strong>En partenariat avec ZW GROUP</strong>
                <span>Une coordination internationale au service des projets en RDC.</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="partner-solutions" class="partner-solutions section light-background">
    <div class="container section-title" data-aos="fade-up">
      <h2>Catégories de solutions</h2>
      <p>Des configurations destinées aux besoins du transport, des infrastructures, des mines, de l’industrie et des services spécialisés.</p>
    </div>

    <div class="container">
      <div class="row g-4">
        <div class="col-md-6 col-lg-4" data-aos="fade-up">
          <article class="partner-solution-card">
            <div class="partner-card-icon"><i class="bi bi-fuel-pump" aria-hidden="true"></i></div>
            <h3>Transport de carburant</h3>
            <p>Solutions de transport en citerne adaptées aux opérations énergétiques, industrielles et logistiques.</p>
            <a href="<?php echo grinco_url_html('/catalogue'); ?>">Voir les solutions <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </article>
        </div>

        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="75">
          <article class="partner-solution-card">
            <div class="partner-card-icon"><i class="bi bi-droplet" aria-hidden="true"></i></div>
            <h3>Assainissement et services municipaux</h3>
            <p>Véhicules et équipements configurables pour l’eau, l’entretien urbain et les services collectifs.</p>
            <a href="<?php echo grinco_url_html('/catalogue'); ?>">Voir les solutions <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </article>
        </div>

        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="150">
          <article class="partner-solution-card">
            <div class="partner-card-icon"><i class="bi bi-cone-striped" aria-hidden="true"></i></div>
            <h3>Transport pour la construction</h3>
            <p>Solutions pour l’acheminement de matériaux, d’équipements et de charges liés aux chantiers.</p>
            <a href="<?php echo grinco_url_html('/catalogue'); ?>">Voir les solutions <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </article>
        </div>

        <div class="col-md-6 col-lg-4" data-aos="fade-up">
          <article class="partner-solution-card">
            <div class="partner-card-icon"><i class="bi bi-gem" aria-hidden="true"></i></div>
            <h3>Transport minier</h3>
            <p>Véhicules lourds et configurations étudiées pour les contraintes des sites et opérations minières.</p>
            <a href="<?php echo grinco_url_html('/catalogue'); ?>">Voir les solutions <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </article>
        </div>

        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="75">
          <article class="partner-solution-card">
            <div class="partner-card-icon"><i class="bi bi-snow" aria-hidden="true"></i></div>
            <h3>Logistique et chaîne du froid</h3>
            <p>Solutions de transport pour les marchandises, les produits sensibles et les flux logistiques spécialisés.</p>
            <a href="<?php echo grinco_url_html('/catalogue'); ?>">Voir les solutions <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </article>
        </div>

        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="150">
          <article class="partner-solution-card">
            <div class="partner-card-icon"><i class="bi bi-tools" aria-hidden="true"></i></div>
            <h3>Assistance et dépannage</h3>
            <p>Configurations et équipements d’intervention pour soutenir les opérations sur route et sur site.</p>
            <a href="<?php echo grinco_url_html('/catalogue'); ?>">Voir les solutions <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
          </article>
        </div>
      </div>
    </div>
  </section>

  <section id="grinco-role" class="partner-role section">
    <div class="container section-title" data-aos="fade-up">
      <h2>Le rôle de GRINCO RDC</h2>
      <p>Un interlocuteur local pour structurer la demande et coordonner chaque étape avec le partenaire industriel.</p>
    </div>

    <div class="container">
      <div class="partner-process">
        <article class="partner-process-step" data-aos="fade-up">
          <div class="partner-step-number">01</div>
          <div class="partner-step-icon"><i class="bi bi-clipboard2-check" aria-hidden="true"></i></div>
          <h3>Analyse du besoin</h3>
          <p>GRINCO recueille les caractéristiques techniques, l’usage prévu et les contraintes du client.</p>
        </article>

        <article class="partner-process-step" data-aos="fade-up" data-aos-delay="75">
          <div class="partner-step-number">02</div>
          <div class="partner-step-icon"><i class="bi bi-ui-checks-grid" aria-hidden="true"></i></div>
          <h3>Sélection de la solution</h3>
          <p>L’équipe identifie le véhicule, l’engin ou la configuration la plus adaptée.</p>
        </article>

        <article class="partner-process-step" data-aos="fade-up" data-aos-delay="150">
          <div class="partner-step-number">03</div>
          <div class="partner-step-icon"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></div>
          <h3>Coordination avec la Chine</h3>
          <p>GRINCO coordonne la cotation, les spécifications et les échanges avec ZW GROUP.</p>
        </article>

        <article class="partner-process-step" data-aos="fade-up" data-aos-delay="225">
          <div class="partner-step-number">04</div>
          <div class="partner-step-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
          <h3>Livraison et accompagnement</h3>
          <p>GRINCO accompagne le client dans le processus d’importation, de livraison et de suivi selon les modalités convenues.</p>
        </article>
      </div>
    </div>
  </section>

  <section id="partnership-benefits" class="partner-benefits section light-background">
    <div class="container">
      <div class="row align-items-end mb-5">
        <div class="col-lg-7" data-aos="fade-right">
          <span class="partner-section-label">Pourquoi ce partenariat</span>
          <h2>Une relation conçue pour simplifier vos projets</h2>
        </div>
        <div class="col-lg-5" data-aos="fade-left">
          <p class="partner-benefits-intro">GRINCO RDC rapproche le besoin local des capacités industrielles et commerciales de ZW GROUP.</p>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-sm-6 col-lg-3" data-aos="zoom-in">
          <article class="partner-benefit-card">
            <i class="bi bi-building-check" aria-hidden="true"></i>
            <h3>Accès direct au fabricant</h3>
            <p>Une coordination structurée avec la source industrielle pour préparer la solution demandée.</p>
          </article>
        </div>
        <div class="col-sm-6 col-lg-3" data-aos="zoom-in" data-aos-delay="75">
          <article class="partner-benefit-card">
            <i class="bi bi-sliders" aria-hidden="true"></i>
            <h3>Configurations personnalisées</h3>
            <p>Des spécifications étudiées selon l’usage, les contraintes et les attentes du client.</p>
          </article>
        </div>
        <div class="col-sm-6 col-lg-3" data-aos="zoom-in" data-aos-delay="150">
          <article class="partner-benefit-card">
            <i class="bi bi-truck" aria-hidden="true"></i>
            <h3>Large choix de véhicules</h3>
            <p>Plusieurs familles de camions, semi-remorques et engins pour différents secteurs.</p>
          </article>
        </div>
        <div class="col-sm-6 col-lg-3" data-aos="zoom-in" data-aos-delay="225">
          <article class="partner-benefit-card">
            <i class="bi bi-headset" aria-hidden="true"></i>
            <h3>Accompagnement commercial et technique</h3>
            <p>Un suivi local depuis l’étude du besoin jusqu’aux modalités convenues après la livraison.</p>
          </article>
        </div>
      </div>
    </div>
  </section>

  <section id="zw-timeline" class="partner-timeline-section section">
    <div class="container section-title" data-aos="fade-up">
      <h2>Chronologie ZW GROUP</h2>
      <p>Les principales étapes du développement international présentées dans la documentation institutionnelle de ZW GROUP.</p>
    </div>

    <div class="container">
      <div class="partner-timeline">
        <article class="partner-timeline-item" data-aos="fade-up">
          <span class="partner-timeline-year">2014</span>
          <span class="partner-timeline-dot" aria-hidden="true"></span>
          <h3>Création</h3>
          <p>Création de ZW GROUP.</p>
        </article>
        <article class="partner-timeline-item" data-aos="fade-up" data-aos-delay="75">
          <span class="partner-timeline-year">2018–2019</span>
          <span class="partner-timeline-dot" aria-hidden="true"></span>
          <h3>Expansion internationale</h3>
          <p>Expansion vers plusieurs marchés internationaux.</p>
        </article>
        <article class="partner-timeline-item" data-aos="fade-up" data-aos-delay="150">
          <span class="partner-timeline-year">2020–2021</span>
          <span class="partner-timeline-dot" aria-hidden="true"></span>
          <h3>Production</h3>
          <p>Modernisation des capacités de production.</p>
        </article>
        <article class="partner-timeline-item" data-aos="fade-up" data-aos-delay="225">
          <span class="partner-timeline-year">2024–2025</span>
          <span class="partner-timeline-dot" aria-hidden="true"></span>
          <h3>Réseau de services</h3>
          <p>Développement du réseau de services internationaux.</p>
        </article>
        <article class="partner-timeline-item" data-aos="fade-up" data-aos-delay="300">
          <span class="partner-timeline-year">2026</span>
          <span class="partner-timeline-dot" aria-hidden="true"></span>
          <h3>Développement continu</h3>
          <p>Poursuite du développement comme fournisseur international de véhicules spécialisés.</p>
        </article>
      </div>
    </div>
  </section>

  <section id="partner-brands" class="partner-brands section light-background">
    <div class="container">
      <div class="row align-items-center gy-4">
        <div class="col-lg-4" data-aos="fade-right">
          <span class="partner-section-label">Offre évolutive</span>
          <h2>Marques et solutions proposées</h2>
          <p>Les produits et modèles seront alimentés ultérieurement depuis la base de données. Aucun prix ni modèle non validé n’est présenté sur cette page.</p>
        </div>
        <div class="col-lg-8" data-aos="fade-left">
          <div class="partner-brand-grid" aria-label="Marques prévues dans le catalogue GRINCO RDC">
            <span>HOWO</span>
            <span>SHACMAN</span>
            <span>FAW</span>
            <span>FOTON</span>
            <span>BEIBEN</span>
            <span>DONGFENG</span>
            <span>CAT</span>
            <span>SANY</span>
            <span>SDLG</span>
            <span>KOMATSU</span>
            <span>HITACHI</span>
            <span>HYUNDAI</span>
            <span>CHANGAN</span>
            <span>AVATR</span>
          </div>
          <p class="partner-brand-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Les logos seront ajoutés uniquement lorsque les fichiers officiels autorisés seront disponibles dans le projet.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="partner-gallery" class="partner-gallery section">
    <div class="container section-title" data-aos="fade-up">
      <h2>Partenariat en images</h2>
      <p>Des visuels disponibles dans le projet pour illustrer les activités techniques, logistiques et relationnelles de GRINCO RDC.</p>
    </div>

    <div class="container">
      <div class="partner-gallery-grid">
        <figure class="partner-gallery-card partner-gallery-wide" data-aos="zoom-in">
          <img src="assets/img/about/ChatGPT Image 29 juil. 2026, 11_07_31.png" alt="Techniciens GRINCO RDC dans un atelier industriel" loading="lazy">
          <figcaption><span>Usine et ateliers</span></figcaption>
        </figure>
        <figure class="partner-gallery-card" data-aos="zoom-in" data-aos-delay="50">
          <img src="assets/img/services/services-12.png" alt="Équipe GRINCO RDC auprès d’un véhicule industriel" loading="lazy">
          <figcaption><span>Camions</span></figcaption>
        </figure>
        <figure class="partner-gallery-card partner-gallery-tall" data-aos="zoom-in" data-aos-delay="100">
          <img src="assets/img/about/import.png" alt="Transport et expédition d’équipements GRINCO RDC" loading="lazy">
          <figcaption><span>Semi-remorques</span></figcaption>
        </figure>
        <figure class="partner-gallery-card" data-aos="zoom-in" data-aos-delay="150">
          <img src="assets/img/about/ingenierie.png" alt="Activités d’ingénierie et équipements GRINCO RDC" loading="lazy">
          <figcaption><span>Engins</span></figcaption>
        </figure>
        <figure class="partner-gallery-card" data-aos="zoom-in" data-aos-delay="200">
          <img src="assets/img/services/services-6.webp" alt="Échange avec des clients autour de leurs besoins" loading="lazy">
          <figcaption><span>Visites de clients</span></figcaption>
        </figure>
        <figure class="partner-gallery-card partner-gallery-wide" data-aos="zoom-in" data-aos-delay="250">
          <img src="assets/img/about/about-15.png" alt="Équipe GRINCO RDC préparant un projet client" loading="lazy">
          <figcaption><span>Préparation et expédition</span></figcaption>
        </figure>
      </div>
    </div>
  </section>

  <section id="partner-cta" class="partner-final-cta section">
    <div class="container">
      <div class="partner-cta-panel" data-aos="zoom-in">
        <div class="partner-cta-icon" aria-hidden="true"><i class="bi bi-truck-front"></i></div>
        <div class="partner-cta-content">
          <span>Parlons de votre projet</span>
          <h2>Vous recherchez un véhicule ou un équipement adapté à votre activité&nbsp;?</h2>
          <p>Présentez votre besoin à GRINCO RDC afin de recevoir une proposition technique et financière adaptée.</p>
        </div>
        <div class="partner-cta-actions">
          <a href="<?php echo grinco_url_html('/demande-devis'); ?>" class="partner-btn partner-btn-light">Demander un devis</a>
          <a href="<?php echo grinco_url_html('/contact'); ?>" class="partner-btn partner-btn-outline-light">Contacter notre équipe</a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
