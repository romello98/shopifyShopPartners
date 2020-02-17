<?php

require_once dirname(__DIR__) . '/private/dataService.php';
require_once dirname(__DIR__) . '/private/security.php';

if(!is_authenticated(true))
{
    header('Location: /admin/');
}

?>

<?php ob_start(); ?>
<?php ?>
<h1 class="mb-5">Mailing</h1>

<?php 

$ADDITIONAL_SCRIPTS = []; 
$ADDITIONAL_SCRIPTS[] = "
<script src='https://cdn.tiny.cloud/1/t820moyapdtlkle38zq4h83u4t1g2pjai8598tfjyyqpbdoq/tinymce/5/tinymce.min.js' referrerpolicy=\"origin\">
</script>
<script>
  tinymce.init({
    selector: '#tiny-zone'
  });
</script>
<script>
  function send()
  {
    console.log('Vous avez envoyé un e-mail: ' + tinymce.activeEditor.getContent({format: 'html'}));
  }
</script>
";

?>

<textarea id="tiny-zone">
</textarea>
<button class="mt-3" onclick="send()" class="btn btn-primary">Envoyer</button>

<?php 
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/private/templates/admin-template.php'; 
?>