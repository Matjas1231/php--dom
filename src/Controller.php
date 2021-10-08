<?php
declare (strict_types=1);
namespace App;

use DOMDocument;

class Controller
{
    public function __construct()
    {           
        $this->doc = new DOMDocument();
    }

    public function exportCsv($html)
    {
        $arrayParams = $this->prepareData($html);
        $delimiter = ';';
        $filename = 'export_data.csv';
        $headers = ['tracking_number', 'po_number', 'data_scheduled', 'customer', 'trade', 'nte_usd', 'store_id', 'street', 'city', 'state', 'postal_code', 'phone'];
        $f = fopen($filename, 'w');

        fputcsv($f, $headers, $delimiter);
        fputcsv($f, $arrayParams, $delimiter);
        
        fclose($f);

        exit("Wyeksportowano plik: $filename");
    }
    
    private function prepareData($html): array
    {
        libxml_use_internal_errors(true);
        $this->doc->loadHTML($html);
        $this->doc->preserveWhiteSpace = false;
        $this->doc->formatOutput = true;
        $arrayParams = [];
        $arrayParams['tracking_number'] = $this->doc->getElementById('wo_number')->textContent;
        $arrayParams['po_number'] = $this->doc->getElementById('po_number')->textContent;
        $arrayParams['data_scheduled'] = $this->prepareDate($this->doc->getElementById('scheduled_date')->textContent);
        $arrayParams['customer'] = preg_replace(['(\s+)u', '(^\s|\s$)u'], [' ', ''], $this->doc->getElementById('customer')->textContent);
        $arrayParams['trade'] = $this->doc->getElementById('trade')->textContent;
        $arrayParams['nte_usd'] = $this->prepareNte($this->doc->getElementById('nte')->textContent);
        $arrayParams['store_id'] = $this->checkStoreId($this->doc->getElementById('store_id')->textContent);
        $arrayParams['street'] = preg_replace(['(\s+)u', '(^\s|\s$)u'], [' ', ''], $this->doc->getElementById('location_address')->firstElementChild->firstChild->textContent);

        $arrayCityStateAndPostal = $this->prepareAddress($this->doc->getElementById('location_address')->firstElementChild->lastChild->textContent);
        $arrayParams['city'] = $arrayCityStateAndPostal['city'];
        $arrayParams['state'] = $arrayCityStateAndPostal['state'];
        $arrayParams['postal_code'] = $arrayCityStateAndPostal['postalCode'];

        $arrayParams['phone'] = $this->preparePhone($this->doc->getElementById('location_phone')->textContent);

        libxml_use_internal_errors(false);
        return $arrayParams;
    }

    private function preparePhone($phone): string
    {
        $phone = explode('-', $phone);
        $phone = implode('', $phone);
        return $phone;
    }

    private function prepareAddress($cityAndState): array
    {
        $cityAndState = $this->doc->getElementById('location_address')->firstElementChild->lastChild->textContent;
        $normCityStateAndPostalCode = preg_replace(['(\s+)u', '(^\s|\s$)u'], [' ', ''], $cityAndState);
        $arrayOfElements = explode(' ', $normCityStateAndPostalCode);
        $city = $arrayOfElements[0];
        $state = $arrayOfElements[1];
        $postalCode = $arrayOfElements[2];
        
        return ['city' => $city, 'state' => $state, 'postalCode' => $postalCode];
    }

    private function checkStoreId($storeId)
    {
        if (!preg_match('/([A-Z]{3})-(\d{3})/', $storeId)) {
            $locationName = $this->doc->getElementById('location_name')->textContent;
        
            if (preg_match('/([A-Z]{3})-(\d{3})/', $locationName)) {
                $storeId = $locationName;
            } else {
                echo "Bad store id";
            }
        }

        return $storeId;
    }

    private function prepareNte($nte)
    {
        $nte = str_replace(',', '', $nte);
        $nte = str_replace('$', '', $nte);
        $nte = number_format((float)$nte, 2,'.','');
        return $nte;
    }

    private function prepareDate($dataScheduled)
    {
        for ($m=1; $m<=12; $m++) {
            $month = date('F', mktime(0,0,0,$m, 1, (int) date('Y')));
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
            return date('Y-d-m H:i', $dateToString);            
        }
}
