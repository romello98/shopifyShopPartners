<?php

require_once dirname(__DIR__) . '/private/dataService.php';
require_once dirname(__DIR__) . '/private/security.php';

if(!is_authenticated(true))
{
    header('Location: /admin/');
}

?>

<?php ob_start(); ?>

<h1 class="mb-5">Partenaires</h1>

<?php 
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/private/templates/admin-template.php'; 
?>