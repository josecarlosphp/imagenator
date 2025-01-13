<?php

require 'inc/init.php';

$i = isset($_GET['i']) ? $_GET['i'] : '';
$r = isset($_GET['r']) ? $_GET['r'] : '';

$imagenator->view($i, $r);