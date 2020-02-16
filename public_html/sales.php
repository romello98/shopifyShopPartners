<?php

require_once __DIR__ . '/private/dataService.php';
require_once __DIR__ . '/private/security.php';

if(!is_authenticated())
{
    header('Location: login.php');
}

setlocale(LC_TIME, "fr_FR");

$currentUser = getCurrentLoggedUser();
$dataService = new DataService();
$sales = $dataService->getPagedSalesByPartner($currentUser->ID);

?>

<?php ob_start(); ?>

<h1 class="mb-5">Ventes</h1>

<table class="table table-striped sales">
    <thead>
        <tr>
            <th>Date de paiement</th>
            <th>Articles</th>
            <th>Montant (€)</th>
            <th>Commission appliquée</th>
            <th>Gains (€)</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
<?php foreach($sales as $sale) : ?>
    <?php $hasBonus = $sale->BonusCommissionPercentage !== null; ?>
        <tr>
            <td>
                <p><?php echo dateFormat($sale->PaymentDateTime); ?>
            </td>
            <td>
                <p>Exemple d'article</p>
            </td>
            <td class="money">
                <p><?php echo number_format($sale->Amount, 2); ?> €</p>
            </td>
            <td>
                <?php if($hasBonus) : ?>
                    <del class="text-secondary"><?php echo number_format($sale->CommissionPercentage * 100, 2) ?> %</del>
                    <span class="text-success font-weight-bold"><?php echo number_format($sale->BonusCommissionPercentage * 100, 2) ?> %</span>
                <?php else : ?>
                    <p><?php echo number_format($sale->CommissionPercentage * 100, 2) ?> %</p>
                <?php endif; ?>
            </td>
            <td class="money">
                <?php if($hasBonus) : ?>
                    <del class="text-secondary"><?php echo number_format($sale->Amount * $sale->CommissionPercentage, 2) ?> €</del>
                    <span class="text-success font-weight-bold"><?php echo number_format($sale->Amount * $sale->BonusCommissionPercentage, 2) ?> €</span>
                <?php else : ?>
                    <p><?php echo number_format($sale->Amount * $sale->CommissionPercentage, 2) ?> €</p>
                <?php endif; ?>
               
            </td>
            <td>
                <p class="status <?php echo $sale->Status; ?>"><?php echo $sale->getStatusLabel(); ?></p>
            </td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>

<?php 
    $content = ob_get_clean();
    require_once __DIR__ . '/private/templates/main-template.php'; 
?>