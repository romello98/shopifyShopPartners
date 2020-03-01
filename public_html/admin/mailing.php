<?php

require_once dirname(__DIR__) . '/private/dataService.php';
require_once dirname(__DIR__) . '/private/security.php';
require_once dirname(__DIR__) . '/phpmailer/PHPMailerAutoload.php';

if (!is_authenticated(true)) {
    header('Location: /admin/');
}

$CLIENTS_VARIABLES =
[
    'customer_firstname' => 'FirstName',
    'customer_lastname' => 'LastName',
    'customer_email' => 'Email',
];

$PARTNERS_VARIABLES =
[
    'partner_firstname' => 'FirstName',
    'partner_lastname' => 'LastName',
    'partner_email' => 'Email',
];

function replaceInfos($variables, $str, $customerObject)
{
    foreach($variables as $variable_key => $objectPropertyName)
    {
        $str = preg_replace('/\\$\\{' . $variable_key . '\\}/', $customerObject->$objectPropertyName, $str);
    }
    return $str;
}

$body = $_POST['body'] ?? null;
$to = $_POST['to'] ?? null;
$subject = $_POST['subject'] ?? null;
$acceptMarketing = $_POST['acceptMarketing'] ?? null;

$isMailSending = $body && $to && $subject;

if ($isMailSending) {

    $numberMailToSend = null;
    $mailsSent = 0;
    $dataService = new DataService();
    $mail = new PHPmailer();
    $mail->IsSMTP();
    $mail->IsHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Host = "mail.pandaroo.yo.fr";
    $mail->SMTPAuth = true;
    
    if($to === 'customers')
    {
        $mail->Username = 'newsletter@pandaroo.yo.fr';
        $mail->Password = 'JN138a12!';
        $mail->From = 'newsletter@pandaroo.yo.fr';
        $mail->FromName = "Pandaroo";

        $customers = $dataService->getCustomers($acceptMarketing === 'on');
        $numberMailToSend = sizeof($customers);
        foreach($customers as $customer) 
        {
            $mail->clearAddresses();
            $mail->Subject = replaceInfos($CLIENTS_VARIABLES, $subject, $customer);
            $mail->Body = replaceInfos($CLIENTS_VARIABLES, $body, $customer);
            $mail->addAddress($customer->Email);
            if($mail->send())
                $mailsSent++;
        }
    }
    else
    {
        if($to === 'partners')
        {
            $mail->Username = 'admin@pandaroo.yo.fr';
            $mail->Password = 'JN138a12!';
            $mail->From = 'admin@pandaroo.yo.fr';
            $mail->FromName = "Partners @ Pandaroo";

            $partners = $dataService->getPartners();
            foreach($partners as $partner)
            {
                $mail->clearAddresses();
                $mail->Subject = replaceInfos($PARTNERS_VARIABLES, $subject, $partner);
                $mail->Body = replaceInfos($PARTNERS_VARIABLES, $body, $partner);
                $mail->addAddress($partner->Email);
                if($mail->send())
                    $mailsSent++;
            }
        }
        else
        {
            echo "Destinataire non spécifié. Veuillez réessayer.";
            exit(-1);
        }
    }

    $mail->SmtpClose();
    unset($mail);

    echo "<p>Emails envoyés: $mailsSent / $numberMailToSend.</p>";
    echo "<a href=\"\">Revenir sur l'admin</a>";
    exit(0);
}

?>

<?php ob_start(); ?>
<?php ?>
<h1 class="mb-5">Mailing</h1>

<?php

$ADDITIONAL_SCRIPTS = [];
$ADDITIONAL_SCRIPTS[] = "
<script src=\"/admin/htmlButton.js\"></script>
<script src=\"//cdn.quilljs.com/1.3.6/quill.min.js\"></script>
<link href=\"//cdn.quilljs.com/1.3.6/quill.snow.css\" rel=\"stylesheet\">
<script>
    window.onload = function() {
        Quill.register(\"modules/htmlEditButton\", htmlEditButton);
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
              htmlEditButton: {},
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

<ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="html-tab" data-toggle="tab" href="#html" role="tab" aria-controls="html" aria-selected="true">HTML</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="visual-tab" data-toggle="tab" href="#visual" role="tab" aria-controls="visual" aria-selected="false">Visuel</a>
    </li>
</ul>
<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="html" role="tabpanel" aria-labelledby="html-tab">
        <form action="" method="POST" class="mt-4 form-inline">
            <input type="text" name="subject" placeholder="Objet"/>
            <textarea style="height: 400px" class="d-block w-100 mb-3" height="400" placeholder="Entrez votre e-mail au format HTML ici..." type="text" name="body" id="body-html"></textarea>
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
    </div>
    <div class="tab-pane fade" id="visual" role="tabpanel" aria-labelledby="visual-tab">
        <div id="editor">
        </div>
        <form action="" method="POST" onsubmit="return send()" class="mt-4 form-inline">
            <input type="text" name="subject" placeholder="Objet"/>
            <input id="body" type="hidden" name="body" />
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
    </div>
</div>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__) . '/private/templates/admin-template.php';
?>