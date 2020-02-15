<?php
session_start();

require_once __DIR__ . '/model/Partner.class.php';

define('SESSION_USER_KEY', 'USER', true);

function get_clean_obtain($paramName)
{
    $rawValue = $_GET[$paramName] ?? null;
    $HTMLFilteredValue = htmlspecialchars($rawValue);
    return $HTMLFilteredValue;
}

function post_clean_obtain($paramName)
{
    $rawValue = $_POST[$paramName] ?? null;
    $HTMLFilteredValue = htmlspecialchars($rawValue);
    return $HTMLFilteredValue;
}


function is_authenticated()
{
    return !empty($_SESSION[SESSION_USER_KEY]);
}

function login(Partner $user)
{
    $_SESSION[SESSION_USER_KEY] = $user;
}

function logout()
{
    unset($_SESSION[SESSION_USER_KEY]);
    session_destroy();
}

function getCurrentLoggedUser()
{
    return $_SESSION[SESSION_USER_KEY];
}