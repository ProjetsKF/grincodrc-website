<?php
$cataloguePages = array(
  'catalogue',
  'camions',
  'semi-remorques',
  'engins-lourds',
  'vehicules-particuliers',
  'pieces-de-rechange'
);
$entreprisePages = array('a-propos', 'equipe', 'partenaires');
$resourcesPages = array('comment-commander', 'actualites', 'galerie');

if (!function_exists('grincoMenuClass')) {
  function grincoMenuClass($page, $currentPage)
  {
    return $page === $currentPage ? ' class="active" aria-current="page"' : '';
  }
}
?>
<nav id="navmenu" class="navmenu" aria-label="Navigation principale">
  <ul>
    <li><a href="index.php"<?php echo grincoMenuClass('accueil', $currentPage); ?>>Accueil</a></li>
    <li class="dropdown">
      <a href="a-propos.php"<?php echo in_array($currentPage, $entreprisePages, true) ? ' class="active"' : ''; ?>>
        <span>Entreprise</span> <i class="bi bi-chevron-down toggle-dropdown" aria-hidden="true"></i>
      </a>
      <ul>
        <li><a href="a-propos.php"<?php echo grincoMenuClass('a-propos', $currentPage); ?>>À propos</a></li>
        <li><a href="equipe.php"<?php echo grincoMenuClass('equipe', $currentPage); ?>>Notre équipe</a></li>
        <li><a href="partenaires.php"<?php echo grincoMenuClass('partenaires', $currentPage); ?>>Partenaires</a></li>
      </ul>
    </li>
    <li class="dropdown">
      <a href="catalogue.php"<?php echo in_array($currentPage, $cataloguePages, true) ? ' class="active"' : ''; ?>>
        <span>Catalogue</span> <i class="bi bi-chevron-down toggle-dropdown" aria-hidden="true"></i>
      </a>
      <ul>
        <li><a href="catalogue.php"<?php echo grincoMenuClass('catalogue', $currentPage); ?>>Tout le catalogue</a></li>
        <li><a href="camions.php"<?php echo grincoMenuClass('camions', $currentPage); ?>>Camions</a></li>
        <li><a href="semi-remorques.php"<?php echo grincoMenuClass('semi-remorques', $currentPage); ?>>Semi-remorques</a></li>
        <li><a href="engins-lourds.php"<?php echo grincoMenuClass('engins-lourds', $currentPage); ?>>Engins lourds</a></li>
        <li><a href="vehicules-particuliers.php"<?php echo grincoMenuClass('vehicules-particuliers', $currentPage); ?>>Véhicules particuliers</a></li>
        <li><a href="pieces-de-rechange.php"<?php echo grincoMenuClass('pieces-de-rechange', $currentPage); ?>>Pièces de rechange</a></li>
      </ul>
    </li>
    <li><a href="services.php"<?php echo grincoMenuClass('services', $currentPage); ?>>Services</a></li>
    <li class="dropdown">
      <a href="comment-commander.php"<?php echo in_array($currentPage, $resourcesPages, true) ? ' class="active"' : ''; ?>>
        <span>Ressources</span> <i class="bi bi-chevron-down toggle-dropdown" aria-hidden="true"></i>
      </a>
      <ul>
        <li><a href="comment-commander.php"<?php echo grincoMenuClass('comment-commander', $currentPage); ?>>Comment commander</a></li>
        <li><a href="actualites.php"<?php echo grincoMenuClass('actualites', $currentPage); ?>>Actualités</a></li>
      </ul>
    </li>
    <li><a href="contact.php"<?php echo grincoMenuClass('contact', $currentPage); ?>>Contact</a></li>
  </ul>
  <i class="mobile-nav-toggle d-xl-none bi bi-list" role="button" tabindex="0" aria-label="Ouvrir le menu"></i>
</nav>
