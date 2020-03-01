<?php

define('ELIGIBLE_PAYMENTS', 'ELIGIBLE_PAYMENTS');
define('ELIGIBLE_AMOUNT', 'ELIGIBLE_AMOUNT');

require_once __DIR__ . '/private/dataService.php';
require_once __DIR__ . '/private/security.php';

if (!is_authenticated()) {
    header('Location: login.php');
}

setlocale(LC_TIME, "fr_FR");

$currentUser = getCurrentLoggedUser();
$dataService = new DataService();
$isPaymentRequest = ($paymentRequest = get_clean_obtain('paymentRequest') == 'true');
$paymentThreshold = 50;

if($isPaymentRequest)
{  
    $errors = [];

    if(!$dataService->hasPaypalEmail($currentUser->ID))
    {
        $errors[] = 'Veuillez spécifier un compte PayPal dans vos <a href="/account.php#paypalEmail" class="alert-link">paramètres de compte</a>.';
    }
    else
    {
        if(!empty($_SESSION[ELIGIBLE_PAYMENTS]) && !empty($_SESSION[ELIGIBLE_AMOUNT]) 
            && $_SESSION[ELIGIBLE_AMOUNT] >= $paymentThreshold)
        {
            try
            {
                $insertedPaymentRequest = $dataService->addPaymentRequest($currentUser->ID, $_SESSION[ELIGIBLE_PAYMENTS]);
                if($insertedPaymentRequest != null) $paymentRequestID = $insertedPaymentRequest->ID;
            } catch(Exception $e)
            {
                $paymentRequestID = null;
                $errors[] = 'Une erreur est survenue lors de la demande de paiement';
            }
        } 
        else
        {
            $paymentRequestID = null;
            $errors[] = 'Une erreur est survenue lors de la demande de paiement';
        }
    }
}

$eligibleOrders = $dataService->getEligiblePayments($currentUser->ID);
$salesInformation = $dataService->getEligiblePaymentsAmount($currentUser->ID);
$eligibleRevenue = $salesInformation->Revenue;
$eligibleSalesAmount = $salesInformation->SalesAmount;
$paidPayments = $dataService->getPaidPayments($currentUser->ID);
$paymentRequests = $dataService->getPaymentRequests($currentUser->ID);

$isAvailable = $eligibleRevenue >= $paymentThreshold;

$_SESSION[ELIGIBLE_PAYMENTS] = array_map(function($payment) {
    return $payment->ID;
}, $eligibleOrders);
$_SESSION[ELIGIBLE_AMOUNT] = $eligibleRevenue;

?>

<?php ob_start(); ?>

<h1 class="mb-5">Paiements</h1>

<?php if($isPaymentRequest) : ?>
    <?php if(isset($paymentRequestID) && $paymentRequestID !== null) : ?>
        <p class="alert alert-success mb-5" role="alert">Vous avez demandé un paiement de <?php echo number_format($insertedPaymentRequest->Total, 2); ?> €. (Numéro: <?php echo $paymentRequestID ?>)</p>
    <?php else : ?>
        <div class="alert alert-danger mb-5">
            <p>La demande de paiement a échoué.</p>
            <ul class="mb-0 pb-0">
                <?php foreach($errors as $error) : ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php /* PAIEMENTS ÉLIGIBLES */ ?>
<h2 class="mb-5">Montant éligible au paiement</h2>
<?php if (sizeof($eligibleOrders) == 0) : ?>
    <p class="text-center mb-5">Aucune commande n'est éligible. Comptez un délai variant de 20 à 30 jours pour qu'une commande devienne éligible.</p>
<?php else : ?>
    <table class="summary mb-5 payment-summary">
        <thead>
            <th>Revenus éligibles</th>
            <th>Action</th>
        </thead>
        <tbody>
            <tr>
                <td class="h3 money <?php echo $isAvailable ? 'available' : 'unavailable' ?>"><?php echo number_format($eligibleRevenue, 2) ?> €</td>
                <td><button class="btn btn-primary
                    <?php echo $isAvailable ? '" data-toggle="modal" data-target="#paymentConfirmationModal"' : " disabled\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"Le seuil de paiement minimum est de $paymentThreshold €.\""; ?>">
                        Demander un paiement
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>
<?php /* DEMANDES DE PAIEMENTS */ ?>
<?php if(sizeof($paymentRequests) > 0) : ?>
<h2 class="mb-5">Demandes de paiement</h2>
<script>
    function getOrdersByPaymentRequestID(requestID, callback = displayOrders)
    {
        let request = new XMLHttpRequest();
        let orders = null;
        request.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            orders = JSON.parse(this.responseText);
            callback(orders);
        }
        };
        request.open("GET", "/data-actions.php?action=ORDERS_BY_PAYMENT_REQUEST_ID&ID=" + requestID, true);
        request.send();
    }

    function displayOrders(orders)
    {
        let table = document.createElement('table');
        table.classList.add('table', 'table-striped', 'mb-5');
        let thead = document.createElement('thead');
        thead.innerHTML = "<th>ID</th><th>Nom</th><th>Montant</th>";
        let tbody = document.createElement('tbody');

        for(let order of orders)
        {
            let tr = document.createElement('tr');
            tr.innerHTML = `<td>${order.ID}</td><td>${'Produit'}</td><td>${Number(order.Amount * order.CommissionPercentage).toFixed(2)} €</td>`;
            tbody.appendChild(tr);
        }

        table.appendChild(thead);
        table.appendChild(tbody);
        let modalBody = $('#ordersDetails #body');
        modalBody.empty();
        modalBody.append(table);
        $('#ordersDetails').modal();
    }
</script>
<table class="table table-striped mb-5">
    <thead>
        <th>ID</th>
        <th>Date</th>
        <th>Ventes concernées</th>
        <th>Montant</th>
        <th>Statut</th>
    </thead>
    <tbody>
    <?php foreach($paymentRequests as $paymentRequest) : ?>
        <tr>
            <td><?php echo $paymentRequest->ID; ?></td>
            <td><?php echo $paymentRequest->DateTime; ?></td>
            <td><button class="btn btn-primary" onclick="getOrdersByPaymentRequestID(<?php echo $paymentRequest->ID ?>)">Voir les commandes</button></td>
            <td><?php echo number_format($paymentRequest->Total, 2); ?> €</td>
            <td><?php echo empty($paymentRequest->PaymentDateTime) ? 'En attente' : "<span class='text-success'>Payée le " . dateFormat($paymentRequest->PaymentDateTime) . "</span>"; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php /* HISTORIQUE DE PAIEMENT */ ?>
<h2 class="mb-5">Historique de commandes payées</h2>
<?php if (sizeof($paidPayments) > 0) : ?>
    <table class="table table-striped mb-5">
        <thead>
            <th>Date de paiement</th>
            <th>Montant</th>
        </thead>
        <tbody>
            <?php foreach ($paidPayments as $payment) : ?>
                <tr>
                    <td class="p-4"><?php echo dateFormat($payment->PayoutDateTime) ?></td>
                    <td class="p-4"><?php echo number_format($payment->getPartnerRevenue(), 2) ?> €</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else : ?>
    <p class="text-center mb-5">Aucun historique de paiement disponible.</p>
<?php endif; ?>


<?php if ($isAvailable) : ?>
    <div class="modal fade" id="paymentConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="title">Demande de paiement</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Voulez-vous envoyer une demande de paiement de <?php echo number_format($eligibleRevenue, 2) ?> € ?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <a href="?paymentRequest=true" type="button" class="btn btn-primary">Envoyer</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="modal fade" id="ordersDetails" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Commandes</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="body">
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/private/templates/main-template.php';
?>