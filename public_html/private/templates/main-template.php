<!DOCTYPE html>
<html>
<?php

if(!isset($withContainer)) $withContainer = true;
if(!isset($content)) $content = 'Insert template content here.';
if(!isset($withNav)) $withNav = true;

require dirname(__DIR__, 2) . '/head.php';
if($withNav) require dirname(__DIR__, 2) . '/nav.php';

?>
<body>
    <?php if($withContainer) : ?>
        <div class="container mt-md-5 pt-md-4 mt-sm-2 pt-sm-1">
            <?php echo $content; ?>
        </div>
    <?php else : echo $content; endif; ?>
</body>
</html>