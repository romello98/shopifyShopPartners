<?php

require_once __DIR__ . '/private/model/Partner.class.php';
require_once __DIR__ . '/private/dataService.php';
require_once __DIR__ . '/private/security.php';

if(is_authenticated())
{
    header('Location: panel.php');
}

$email = post_clean_obtain('email');
$password = post_clean_obtain('password');
$errorMessage = null;
$isLoginRequest = !empty($email) && !empty($password);

if($isLoginRequest)
{
    $dataService = new DataService();
    $result = $dataService->getUserByEmailAndPassword($email, $password);

    if(is_object($result) && get_class($result) == 'Partner')
    {
        login($result);
        header('Location: panel.php');
    }

    $errorMessage = $result;
}

?>
<?php ob_start(); ?>

<h1 class="text-center mb-5">Connexion</h1>

<?php if($isLoginRequest && !empty($errorMessage)) : ?>
    <p class="alert alert-danger"><?php echo $errorMessage; ?><p>
<?php endif; ?>

<form method="POST" action="" class="form-signin">
    <div class="form-group">
        <label for="email">E-mail</label>
        <input class="form-control" id="email" name="email" type="email" required />
    </div>
    <div class="form-group">
        <label for="password">Mot de passe</label>
        <input class="form-control" id="password" name="password" type="password" required />
    </div>
    <button class="d-block mr-auto ml-auto w-100 btn btn-lg btn-primary" type="submit">Se connecter</button>
</form>

<?php 
    $withNav = false;
    $content = ob_get_clean();
    require_once __DIR__ . '/private/templates/main-template.php'; 
?>