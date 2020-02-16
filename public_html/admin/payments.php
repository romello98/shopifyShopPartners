<?php

require_once dirname(__DIR__) . '/private/dataService.php';
require_once dirname(__DIR__) . '/private/security.php';

if(!is_authenticated(true))
{
    header('Location: /admin/');
}

$dataService = new DataService();
$isPayment = get_clean_obtain('pay') == 'true';

if($isPayment)
{
    $paymentRequestID = get_clean_obtain('ID');
    $paymentSuccessful = $dataService->makePayment($paymentRequestID);
}

$allPaymentRequests = $dataService->getPaymentRequests(null, true);

?>

<?php ob_start(); ?>

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

<h1 class="mb-5">Paiements</h1>

<?php if($isPayment) : ?>
    <?php if($paymentSuccessful) : ?>
        <p class="alert alert-success">Le paiement a été validé avec succès.</p>
    <?php else : ?>
        <p class="alert alert-danger">Une erreur est survenue lors du paiement.</p>
    <?php endif; ?>
<?php endif; ?>

<table class="table table-striped mb-5">
    <thead>
        <th>ID</th>
        <th>Date</th>
        <th>Ventes concernées</th>
        <th>Montant</th>
        <th>Statut</th>
    </thead>
    <tbody>
    <?php foreach($allPaymentRequests as $paymentRequest) : ?>
        <tr>
            <td><?php echo $paymentRequest->ID; ?></td>
            <td><?php echo $paymentRequest->DateTime; ?></td>
            <td><button class="btn btn-primary" onclick="getOrdersByPaymentRequestID(<?php echo $paymentRequest->ID ?>)">Voir les commandes</button></td>
            <td><?php echo number_format($paymentRequest->Total, 2); ?> €</td>
            <td><?php echo empty($paymentRequest->PaymentDateTime) ? "<a class=\"btn btn-primary\" href=\"?pay=true&ID=$paymentRequest->ID\">Payer</a>" : "<span class='text-success'>Payée le " . dateFormat($paymentRequest->PaymentDateTime) . "</span>"; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

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
    require_once dirname(__DIR__) . '/private/templates/admin-template.php'; 
?>