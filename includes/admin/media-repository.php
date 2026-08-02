<?php

require_once dirname(__DIR__) . '/database.php';

function grinco_media_product_options()
{
    $statement = grinco_database()->prepare(
        'SELECT id, reference, nom, modele FROM produits ORDER BY reference ASC, nom ASC'
    );
    $statement->execute();
    return $statement->fetchAll();
}

function grinco_media_find_product($productId)
{
    $statement = grinco_database()->prepare(
        'SELECT id, reference, nom, modele FROM produits WHERE id = :id LIMIT 1'
    );
    $statement->execute(array(':id' => (int) $productId));
    $product = $statement->fetch();
    return $product ? $product : null;
}

function grinco_media_images($productId)
{
    $statement = grinco_database()->prepare(
        'SELECT id, produit_id, image, image_principale FROM produit_images '
        . 'WHERE produit_id = :product_id ORDER BY image_principale DESC, id ASC'
    );
    $statement->execute(array(':product_id' => (int) $productId));
    return $statement->fetchAll();
}

function grinco_media_documents($productId)
{
    $statement = grinco_database()->prepare(
        'SELECT d.id, d.produit_id, d.document, p.reference, p.nom AS produit_nom '
        . 'FROM produit_documents d INNER JOIN produits p ON p.id = d.produit_id '
        . 'WHERE d.produit_id = :product_id ORDER BY d.id ASC'
    );
    $statement->execute(array(':product_id' => (int) $productId));
    return $statement->fetchAll();
}

function grinco_media_find_image($imageId, $productId)
{
    $statement = grinco_database()->prepare(
        'SELECT id, produit_id, image, image_principale FROM produit_images '
        . 'WHERE id = :id AND produit_id = :product_id LIMIT 1'
    );
    $statement->execute(array(':id' => (int) $imageId, ':product_id' => (int) $productId));
    $image = $statement->fetch();
    return $image ? $image : null;
}

function grinco_media_find_document($documentId, $productId)
{
    $statement = grinco_database()->prepare(
        'SELECT id, produit_id, document FROM produit_documents '
        . 'WHERE id = :id AND produit_id = :product_id LIMIT 1'
    );
    $statement->execute(array(':id' => (int) $documentId, ':product_id' => (int) $productId));
    $document = $statement->fetch();
    return $document ? $document : null;
}

function grinco_media_find_document_by_id($documentId)
{
    $statement = grinco_database()->prepare(
        'SELECT id, produit_id, document FROM produit_documents WHERE id = :id LIMIT 1'
    );
    $statement->execute(array(':id' => (int) $documentId));
    $document = $statement->fetch();
    return $document ? $document : null;
}

function grinco_media_product_has_primary_image($productId)
{
    $statement = grinco_database()->prepare(
        'SELECT COUNT(*) FROM produit_images WHERE produit_id = :product_id AND image_principale = 1'
    );
    $statement->execute(array(':product_id' => (int) $productId));
    return (int) $statement->fetchColumn() > 0;
}
