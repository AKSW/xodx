<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_Application extends Saft_Application
{
    public function run() {
        parent::run();

        // TODO: parse request uri to determine correct controller

        $bootstrap = $this->getBootstrap();

        $appController = $this->getController('Xodx_ApplicationController');
        $appController->authenticate();

        /**
         * Prepare Template
         */
        $this->_layout->setLayout('templates/layout.phtml');
        $this->_layout->addMenu('templates/menu.phtml');

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

        $controllerName = $this->_appNamespace . $requestController . 'Controller';

        $actionName = $requestAction . 'Action';
        $controller = $this->getController($controllerName);
        $this->_layout = $controller->$actionName($this->_layout);

        $userController = $this->getController('Xodx_UserController');
        $user = $userController->getUser();

        $this->_layout->username = $user->getName();
        $this->_layout->notifications = $userController->getNotifications($user->getUri());

        $config = $bootstrap->getResource('config');
        if (isset($config['debug']) && $config['debug'] == false) {
            $this->_layout->disableDebug();
        }

        $this->_layout->render();
    }
}
