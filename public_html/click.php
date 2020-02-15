<?php

require_once __DIR__ . '/private/security.php';
require_once __DIR__ . '/private/dataService.php';

$affiliateCode = get_clean_obtain('affiliateCode');
$dataService = new DataService();

$dataService->addVisit($affiliateCode);

//TODO: connect to database to insert click