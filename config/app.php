<?php

/*
 * Préfixe public du site.
 *
 * En local, le projet est servi depuis /grincodrc.com.
 * En production, le domaine pointe directement vers la racine du projet.
 */
$grincoHost = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
$grincoIsLocal = strpos($grincoHost, '127.0.0.1') !== false
    || strpos($grincoHost, 'localhost') !== false;

$baseUrl = $grincoIsLocal ? '/grincodrc.com' : '';

if (!function_exists('grinco_url')) {
    function grinco_url($path)
    {
        global $baseUrl;

        $path = (string) $path;
        if ($path === '' || $path === '/') {
            return $baseUrl . '/';
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('grinco_url_html')) {
    function grinco_url_html($path)
    {
        return htmlspecialchars(grinco_url($path), ENT_QUOTES, 'UTF-8');
    }
}

/*
 * Diagnostic de rendu désactivé par défaut.
 * Pour l'activer temporairement en production, définir
 * GRINCO_RENDER_DIAGNOSTICS=1 dans l'environnement PHP.
 */
if (!function_exists('grinco_render_log')) {
    function grinco_render_log($page, $stage)
    {
        if (getenv('GRINCO_RENDER_DIAGNOSTICS') !== '1') {
            return;
        }

        $page = preg_replace('/[^a-z0-9._-]/i', '-', (string) $page);
        $stage = preg_replace('/[^a-z0-9._-]/i', '-', (string) $stage);
        error_log('[GRINCO production page] page=' . $page . ' stage=' . $stage);
    }
}
