<?php
$pageTitle = 'Notre équipe';
$pageDescription = 'Découvrez l’équipe commerciale de GRINCO RDC et son agent de liaison entre la Chine et la République démocratique du Congo.';
$currentPage = 'equipe';

$agents = array(
  array(
    'name' => 'Francky Sabiti',
    'role' => 'Sales Manager',
    'email' => 'franckysabiti2@gmail.com',
    'phone' => '+243 812 700 366',
    'whatsapp' => '+243 850 754 604',
    'location' => 'RDC Congo : Kinshasa, Lubumbashi, Kolwezi (en progrès).',
    'image' => 'assets/img/person/francky.jpeg',
    'placeholder' => false
  ),
  array(
    'name' => 'Darcin N’naka',
    'role' => 'Sales Manager',
    'email' => 'cishugil11@gmail.com',
    'phone' => '+243 977 589 764',
    'whatsapp' => '+243 977 589 764',
    'location' => 'RDC Congo : Kinshasa, Lubumbashi, Kolwezi (en progrès).',
    'image' => 'assets/img/person/darcin.png',
    'placeholder' => true
  ),
  array(
    'name' => 'Elie Nzeza',
    'role' => 'Sales Manager',
    'email' => 'elienzeza11@gmail.com',
    'phone' => '+243 998 445 575',
    'whatsapp' => '+243 998 445 575',
    'location' => 'RDC Congo : Kinshasa, Lubumbashi, Kolwezi (en progrès).',
    'image' => 'assets/img/person/elie.jpeg',
    'placeholder' => true
  ),
  array(
    'name' => 'Carine Ndungo',
    'role' => 'Sales Manager',
    'email' => 'carinendng@gmail.com',
    'phone' => '+243 990 485 866',
    'whatsapp' => '+243 990 485 866',
    'location' => 'RDC Congo : Kinshasa, Lubumbashi, Kolwezi (en progrès).',
    'image' => 'assets/img/person/vide.png',
    'placeholder' => true
  ),
  array(
    'name' => 'Arnold (赵)',
    'role' => 'Sales Manager',
    'email' => 'ntabalar97@hotmail.com',
    'phone' => '+234 999 304 030',
    'whatsapp' => '+86 132 4168 7081',
    'location' => 'RDC Congo : Kinshasa, Lubumbashi, Kolwezi (en progrès).',
    'image' => 'assets/img/person/arno.png',
    'placeholder' => false
  ),
  array(
    'name' => 'Moranda Guo',
    'role' => 'Agent de liaison Chine – RDC',
    'email' => 'moranda@chinausedtrucktrailer.com',
    'phone' => '+86 138 3536 4021',
    'whatsapp' => '+86 133 5669 8013',
    'location' => 'Room 2609, Greenland Center, Jinan City, Shandong Province, China.',
    'image' => 'assets/img/person/vide.png',
    'placeholder' => true
  )
);

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>

<main class="main">
  <div class="page-title light-background">
    <div class="container d-lg-flex justify-content-between align-items-center">
      <h1 class="mb-2 mb-lg-0">Notre équipe</h1>
      <nav class="breadcrumbs" aria-label="Fil d’Ariane">
        <ol>
          <li><a href="index.php">Accueil</a></li>
          <li><a href="a-propos.php">Entreprise</a></li>
          <li class="current">Notre équipe</li>
        </ol>
      </nav>
    </div>
  </div>

  <section id="equipe" class="team-page section">
    <div class="container section-title" data-aos="fade-up">
      <h2>Une équipe proche de vos besoins</h2>
      <p>Notre équipe commerciale vous accompagne en République démocratique du Congo, avec un relais dédié pour les échanges et les opérations entre la Chine et la RDC.</p>
    </div>

    <div class="container">
      <div class="row g-4">
        <?php foreach ($agents as $index => $agent) {
          $phoneHref = 'tel:' . preg_replace('/[^\d+]/', '', $agent['phone']);
          $emailHref = 'mailto:' . $agent['email'];
          $whatsappHref = 'https://wa.me/' . preg_replace('/\D/', '', $agent['whatsapp']);
          $imageClass = $agent['placeholder'] ? ' team-agent-placeholder' : '';
        ?>
          <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo ($index % 3) * 100; ?>">
            <article class="team-agent-card">
              <div class="team-agent-photo<?php echo $imageClass; ?>">
                <img
                  src="<?php echo htmlspecialchars($agent['image'], ENT_QUOTES, 'UTF-8'); ?>"
                  alt="<?php echo htmlspecialchars($agent['placeholder'] ? 'Photo à venir pour ' . $agent['name'] : 'Portrait de ' . $agent['name'], ENT_QUOTES, 'UTF-8'); ?>"
                  loading="lazy">
              </div>

              <div class="team-agent-content">
                <header>
                  <h3><?php echo htmlspecialchars($agent['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p class="team-agent-role"><?php echo htmlspecialchars($agent['role'], ENT_QUOTES, 'UTF-8'); ?></p>
                </header>

                <ul class="team-agent-details">
                  <li>
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <span><strong>E-mail</strong><?php echo htmlspecialchars($agent['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                  <li>
                    <i class="bi bi-telephone" aria-hidden="true"></i>
                    <span><strong>Téléphone</strong><?php echo htmlspecialchars($agent['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                  <li>
                    <i class="bi bi-whatsapp" aria-hidden="true"></i>
                    <span><strong>WhatsApp</strong><?php echo htmlspecialchars($agent['whatsapp'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                  <li>
                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                    <span><strong>Zone</strong><?php echo htmlspecialchars($agent['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                </ul>

                <div class="team-agent-actions">
                  <a href="<?php echo htmlspecialchars($phoneHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Appeler <?php echo htmlspecialchars($agent['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-telephone-fill" aria-hidden="true"></i><span>Appeler</span>
                  </a>
                  <a href="<?php echo htmlspecialchars($emailHref, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Envoyer un e-mail à <?php echo htmlspecialchars($agent['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-envelope-fill" aria-hidden="true"></i><span>E-mail</span>
                  </a>
                  <a href="<?php echo htmlspecialchars($whatsappHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" aria-label="Contacter <?php echo htmlspecialchars($agent['name'], ENT_QUOTES, 'UTF-8'); ?> sur WhatsApp">
                    <i class="bi bi-whatsapp" aria-hidden="true"></i><span>WhatsApp</span>
                  </a>
                </div>
              </div>
            </article>
          </div>
        <?php } ?>
      </div>
    </div>
  </section>

  <section class="team-contact-cta section">
    <div class="container" data-aos="fade-up">
      <div class="team-contact-cta-inner">
        <div>
          <span>Un projet ou une demande commerciale&nbsp;?</span>
          <h2>Échangez avec l’équipe GRINCO RDC</h2>
          <p>Présentez-nous votre besoin en véhicules, équipements, pièces ou services techniques.</p>
        </div>
        <a href="contact.php" class="btn btn-light">Nous contacter</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/scripts.php'; ?>
