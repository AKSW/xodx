<?php

require_once 'Tools.php';

/**
 * This class implements general action for administration and other business processes
 * and:
 * This class implements the WebID Authentication Protocal (a.k.a. FOAF+SSL) and as fallback
 * a simple authentication with username and password.
 * The Fallback is important for users without WebID to first login and user which are using
 * a new browser which has not yes a WebID setup
 * WebID:
 * - http://www.w3.org/wiki/WebID
 * - http://www.w3.org/2005/Incubator/webid/wiki/Main_Page
 * - http://www.w3.org/2005/Incubator/webid/wiki/Implementations
 * - http://www.w3.org/2005/Incubator/webid/wiki/Apache_Configuration
 */
class Xodx_ApplicationController extends Xodx_Controller
{
    /**
     * This is the username of the currently logedin user
     */
    private $_user;

    public function newuserAction ($template) {
        $nsPingback = 'http://purl.org/net/pingback/';
        $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsFoaf = 'http://xmlns.com/foaf/0.1/';
        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsXodx = 'http://example.org/voc/xodx/';

        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $store = $bootstrap->getResource('store');
        $request = $bootstrap->getResource('request');

        // get URI
        $personUri = $request->getValue('personUri', 'post');
        $username = $request->getValue('username', 'post');
        $password = $request->getValue('password', 'post');
        $passwordVerify = $request->getValue('passwordVerify', 'post');

        // verify form data
        $formError = array();

        // TODO check of personUri is a uri and is available
        if (false) {
            $formError['personUri'] = true;
        }

        // TODO check other things, e.g. (un)allowed chars, uri compatible …
        if (empty($username)) {
            $formError['username'] = true;
        }

        if ($password != $passwordVerify) {
            $formError['password'] = true;
        }

        if (count($formError) <= 0) {
            $graphUri = $model->getModelIri();

            if (!empty($personUri)) {
                $newStatements = Tools::getLinkedDataResource($personUri);
                $store->addMultipleStatements($graphUri, $newStatements);

                $template->addDebug(var_export($newStatements, true));
            }

            $newPersonUri = $this->_app->getBaseUri() . '?c=person&a=get&username=' . urlencode($username);
            $newPerson = array(
                $newPersonUri => array(
                    $nsRdf . 'type' => array(
                        array(
                            'type' => 'uri',
                            'value' => $nsFoaf . 'Person'
                        )
                    ),
                )
            );
            $store->addMultipleStatements($graphUri, $newPerson);

            $newUserUri = $this->_app->getBaseUri() . '?c=user&a=get&username=' . urlencode($username);
            $newUser = array(
                 $newUserUri => array(
                    $nsRdf . 'type' => array(
                        array(
                            'type' => 'uri',
                            'value' => $nsSioc . 'UserAccount'
                        )
                    ),
                    $nsXodx . 'password' => array(
                        array(
                            'type' => 'literal',
                            'value' => md5($password)
                        )
                    ),
                    $nsSioc . 'account_of' => array(
                        array(
                            'type' => 'uri',
                            'value' => $newPersonUri
                        )
                    ),
                )
            );
            $store->addMultipleStatements($graphUri, $newUser);

            $newProfile = array(
                $this->_app->getBaseUri() . '?c=profile&a=get&username=' . urlencode($username) => array(
                    $nsRdf . 'type' => array(
                        array(
                            'type' => 'uri',
                            'value' => $nsFoaf . 'PersonalProfileDocument'
                        )
                    ),
                    $nsFoaf . 'primaryTopic' => array(
                        array(
                            'type' => 'uri',
                            'value' => $newPersonUri
                        )
                    ),
                )
            );
            $store->addMultipleStatements($graphUri, $newProfile);

        } else {
            $template->formError = $formError;
            $template->addContent('templates/newuser.phtml');
        }

        return $template;
    }

    public function loginAction ($template) {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        $username = $request->getValue('username', 'post');
        $password = $request->getValue('password', 'post');

        if ($this->login($username, $password)) {
        } else {
            $template->addContent('templates/login.phtml');
        }

        return $template;
    }

    public function login ($username = null, $password = null)
    {
        if ($username == 'guest' || ($username !== null && $password !== null)) {
            $userController = $this->_app->getController('Xodx_UserController');

            if ($username == 'guest') {
                $_SESSION['username'] = $username;
                $_SESSION['logedin'] = false;

                return true;
            } else if ($userController->verifyPasswordCredentials($username, $password)) {
                $_SESSION['username'] = $username;
                $_SESSION['logedin'] = true;

                return true;
            } else {
                throw new Exception('The provided credentials are incorrect.');
            }
        } else {
            return false;
        }
    }

    public function authenticate ()
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        if (!($request->getValue('logedin', 'session') === true)) {
            $this->login('guest');
        }
        $this->_user = $request->getValue('username');
    }

    public function getUser ()
    {
        return $this->_user;
    }
}
