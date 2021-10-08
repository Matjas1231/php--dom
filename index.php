<?php
require_once('src/Controller.php');
use App\Controller;



$html = file_get_contents('wo_for_parse.html');
(new Controller())->exportCsv($html);
