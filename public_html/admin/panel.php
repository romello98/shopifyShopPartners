<?php

require_once dirname(__DIR__) . '/private/dataService.php';
require_once dirname(__DIR__) . '/private/security.php';

if(!is_authenticated(true))
{
    header('Location: /admin/');
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


$MONTHS = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$CURRENT_YEAR = date('Y');
$CURRENT_MONTH = date('n');
$currentUser = getCurrentLoggedUser();
setlocale(LC_TIME, "fr_FR");

$getMonth = get_clean_obtain('month');
$getYear = get_clean_obtain('year');

$currentYear = isValidYear($getYear) ? intval($getYear) : intval(date('Y'));
$currentMonth = isValidMonth($getMonth) ? intval($getMonth) : intval(date('n'));
$currentDay = date('j');

$dataService = new DataService();
$visits = $dataService->getAllVisitsByMonth($currentMonth, $currentYear);
$sales = $dataService->getAllPartnerSales($currentMonth, $currentYear);
$turnover = 0;
$iVisit = 0;
$iSale = 0;

$thisMonthDays = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

$graphHeight = 200;
$graphData = [];
$maxVisits = 0;
$maxSales = 0;
$totalMonthSales = 0;
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
        $turnover += $sales[$iSale]['Turnover'];
        if($totalSales > $maxSales)
        {
            $maxSales = $totalSales;
            $iDayMostSales = $iDay;
        }
        $iSale++;
    }
    else $totalSales = 0;
    $totalMonthSales += $totalSales;

    $graphData[$iDay] = [];
    $graphData[$iDay]['visits'] = $nbVisits;
    $graphData[$iDay]['sales'] = $totalSales;
}

$maxVisits = ceil(($maxVisits + 1) / 10) * 10;
$maxSales = ceil(($maxSales + 1) / 10) * 10;

?>

<?php ob_start(); ?>

<h1 class="mb-5">Activité</h1>
<table class="mb-5 summary">
    <style type="text/css" scoped>
        .data-graph.y<?php echo $CURRENT_YEAR ?>.m<?php echo $CURRENT_MONTH ?> td.d<?php echo $currentDay ?>
        {
            background-color: lightgoldenrodyellow !important;
        }
    </style>
    <thead>
        <th class="w-50">Mois</th>
        <th>Revenus des partenaires</th>
        <th>CA des partenaires</th>
        <th>Visites partenaires</th>
    </thead>
    <tbody>
        <tr>
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
            <td><?php echo number_format($totalMonthSales, 2); ?> €</td>
            <td><?php echo number_format($turnover, 2) ?> €</td>
            <td><?php echo $totalMonthVisits ?></td>
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
    require_once dirname(__DIR__) . '/private/templates/admin-template.php'; 
?>