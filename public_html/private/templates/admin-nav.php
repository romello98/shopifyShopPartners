<?php

function isPage($pageName)
{
    return basename($_SERVER["SCRIPT_FILENAME"], '.php') === $pageName;
}

?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary p-4 mb-3">
    <div class="container">
        <a class="navbar-brand mr-5" href="#">Administration</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item<?php echo isPage('panel') ? ' active' : '' ?>">
                    <a class="nav-link" href="/admin/panel.php">Activité <span class="sr-only"></span></a>
                </li>
                <li class="nav-item<?php echo isPage('payments') ? ' active' : '' ?>">
                    <a class="nav-link" href="/admin/payments.php">Paiements <span class="sr-only"></span></a>
                </li>
                <li class="nav-item<?php echo isPage('partners') ? ' active' : '' ?>">
                    <a class="nav-link" href="/admin/partners.php">Partenaires <span class="sr-only"></span></a>
                </li>
                <li class="nav-item<?php echo isPage('mailing') ? ' active' : '' ?>">
                    <a class="nav-link" href="/admin/mailing.php">Mailing <span class="sr-only"></span></a>
                </li>
            </ul>
            <ul class="navbar-nav my-2 my-lg-0">
                <li class="nav-item<?php echo isPage('account') ? ' active' : '' ?>">
                    <a class="nav-link" href="/admin/account.php">Compte <span class="sr-only"></span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/logout.php?admin=true">Déconnexion <span class="sr-only"></span></a>
                </li>
            </ul>
        </div>
    </div>
</nav>
