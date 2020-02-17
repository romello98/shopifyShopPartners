<?php

require_once dirname(__DIR__) . '/private/dataService.php';
require_once dirname(__DIR__) . '/private/security.php';

if(!is_authenticated(true))
{
    header('Location: /admin/');
}

$body = $_POST['body'] ?? null;
$to = $_POST['to'] ?? null;
$acceptMarketing = $_POST['acceptMarketing'] ?? null;

$isMailSending = $body && $to;

if($isMailSending)
{
    echo "Vous voulez envoyer un e-mail à [$to" . ($to === 'customers' ? ' - ' . ($acceptMarketing == 'on' ? 'Uniquement marketing' : 'Tous') : '') . "]:\n\n"
        . "$body";
    exit(0);
}

?>

<?php ob_start(); ?>
<?php ?>
<h1 class="mb-5">Mailing</h1>

<?php 

$ADDITIONAL_SCRIPTS = []; 
$ADDITIONAL_SCRIPTS[] = "
<script src=\"//cdn.quilljs.com/1.3.6/quill.min.js\"></script>
<link href=\"//cdn.quilljs.com/1.3.6/quill.snow.css\" rel=\"stylesheet\">
<script>
    window.onload = function() {
        container = document.getElementById('editor');
        var toolbarOptions = [
            [{ 'font': [] }],
            [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
            ['blockquote', 'code-block'],
          
            [{ 'header': 1 }, { 'header': 2 }],               // custom button values
            ['link', 'image'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript
            [{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent
            [{ 'direction': 'rtl' }],                         // text direction
          
            [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
            [{ 'align': [] }],
          
            ['clean']                                         // remove formatting button
          ];
        var options = {
            debug: 'info',
            modules: {
              toolbar: toolbarOptions,
            },
            placeholder: 'Rédigez votre e-mail...',
            readOnly: false,
            theme: 'snow'
          };
        editor = new Quill(container, options);
        let acceptMarketingCheckbox = $('#acceptMarketing');
        $('#to').change(function() {
            acceptMarketingCheckbox.parent().toggleClass('d-none');
        });
    }

    function send()
    {
        let bodyHTML = editor.container.firstChild.innerHTML;
        $('#body').val(bodyHTML);
        return true;
    }
</script>
";

?>

<div id="editor">
</div>
<form action="" method="POST" onsubmit="return send()" class="mt-4 form-inline">
    <input id="body" type="hidden" name="body"/>
    <label class="mr-2" for="to">Destinataires : </label>
    <select class="custom-select mr-4" name="to" id="to">
        <option selected value="customers">Clients</option>
        <option value="partners">Partenaires</option>
    </select>
    <div class="custom-control custom-checkbox mr-4">
        <input type="checkbox" class="mr-2 custom-control-input" name="acceptMarketing" id="acceptMarketing">
        <label class="custom-control-label" for="acceptMarketing">Marketing accepté uniquement</label>
    </div>
    <button type="submit" class="ml-auto btn btn-primary">Envoyer</button>
</form>

<?php 
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/private/templates/admin-template.php'; 
?>