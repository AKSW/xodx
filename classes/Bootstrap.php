<?php

class Bootstrap
{
    private $_resources;
    private $_model;
    private $_store;

    public function __construct ()
    {
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

    private function initStore ()
    {
        // Creating an instance of Erfurt API
        $erfurt = Erfurt_App::getInstance( );

        // TODO: add the stuff from
        // https://github.com/AKSW/Erfurt/wiki/Internals

        // Authentification on Erfurt (needed for model access)
        $dbUser = $erfurt->getStore()->getDbUser();
        $dbPass = $erfurt->getStore()->getDbPassword();
        $erfurt->authenticate( $dbUser, $dbPass );

        return $erfurt->getStore();
    }

    private function initModel ()
    {
        $model = null;
        $store = $this->getResource('store');
        // Get a new model
        try {
            // Create it if it doesn't exist
            $model = $store->getNewModel('http://localhost/Xodx');
        } catch (Erfurt_Store_Exception $e) {
            // Get it if it already exists
            $model = $store->getModel('http://localhost/Xodx');
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

        return $request;
    }

    private function initLogger ()
    {
        return new Xodx_Logger();
    }

}
