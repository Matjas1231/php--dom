
<?php
libxml_use_internal_errors(true);
$doc = new DOMDocument();
$html = file_get_contents('wo_for_parse.html');
$arrayParams = [];


$doc->loadHTML($html);
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true;

$arrayParams['tracking_number'] = $doc->getElementById('wo_number')->textContent;
$arrayParams['po_number'] = $doc->getElementById('po_number')->textContent;


$dataScheduled = $doc->getElementById('scheduled_date')->textContent;

for ($m=1; $m<=12; $m++) {
    $month = date('F', mktime(0,0,0,$m, 1, date('Y')));
    if (strpos($dataScheduled, $month)) {
        $posOfMonth = strpos($dataScheduled, $month);
        $monthAndDay = substr($dataScheduled, $posOfMonth, strlen($month)+3);
        break;
        }
    }

$monthAndDay = explode(' ', $monthAndDay);
$month = $monthAndDay[0];
$month = date_parse($month);
$month = $month['month'];
$day = $monthAndDay[1];

$year = null;
$hour = null;
if (preg_match("/\d{4}/", $dataScheduled, $match)) {
  $year = $match[0];
}
if (preg_match('/(\d{2}):(\d{2} [A-Z]{2})/', $dataScheduled, $match)) {
    $hour = $match[0];
}

$dateContate = $year.'-'.$month.'-'.$day.' '.$hour;
$dateToString = strtotime($dateContate);
$date = date('Y-d-m H:i', $dateToString);

$arrayParams['data_scheduled'] = $date;

$customer = $doc->getElementById('customer')->textContent;
$arrayParams['customer'] = preg_replace(['(\s+)u', '(^\s|\s$)u'], [' ', ''], $customer);

$arrayParams['trade'] = $doc->getElementById('trade')->textContent;

$nte = $doc->getElementById('nte')->textContent;
$nte = str_replace(',', '', $nte);
$nte = str_replace('$', '', $nte);
$nte = number_format((float)$nte, 2,'.','');
$arrayParams['nte_usd'] = $nte;

$storeId = $doc->getElementById('store_id')->textContent;

if (!preg_match('/([A-Z]{3})-(\d{3})/', $storeId)) {
    $locationName = $doc->getElementById('location_name')->textContent;

    if (preg_match('/([A-Z]{3})-(\d{3})/', $locationName)) {
        $storeId = $locationName;
    } else {
        echo "Bad store id";
    }
}
$arrayParams['store_id'] = $storeId;



$street = $doc->getElementById('location_address')->firstElementChild->firstChild->textContent;
$normStreet = preg_replace(['(\s+)u', '(^\s|\s$)u'], [' ', ''], $street);
$arrayParams['street'] = $normStreet;


$cityAndState = $doc->getElementById('location_address')->firstElementChild->lastChild->textContent;
$normCityStateAndPostalCode = preg_replace(['(\s+)u', '(^\s|\s$)u'], [' ', ''], $cityAndState);
$arrayOfElements = explode(' ', $normCityStateAndPostalCode);
$city = $arrayOfElements[0];
$state = $arrayOfElements[1];
$postalCode = $arrayOfElements[2];
$arrayParams['city'] = $city;
$arrayParams['state'] = $state;
$arrayParams['postal_code'] = $postalCode;

$phone = $doc->getElementById('location_phone')->textContent;
$phone = explode('-', $phone);
$phone = implode('', $phone);
$arrayParams['phone'] = $phone;

libxml_use_internal_errors(false);

echo('<br>=======================<br>');
// print_r($arrayParams);

$delimiter = ';';
$filename = 'export_data.csv';
$headers = ['tracking_number', 'po_number', 'data_scheduled', 'customer', 'trade', 'nte_usd', 'store_id', 'street', 'city', 'state', 'postal_code', 'phone'];
$f = fopen($filename, 'w');
fputcsv($f, $headers, $delimiter);
$csvData = array($arrayParams['tracking_number'], $arrayParams['po_number'], $arrayParams['data_scheduled'], $arrayParams['customer'], $arrayParams['trade'], $arrayParams['nte_usd'], $arrayParams['store_id'], $arrayParams['street'], $arrayParams['city'], $arrayParams['state'], $arrayParams['postal_code'], $arrayParams['phone']);
var_dump($arrayParams);


fputcsv($f, $arrayParams, $delimiter);
fclose($f);