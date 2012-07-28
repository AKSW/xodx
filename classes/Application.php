<?php

require_once 'Template.php';
require_once 'Bootstrap.php';

class Application
{
    private static $_app = null;

    private $_classNamespace = 'Xodx_';
    private $_bootstrap = null;
    private $_baseUri = null;

    private $_controllers = array();

    public static function getInstance()
    {
        if (self::$_app == null) {
            self::$_app = new Application();
        }
        return self::$_app;
    }

    public function getBootstrap()
    {
        if (!isset($this->_bootstrap)) {
            $this->_bootstrap = new Bootstrap();
        }

        return $this->_bootstrap;
    }

    public function getController ($controllerName)
    {
        if (!isset($this->_controllers[$controllerName])) {
            $this->_controllers[$controllerName] = new $controllerName($this);
        }

        return $this->_controllers[$controllerName];
    }

    public function run() {
        // TODO: parse request uri to determine correct controller

        $bootstrap = $this->getBootstrap();

        $appController = $this->getController('Xodx_ApplicationController');
        $appController->authenticate();

        /**
         * Prepare Template
         */
        $template = Template::getInstance();
        $template->setLayout('templates/layout.phtml');
        $template->addMenu('templates/menu.phtml');

        $template->username = $appController->getUser();

        $request = $bootstrap->getResource('request');

        if ($request->hasValue('c')) {
            $requestController = ucfirst(strtolower($request->getValue('c'))); // 'get'
        } else {
            $requestController = ucfirst(strtolower('index'));
        }

        if ($request->hasValue('a')) {
            $requestAction = strtolower($request->getValue('a'));

        } else {
            $requestAction = 'index';
        }

        $controllerName = $this->_classNamespace . $requestController . 'Controller';

        $actionName = $requestAction . 'Action';
        $controller = $this->getController($controllerName);
        $template = $controller->$actionName($template);

        $template->render();
    }

    public function setBaseUri ($baseUri)
    {
        $this->_baseUri = $baseUri;
    }

    public function getBaseUri ()
    {
        return $this->_baseUri;
    }

    public function setBaseDir ($baseDir)
    {
        $this->_baseDir = $baseDir;
    }

    public function getBaseDir ()
    {
        return $this->_baseDir;
    }
}
