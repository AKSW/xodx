<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

$main_dir = rtrim(dirname(__FILE__), '/\\');

if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
    $protocol = 'https';
} else {
    $protocol = 'http';
}

$base_uri =  $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

// append trailing slash if not present
if ($base_uri[strlen($base_uri) - 1] != '/') {
    $base_uri .= '/';
}

if ($main_dir[strlen($main_dir) - 1] != '/') {
    $main_dir .= '/';
}

# Set include paths
$includePath  = get_include_path() . PATH_SEPARATOR;

$includePath .= $main_dir . '/classes/' . PATH_SEPARATOR;
$includePath .= $main_dir . '/classes/Xodx/' . PATH_SEPARATOR;
$includePath .= $main_dir . '/libraries/' . PATH_SEPARATOR;
$includePath .= $main_dir . '/libraries/Erfurt/library/' . PATH_SEPARATOR;
$includePath .= $main_dir . '/libraries/lib-dssn-php/' . PATH_SEPARATOR;
$includePath .= $main_dir . '/libraries/ARC2/' . PATH_SEPARATOR;

set_include_path($includePath);

# Include Zend Autoloader
require_once 'Zend/Loader/Autoloader.php';

# Configure Zend Autoloader
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Erfurt_');
$autoloader->registerNamespace('Saft_');
$autoloader->registerNamespace('Xodx_');
$autoloader->registerNamespace('DSSN_');
$autoloader->registerNamespace('ARC2_');

DSSN_Utils::setConstants();

DSSN_Utils::setConstants();

// Check if Application or Worker should be started
$app = new Xodx_Application();
$app->setAppNamespace('Xodx_');
$app->setBaseUri($base_uri);
$app->setBaseDir($main_dir);
$app->run();
