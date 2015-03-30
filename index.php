<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

$main_dir = rtrim(dirname(__FILE__), '/\\');

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

$app = new Xodx_Application();
$app->setAppNamespace('Xodx_');
$app->setBaseDir($main_dir);

// Check if Application should be started normally or to run the Worker
$options = getopt('j');
if (!isset($options['j'])) {
    // if the Application is started normally we assume to be in a server environment


    if (!empty($_SERVER['APP_BASE'])) {
        //is we receive an explicit setup for the app base, we use it
        $base_uri = $_SERVER['APP_BASE'];
    } else {
        //otherwise attempt to construct it from default headers
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }

        //use the whole request URI to obtain the app base path, since that seems the only reliable method
        $app_path = $_SERVER['REQUEST_URI'];

        //trim trailing query string if present
        if(!empty($_SERVER['QUERY_STRING'])) {
            $app_path = preg_replace('/'. preg_quote('?' . $_SERVER['QUERY_STRING'], '/') . '$/', '', $app_path);
        }

        //remove PHP script name and remaining suffix after it, if it occured explicitly in the requers URI
        if(preg_match('/^.*?(\w+\.php.*)$/', $app_path, $script_match) === 1) {
            $app_path = str_replace($script_match[1], '', $app_path);
        }

        //original hostname of the client query must be handed to the CGI call unmodified for this to work properly
        //HTTPD rewrite and proxy usage must be configured accordingly
        $base_uri = $protocol . "://" . $_SERVER['HTTP_HOST'] . $app_path;
    }

    // append trailing slash if not present
    if ($base_uri[strlen($base_uri) - 1] != '/') {
        $base_uri .= '/';
    }

    $app->setBaseUri($base_uri);

    $app->run();
} else {
    $app->runJobs();
}
