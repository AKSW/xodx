<?php

/**
 * This Class is responsible to initialize resources and returning them on request.
 * It keeps a registry of the already initialized resources to not have to the work twice.
 */
class Bootstrap
{
    /**
     * A reference to the Application instance
     */
    private $_app;

    /**
     * The registry of already initialized resources
     */
    private $_resources;

    /**
     * The constructor of the Bootstrap class, should be called only once
     *
     * @param $app the instance of the Application
     */
    public function __construct ($app)
    {
        $this->_app = $app;
        $this->_resources = array();
    }

    /**
     * The descructor can be used to cleanup resources e.g. close file handlers
     */
    public function __destruct ()
    {
    }

    /**
     * Gives the requested resource from the registry, or initializes it if this wasn't done before
     *
     * @param $resourceName the name of the resource, which is needed
     * @return the resources object
     */
    public function getResource ($resourceName)
    {
        $resourceName = ucfirst(strtolower($resourceName));

        if (!isset($this->_resources[$resourceName])) {
            $initMethod = 'init' . $resourceName;
            $this->_resources[$resourceName] = $this->$initMethod();
        }

        return $this->_resources[$resourceName];
    }

    /**
     * Initializes the configuration by reading the config file
     */
    private function initConfig ()
    {
        $configPath = $this->_app->getBaseDir() . 'config.ini';

        $configArray = parse_ini_file($configPath, true);

        // TODO merge with some default settings
        // TODO move most settings into the model

        return $configArray['xodx'];
    }

    /**
     * Initializes the Erfurt Store
     */
    private function initStore ()
    {
        $configPath = $this->_app->getBaseDir() . 'config.ini';
        $erfurtConfig = null;

        if (is_readable($configPath)) {
            try {
                $erfurtConfig = new Zend_Config_Ini($configPath, 'erfurt');
            } catch (Exception $e) {
            }
        }

        // Creating an instance of Erfurt API
        // don't autostart it to set config
        $erfurt = Erfurt_App::getInstance(false);
        $erfurt->start($erfurtConfig);

        // TODO: add the stuff from
        // https://github.com/AKSW/Erfurt/wiki/Internals

        // Authentification on Erfurt (needed for model access)
        $dbUser = $erfurt->getStore()->getDbUser();
        $dbPass = $erfurt->getStore()->getDbPassword();
        $erfurt->authenticate($dbUser, $dbPass);

        if (!$erfurt->getStore()->isModelAvailable('http://ns.ontowiki.net/SysOnt/', true)) {
            // It seams the store is new, so we should run some setup stuff

            // Create SysOnt
            $t = time ();
            $erfurt->getStore()->getNewModel ( 'http://ns.ontowiki.net/SysOnt/' );
            $erfurt->getStore()->addStatement (
                'http://ns.ontowiki.net/SysOnt/', $t, $t,
                array('value' => $t, 'type' => 'literal')
            );
            $erfurt->getStore()->deleteMatchingStatements ( 
                'http://ns.ontowiki.net/SysOnt/', $t, $t,
                array('value' => $t, 'type' => 'literal')
            );

            // Creates cache tables
            $c = new Erfurt_Cache_Backend_QueryCache_Database ();
            $c->createCacheStructure ();

            // Creates versioning tables
            $v = new Erfurt_Versioning ();
            $v->isVersioningEnabled ();
        }

        return $erfurt->getStore();
    }

    /**
     * Initializes the default Erfurt Model as specified in the config
     */
    private function initModel ()
    {
        $model = null;
        $store = $this->getResource('store');
        $config = $this->getResource('config');

        $modelUri = $config['xodx.model'];

        if (empty($modelUri)) {
            throw new Exception('No xodx model configured. Please add "xodx.model" entry to "config.ini".');
        }

        // Get a new model
        try {
            // Create it if it doesn't exist
            $model = $store->getNewModel($modelUri);
        } catch (Erfurt_Store_Exception $e) {
            // Get it if it already exists
            $model = $store->getModel($modelUri);
        }

        return $model;
    }

    /**
     * Initializes the session
     * Note: currently this is done by initializing Erfurt
     */
    private function initSession ()
    {
        // Session is started by Zend in Erfurt
        // session_start();

        $store = $this->getResource('store');
        $model = $this->getResource('model');

        return null;
    }

    /**
     * Initializes the Request object
     */
    private function initRequest ()
    {
        $store = $this->getResource('session');

        $values = array();
        $values['session'] = $_SESSION;
        $values['get'] = $_GET;
        $values['post'] = $_POST;

        $values['all'] = array();

        foreach (array_keys($_GET) as $key) {
            $values['all'][$key] = 'get';
        }

        foreach (array_keys($_POST) as $key) {
            $values['all'][$key] = 'post';
        }

        foreach (array_keys($_SESSION) as $key) {
            $values['all'][$key] = 'session';
        }

        $request = new Xodx_Request($_SERVER['REQUEST_METHOD'], $values);

        $body = file_get_contents('php://input');

        if (!empty($body)) {
            $request->setBody($body);
        }

        return $request;
    }

    /**
     * Initializes the Logger Object
     */
    private function initLogger ()
    {
        return new Xodx_Logger();
    }

}
