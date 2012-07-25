<?php

class Xodx_AuthController extends Xodx_Controller
{
    private $_user;
    private $_status;


    public function loginAction($template, $user = null, $password = null)
    {
        $bootstrap = $this->_app->getBootstrap();
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
