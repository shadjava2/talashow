<?php

/**
 * Point d'entrée pour hébergement où la racine web est le dossier du projet
 * (et non public/). Redirige tout vers public/index.php.
 */
$path = __DIR__ . '/public/index.php';
if (file_exists($path)) {
    require $path;
} else {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Laravel public/index.php introuvable.';
}
