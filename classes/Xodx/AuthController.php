<?php

/**
 * This class implements the WebID Authentication Protocal (a.k.a. FOAF+SSL) and as fallback
 * a simple authentication with username and password.
 * The Fallback is important for users without WebID to first loggin and user which are using
 * a new browser which has not yes a WebID setup
 * WebID:
 * - http://www.w3.org/wiki/WebID
 * - http://www.w3.org/2005/Incubator/webid/wiki/Main_Page
 * - http://www.w3.org/2005/Incubator/webid/wiki/Implementations
 * - http://www.w3.org/2005/Incubator/webid/wiki/Apache_Configuration
 */
class Xodx_AuthController
{
    private $_user;
    private $_status;

    public function authenticate()
    {
        $app = Application::getInstance();
        $bootstrap = $app->getBootstrap();
        $request = $bootstrap->getResource('request');

        if ($request->getValue('logedin', 'session') === true) {
            $this->_user = $request->getValue('user');
        } else {
            $this->loginAction('guest');
        }
    }

    public function loginAction($template, $user = null, $password = null)
    {
        $app = Application::getInstance();
        $bootstrap = $app->getBootstrap();
        $request = $bootstrap->getResource('request');

        if ($user == null && $request->hasValue('user', 'post') && $request->hasValue('password', 'post')) {
            $user = $request->getValue('user', 'post');
            $password = $request->getValue('password', 'post');
        }

        if ($this->checkCredentials($user, $password)) {
            $_SESSION['user'] = $user;
            $_SESSION['logedin'] = true;
        }

        return $template;
    }

    public function checkCredentials()
    {
    }
}
