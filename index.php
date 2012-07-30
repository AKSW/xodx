<?php

$main_dir = rtrim(dirname(__FILE__), '/\\');
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") ? "https" : "http";
$base_uri =  $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

// append trailing slash if not present
if ($base_uri[strlen($base_uri) - 1] != '/') {
    $base_uri .= '/';
}

# Set include paths
$includePath  = get_include_path() . PATH_SEPARATOR;

$includePath .= $main_dir . '/classes/' . PATH_SEPARATOR;
$includePath .= $main_dir . '/classes/Xodx/' . PATH_SEPARATOR;
$includePath .= $main_dir . '/libraries/' . PATH_SEPARATOR;
$includePath .= $main_dir . '/libraries/Erfurt/library' . PATH_SEPARATOR;

set_include_path($includePath);

# Include Zend Autoloader
require_once 'Zend/Loader/Autoloader.php';
require_once 'Application.php';

# Configure Zend Autoloader
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Erfurt_');
$autoloader->registerNamespace('Xodx_');

$app = Application::getInstance();
$app->setBaseUri($base_uri);
$app->setBaseDir($main_dir);
$app->run();
