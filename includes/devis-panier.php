<?php

require_once __DIR__ . '/form-security.php';
require_once __DIR__ . '/catalogue-repository.php';

function grinco_quote_cart_key()
{
    return 'grinco_quote_cart';
}

function grinco_quote_cart_max_quantity()
{
    return 1000;
}

function grinco_quote_cart()
{
    $key = grinco_quote_cart_key();
    $source = isset($_SESSION[$key]) && is_array($_SESSION[$key]) ? $_SESSION[$key] : array();
    $cart = array();
    foreach ($source as $productId => $quantity) {
        if (
            ctype_digit((string) $productId)
            && (int) $productId > 0
            && ctype_digit((string) $quantity)
            && (int) $quantity >= 1
            && (int) $quantity <= grinco_quote_cart_max_quantity()
        ) {
            $cart[(int) $productId] = (int) $quantity;
        }
    }
    $_SESSION[$key] = $cart;
    return $cart;
}

function grinco_quote_cart_replace($cart)
{
    $_SESSION[grinco_quote_cart_key()] = $cart;
}

function grinco_quote_cart_add($productId, $quantity)
{
    $cart = grinco_quote_cart();
    $current = isset($cart[$productId]) ? (int) $cart[$productId] : 0;
    $cart[$productId] = min(grinco_quote_cart_max_quantity(), $current + $quantity);
    grinco_quote_cart_replace($cart);
    return $cart[$productId];
}

function grinco_quote_cart_update($productId, $quantity)
{
    $cart = grinco_quote_cart();
    if (!isset($cart[$productId])) {
        return false;
    }
    $cart[$productId] = $quantity;
    grinco_quote_cart_replace($cart);
    return true;
}

function grinco_quote_cart_remove($productId)
{
    $cart = grinco_quote_cart();
    unset($cart[$productId]);
    grinco_quote_cart_replace($cart);
}

function grinco_quote_cart_clear()
{
    grinco_quote_cart_replace(array());
}

function grinco_quote_cart_count()
{
    return count(grinco_quote_cart());
}

function grinco_quote_cart_items()
{
    $cart = grinco_quote_cart();
    $products = grinco_catalogue_public_products_by_ids(array_keys($cart));
    $items = array();
    $missing = array();

    foreach ($cart as $productId => $quantity) {
        if (!isset($products[$productId])) {
            $missing[] = (int) $productId;
            continue;
        }
        $product = $products[$productId];
        $product['quantite'] = (int) $quantity;
        $items[] = $product;
    }

    return array('items' => $items, 'missing_ids' => $missing, 'cart' => $cart);
}

function grinco_quote_cart_set_flash($type, $message)
{
    $_SESSION['quote_cart_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message
    );
}

function grinco_quote_cart_take_flash()
{
    $flash = isset($_SESSION['quote_cart_flash']) && is_array($_SESSION['quote_cart_flash'])
        ? $_SESSION['quote_cart_flash']
        : null;
    unset($_SESSION['quote_cart_flash']);
    return $flash;
}
