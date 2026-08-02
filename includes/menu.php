<?php
require_once dirname(__DIR__) . '/config/app.php';

$cataloguePages = array(
  'catalogue',
  'produit',
  'panier-devis',
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
    <li><a href="<?php echo grinco_url_html('/'); ?>"<?php echo grincoMenuClass('accueil', $currentPage); ?>>Accueil</a></li>
    <li class="dropdown">
      <a href="<?php echo grinco_url_html('/a-propos'); ?>"<?php echo in_array($currentPage, $entreprisePages, true) ? ' class="active"' : ''; ?>>
        <span>Entreprise</span> <i class="bi bi-chevron-down toggle-dropdown" aria-hidden="true"></i>
      </a>
      <ul>
        <li><a href="<?php echo grinco_url_html('/a-propos'); ?>"<?php echo grincoMenuClass('a-propos', $currentPage); ?>>À propos</a></li>
        <li><a href="<?php echo grinco_url_html('/equipe'); ?>"<?php echo grincoMenuClass('equipe', $currentPage); ?>>Notre équipe</a></li>
        <li><a href="<?php echo grinco_url_html('/partenaires'); ?>"<?php echo grincoMenuClass('partenaires', $currentPage); ?>>Partenaires</a></li>
      </ul>
    </li>
    <li class="dropdown">
      <a href="<?php echo grinco_url_html('/catalogue'); ?>"<?php echo in_array($currentPage, $cataloguePages, true) ? ' class="active"' : ''; ?>>
        <span>Catalogue</span> <i class="bi bi-chevron-down toggle-dropdown" aria-hidden="true"></i>
      </a>
      <ul>
        <li><a href="<?php echo grinco_url_html('/catalogue'); ?>"<?php echo grincoMenuClass('catalogue', $currentPage); ?>>Tout le catalogue</a></li>
        <li><a href="<?php echo grinco_url_html('/camions'); ?>"<?php echo grincoMenuClass('camions', $currentPage); ?>>Camions</a></li>
        <li><a href="<?php echo grinco_url_html('/semi-remorques'); ?>"<?php echo grincoMenuClass('semi-remorques', $currentPage); ?>>Semi-remorques</a></li>
        <li><a href="<?php echo grinco_url_html('/engins-lourds'); ?>"<?php echo grincoMenuClass('engins-lourds', $currentPage); ?>>Engins lourds</a></li>
        <li><a href="<?php echo grinco_url_html('/vehicules-particuliers'); ?>"<?php echo grincoMenuClass('vehicules-particuliers', $currentPage); ?>>Véhicules particuliers</a></li>
        <li><a href="<?php echo grinco_url_html('/pieces-de-rechange'); ?>"<?php echo grincoMenuClass('pieces-de-rechange', $currentPage); ?>>Pièces de rechange</a></li>
      </ul>
    </li>
    <li><a href="<?php echo grinco_url_html('/services'); ?>"<?php echo grincoMenuClass('services', $currentPage); ?>>Services</a></li>
    <li class="dropdown">
      <a href="<?php echo grinco_url_html('/comment-commander'); ?>"<?php echo in_array($currentPage, $resourcesPages, true) ? ' class="active"' : ''; ?>>
        <span>Ressources</span> <i class="bi bi-chevron-down toggle-dropdown" aria-hidden="true"></i>
      </a>
      <ul>
        <li><a href="<?php echo grinco_url_html('/comment-commander'); ?>"<?php echo grincoMenuClass('comment-commander', $currentPage); ?>>Comment commander</a></li>
        <li><a href="<?php echo grinco_url_html('/actualites'); ?>"<?php echo grincoMenuClass('actualites', $currentPage); ?>>Actualités</a></li>
      </ul>
    </li>
    <li><a href="<?php echo grinco_url_html('/contact'); ?>"<?php echo grincoMenuClass('contact', $currentPage); ?>>Contact</a></li>
  </ul>
  <i class="mobile-nav-toggle d-xl-none bi bi-list" role="button" tabindex="0" aria-label="Ouvrir le menu"></i>
</nav>
