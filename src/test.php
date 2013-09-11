<?php
require 'temploader.php';

use igorw\edn;

$person = new edn\Map();
$person[edn\keyword('name')] = 'igorw';

var_dump($person);
