<?php

require_once __DIR__ . '/private/dataService.php';
require_once __DIR__ . '/private/security.php';

if(!is_authenticated())
{
    header('Location: login.php');
}

function isValidYear($year)
{
    global $CURRENT_YEAR;
    return is_numeric($year) && $year <= $CURRENT_YEAR && $year >= $CURRENT_YEAR - 5;
}

function isValidMonth($month)
{
    return is_numeric($month) && $month <= 12 && $month >= 1;
}

$MIN_TURNOVER_FOR_BONUS = 500;
$COMMISSION_BONUS = 0.25;
$dataService = new DataService();
$currentUser = getCurrentLoggedUser();
setlocale(LC_TIME, "fr_FR");

$MONTHS = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$CURRENT_YEAR = date('Y');
$CURRENT_MONTH = date('n');

$getMonth = get_clean_obtain('month');
$getYear = get_clean_obtain('year');

$currentYear = isValidYear($getYear) ? intval($getYear) : intval(date('Y'));
$currentMonth = isValidMonth($getMonth) ? intval($getMonth) : intval(date('n'));
$currentDay = date('j');

$isCurrentMonth = $CURRENT_MONTH == $currentMonth && $CURRENT_YEAR == $currentYear;
$hasAlreadyHadBonus = $dataService->hasAlreadyHadMonthlyBonus($currentUser->ID, $currentMonth, $currentYear);

$visits = $dataService->getVisitsByPartnerIdAndMonth($currentUser->ID, $currentMonth, $currentYear);
$sales = $dataService->getSalesByPartnerIdAndMonth($currentUser->ID, $currentMonth, $currentYear);
$turnover = 0;
$iVisit = 0;
$iSale = 0;

$thisMonthVisits = $dataService->getVisitsByPartnerIdAndMonth($currentUser->ID, $currentMonth, $currentYear);
$thisMonthDays = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

$graphHeight = 200;
$graphData = [];
$maxVisits = 0;
$maxSales = 0;
$totalMonthSales = 0;
$totalMonthNoBonusSales = 0;
$totalMonthVisits = 0;
$iDayMostVisits = 1;
$iDayMostSales = 1;

for($iDay = 1; $iDay <= $thisMonthDays; $iDay++)
{
    if(!empty($visits[$iVisit]) && $visits[$iVisit]['Day'] == $iDay)
    {
        $nbVisits = $visits[$iVisit]['TotalVisits'];
        if($nbVisits > $maxVisits)
        {
            $maxVisits = $nbVisits;
            $iDayMostVisits = $iDay;
        }
        $iVisit++;
    }
    else $nbVisits = 0;
    $totalMonthVisits += $nbVisits;

    if(!empty($sales[$iSale]) && $sales[$iSale]['Day'] == $iDay)
    {
        $totalSales = $sales[$iSale]['TotalSales'];
        $noBonusTotalSales = $sales[$iSale]['NoBonusTotalSales'];
        $turnover += $sales[$iSale]['Turnover'];
        if($totalSales > $maxSales)
        {
            $maxSales = $totalSales;
            $iDayMostSales = $iDay;
        }
        $iSale++;
    }
    else 
    { 
        $totalSales = 0;
        $noBonusTotalSales = 0;
    }
    $totalMonthSales += $totalSales;
    $totalMonthNoBonusSales += $noBonusTotalSales;

    $graphData[$iDay] = [];
    $graphData[$iDay]['visits'] = $nbVisits;
    $graphData[$iDay]['sales'] = $totalSales;
}

$maxVisits = ceil(($maxVisits + 1) / 10) * 10;
$maxSales = ceil(($maxSales + 1) / 10) * 10;

?>

<?php ob_start(); ?>

<h1 class="mb-5">Activité</h1>
<h2 class="mb-3">Progression</h2>
<?php $remainingTurnoverForBonus = ($MIN_TURNOVER_FOR_BONUS - $turnover); ?>
<?php if(!$hasAlreadyHadBonus && $remainingTurnoverForBonus > 0) : ?>
    <?php if($isCurrentMonth) : ?>
    <p class="text-secondary">
        Il vous reste 
            <span class="font-weight-bold text-success"><?php echo number_format($remainingTurnoverForBonus, 2) ?> €</span> 
        à réaliser pour faire passer votre commission de 
            <span class="current-commission font-weight-bold"><?php echo number_format($currentUser->CommissionPercentage * 100, 2) ?> %</span>
        à 
            <span class="bonus-commission font-weight-bold text-success"><?php echo number_format($COMMISSION_BONUS * 100, 2) ?> %</span> !
    </p>
    <?php else : ?>
        <p class="text-secondary">Vous n'aviez pas atteint le seuil bonus de ce mois.</p>
    <?php endif; ?>
<?php else : ?>
    <p class="text-success">Vous avez atteint le seuil bonus pour ce mois, félicitations pour vos ventes !</p>
<?php endif; ?>
<div class="progress mb-5">
    <div data-toggle="tooltip" data-placement="top" title="<?php echo number_format($turnover, 2) . ' € / ' . number_format($MIN_TURNOVER_FOR_BONUS, 2) . ' €' ?>" class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: <?php echo $remainingTurnoverForBonus <= 0 ? '100' : (($MIN_TURNOVER_FOR_BONUS - $remainingTurnoverForBonus) / $MIN_TURNOVER_FOR_BONUS * 100); ?>%" aria-valuenow="<?php echo $turnover ?>" aria-valuemin="10" aria-valuemax="<?php echo $MIN_TURNOVER_FOR_BONUS ?>"></div>
</div>
<table class="mb-5 summary">
    <style type="text/css" scoped>
        .data-graph.y<?php echo $CURRENT_YEAR ?>.m<?php echo $CURRENT_MONTH ?> td.d<?php echo $currentDay ?>
        {
            background-color: #DDDDDD !important;
        }
    </style>
    <thead>
        <th>Vos revenus</th>
        <th>Chiffre d'affaire</th>
        <th>Visites</th>
        <th class="w-50">Mois</th>
    </thead>
    <tbody>
        <tr>
            <td>
                <?php if($totalMonthNoBonusSales != $totalMonthSales) : ?>
                    <del class="text-secondary"><?php echo number_format($totalMonthNoBonusSales, 2); ?> €</del>
                    <span class="text-success"><?php echo number_format($totalMonthSales, 2) ?> €</span>
                <?php else : ?>
                    <span><?php echo number_format($totalMonthSales, 2) ?> €</span>
                <?php endif; ?>
            </td>
            <td>
                <?php echo number_format($turnover, 2) ?> €
            </td>
            <td><?php echo $totalMonthVisits ?></td>
            <td>
                <form method="GET" action="" class="d-flex">
                <select class="form-control mr-2" name="month">
                    <?php foreach($MONTHS as $i => $month) : ?>
                        <option value="<?php echo ($i + 1); ?>" <?php echo $currentMonth == ($i + 1) ? 'selected' : '' ?>>
                            <?php echo $month; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="form-control mr-2" name="year">
                    <?php for($year = $CURRENT_YEAR; $year >= $CURRENT_YEAR  - 5; $year--) : ?>
                        <option value="<?php echo $year; ?>" <?php echo $currentYear == $year ? 'selected' : '' ?>><?php echo $year; ?></option>
                    <?php endfor; ?>
                </select>
                <button class="btn btn-primary" type="submit">Voir</button>
                </form>
            </td>
        </tr>
    </tbody>
</table>
<div class="data-graph y<?php echo $currentYear ?> m<?php echo $currentMonth ?>">
    <table>
        <caption>
            <div class="horizontal-bar"></div>
            <div class="horizontal-bar" style="margin-top: calc(30px + <?php echo $graphHeight / 2 ?>px);"></div>
        </caption>
        <thead>
            <tr>
                <td class="units">
                    <div class="left-y-axis" style="height: <?php echo $graphHeight; ?>px;">
                        <p class="y-axis-step"><?php echo $maxVisits; ?></p>
                        <div class="fill-available"></div>
                        <p class="y-axis-step"><?php echo $maxVisits / 2;?></p>
                        <div class="fill-available"></div>
                    </div>
                </td>
                <?php foreach($graphData as $dayNumber => $dataDetails) : ?>
                    <td class="x-axis-step d<?php echo $dayNumber ?>" data-html="true" data-toggle="tooltip" data-placement="top" title="
                    <p class='mb-0 text-left'><?php echo sprintf('%02d',$dayNumber) . "/" . sprintf('%02d', $currentMonth) . "/$currentYear" ?></p>
                    <h6 class='mb-0 text-left'>Revenus: <?php echo number_format($dataDetails['sales'], 2) . ' €' ?>
                    </h6><h6 class='mb-0 text-left'>Visites: <?php echo $dataDetails['visits']; ?></h6>">
                        <div class="data-content d-flex flex-row" style="height: <?php echo $graphHeight?>px">
                            <?php
                            $visitsBarHeight = ($dataDetails['visits'] / ($maxVisits == 0 ? 1 : $maxVisits) * $graphHeight) . 'px';
                            $salesBarHeight = ($dataDetails['sales'] / ($maxSales == 0 ? 1 : $maxSales) * $graphHeight) . 'px';
                            ?>
                            <div class="graph-bar">
                                <div class="fill-available"></div>
                                <div data-toggle="tooltip" data-placement="top" title="<?php echo $dataDetails['visits']; ?> visites" class="graph-bar-data" style="height: <?php echo $visitsBarHeight ?>;"></div>
                            </div>
                            <div class="graph-bar">
                                <div class="fill-available"></div>
                                <div data-toggle="tooltip" data-placement="top" title="<?php echo number_format($dataDetails['sales'], 2); ?> €" class="graph-bar-data" style="height: <?php echo $salesBarHeight ?>;"></div>
                            </div>
                        </div>
                    </td>
                <?php endforeach; ?>
                <td class="units">
                    <div class="left-y-axis" style="height: <?php echo $graphHeight; ?>px;">
                        <p class="y-axis-step"><?php echo number_format($maxSales, 2); ?> €</p>
                        <div class="fill-available"></div>
                        <p class="y-axis-step"><?php echo number_format($maxSales / 2, 2);?> €</p>
                        <div class="fill-available"></div>
                    </div>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="units">Visites</td>
                <?php foreach($graphData as $dayNumber => $dataDetails) : ?>
                    <td class="text-center d<?php echo $dayNumber ?>"><?php echo "$dayNumber" ?></td>
                <?php endforeach; ?>
                <td class="units">Ventes (€)</td>
            </tr>
        </tbody>
    </table>
</div>

<?php 
    $content = ob_get_clean();
    require_once __DIR__ . '/private/templates/main-template.php'; 
?>