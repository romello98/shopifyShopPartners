<?php

require_once __DIR__ . '/private/security.php';

if(is_authenticated())
{
    logout();
}

header('Location: login.php');