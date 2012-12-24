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
        $template = Saft_Template::getInstance();
        $template->setLayout('templates/layout.phtml');
        $template->addMenu('templates/menu.phtml');

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
        $template = $controller->$actionName($template);

        $template->username = $appController->getUser();

        $template->render();
    }
}
