<?php

require dirname(__FILE__) . '/classes/josecarlosphp/Http.php';
require dirname(__FILE__) . '/classes/josecarlosphp/Lister.php';
require dirname(__FILE__) . '/classes/josecarlosphp/Imagenator.php';

$configImagenator = include dirname(__FILE__) . '/../config/config.php';

$imagenator = new \josecarlosphp\Imagenator();
$imagenator->setConfig($configImagenator);
$imagenator->read();

unset($configImagenator);
