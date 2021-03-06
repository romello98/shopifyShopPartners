<?php

if(!isset($ADDITIONAL_SCRIPTS)) $ADDITIONAL_SCRIPTS = [];

?>

<head>
    <title>Administration</title>
    <meta charset="UTF-8"/>
    <link rel="stylesheet" type="text/css" href="/style.css"/>
    <script src="https://code.jquery.com/jquery-2.1.3.js"></script>
    <script src="/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/script.js"></script>
    <?php foreach($ADDITIONAL_SCRIPTS as $script) : ?>
        <?php echo $script ?>
    <?php endforeach; ?>
</head>