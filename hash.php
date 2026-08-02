<?php

// Saisissez ici le mot de passe à hacher
$motDePasse = 'grinco2026!';

// Génération du hash
$hash = password_hash($motDePasse, PASSWORD_DEFAULT);

echo "<h3>Mot de passe</h3>";
echo "<p>" . htmlspecialchars($motDePasse) . "</p>";

echo "<h3>Hash généré</h3>";
echo "<textarea rows='4' cols='100'>" . $hash . "</textarea>";