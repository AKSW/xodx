<?php

require_once 'Template.php';
require_once 'Bootstrap.php';

class Application
{
    private $_classNamespace = 'Xodx_';
    private $_bootstrap = null;
    private $_baseUri = null;
    private static $_app = null;

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

    public function run() {
        // TODO: parse request uri to determine correct controller

        $bootstrap = $this->getBootstrap();

        $auth = new Xodx_AuthController();
        $auth->authenticate();

        $template = Template::getInstance();
        $template->setLayout('templates/layout.phtml');
        $template->addContent('templates/debug.phtml');

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
        $controller = new $controllerName;
        $controller->$actionName();

        $template->addMenu('templates/menu.phtml');

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

