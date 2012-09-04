<?php

class Bootstrap
{
    private $_app;
    private $_resources;
    private $_model;
    private $_store;

    public function __construct ($app)
    {
        $this->_app = $app;
        $this->_resources = array();
    }

    public function __destruct ()
    {
    }

    public function getResource ($resourceName)
    {
        $resourceName = ucfirst(strtolower($resourceName));

        if (!isset($this->_resources[$resourceName])) {
            $initMethod = 'init' . $resourceName;
            $this->_resources[$resourceName] = $this->$initMethod();
        }

        return $this->_resources[$resourceName];
    }

    private function initConfig ()
    {
        $configPath = $this->_app->getBaseDir() . 'config.ini';

        $configArray = parse_ini_file($configPath, true);

        // TODO merge with some default settings
        // TODO move most settings into the model

        return $configArray['xodx'];
    }

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

        return $erfurt->getStore();
    }

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

    private function initSession ()
    {
        // Session is started by Zend in Erfurt
        // session_start();

        $store = $this->getResource('store');
        $model = $this->getResource('model');

        return null;
    }

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

    private function initLogger ()
    {
        return new Xodx_Logger();
    }

}
