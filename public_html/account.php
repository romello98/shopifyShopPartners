<?php

require_once __DIR__ . '/private/dataService.php';
require_once __DIR__ . '/private/security.php';

if (!is_authenticated()) {
    header('Location: login.php');
}

$currentUser = getCurrentLoggedUser();
$isSaveRequest = !empty(post_clean_obtain('saveAccount'));

setlocale(LC_TIME, "fr_FR");
$dataService = new DataService();

if($isSaveRequest) {
    $email = post_clean_obtain('email');
    $lastName = post_clean_obtain('name');
    $firstName = post_clean_obtain('firstName');
    $paypalEmail = post_clean_obtain('paypalEmail');
    $password = post_clean_obtain('password');
    $result = false;
    $errors = [];

    if(strlen($password) < 8)
        $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
    if(!preg_match('/\w+@\w+\.\w+/', $email))
        $errors[] = 'L\'email a un format incorrect.';
    if(empty($email))
        $errors[] = 'L\'e-mail ne peut pas être vide.';
    else if($dataService->otherPartnerEmailExists($email, $currentUser->ID))
        $errors[] = 'L\'e-mail entré est déjà utilisé par un autre utilisateur que vous.';
    if(empty($firstName))
        $errors[] = 'Le prénom ne peut pas être vide.';
    if(empty($lastName))
        $errors[] = 'Le nom ne peut pas être vide.';
    
    if(sizeof($errors) == 0)
    {
        $partner = new Partner();
        $partner->ID = $currentUser->ID;
        $partner->Email = $email;
        $partner->FirstName = $firstName;
        $partner->LastName = $lastName;
        $partner->PayPalEmail = empty($paypalEmail) ? NULL : $paypalEmail;
        $partner->Password = $password;
        $result = $dataService->savePartner($partner);

        if($result === true)
        {
            $savedPartnerCode = $currentUser->PartnerCode;
            $_SESSION[SESSION_USER_KEY] = $partner;
            $_SESSION[SESSION_USER_KEY]->PartnerCode = $savedPartnerCode;
            $currentUser = $_SESSION[SESSION_USER_KEY];
        }
    }
}

$sales = $dataService->getPagedSalesByPartner($currentUser->ID);

?>

<?php ob_start(); ?>

<h1 class="mb-5">Compte</h1>

<?php if($isSaveRequest) : ?>
    <?php if($result) : ?>
        <p class="alert alert-success">Vos informations ont été mises à jour.</p>
    <?php else : ?>
        <div class="alert alert-danger">
            <p >Des erreurs sont survenues lors de la mise à jour de vos informations.</p>
            <ul class="mb-0 pb-0">
                <?php foreach($errors as $error) : ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
    function confirmSend()
    {
        let paypalEmail = $('#paypalEmail').val();
        if(paypalEmail == "")
        {
            let response = confirm("Vous avez un e-mail PayPal vide. Si vous avez des paiements en attente, cela peut poser problème. Êtes-vous sûr de vouloir continuer ?");
            return response;
        }
        return true;
    }
</script>

<form class="" action="" method="POST" autocomplete="false" onsubmit="return confirmSend()">
    <div class="row">
        <div class="col form-group">
            <label for="email">Adresse e-mail</label>
            <input name="email" type="email" class="form-control" id="email" aria-describedby="emailHelp" placeholder="Adresse e-mail" value="<?php echo $currentUser->Email; ?>">
        </div>
        <div class="col form-group">
            <label for="password">Mot de passe</label>
            <input name="password" type="password" class="form-control" id="password" placeholder="Mot de passe">
        </div>
    </div>
    <div class="row">
        <div class="col form-group">
            <label for="name">Nom</label>
            <input name="name" type="text" class="form-control" id="name" value="<?php echo $currentUser->LastName; ?>" placeholder="Nom">
        </div>
        <div class="col form-group">
            <label for="firstName">Prénom</label>
            <input name="firstName" type="text" class="form-control" id="firstName" value="<?php echo $currentUser->FirstName; ?>" placeholder="Prénom">
        </div>
    </div>
    <div class="row">
        <div class="col form-group">
            <label for="paypalEmail">E-mail PayPal</label>
            <input name="paypalEmail" type="text" class="form-control" id="paypalEmail" value="<?php echo $currentUser->PayPalEmail; ?>" placeholder="E-mail PayPal">
        </div>
        <div class="col form-group">
            <label for="affiliateCode">Code affilié</label>
            <input disabled type="text" class="form-control" value="<?php echo $currentUser->PartnerCode; ?>" id="affiliateCode">
        </div>
    </div>
    <button type="submit" name="saveAccount" value="true" class="btn btn-primary">Enregistrer</button>
</form>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/private/templates/main-template.php';
?>