<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

// include password hash functions for 5.3.7 <= PHP < 5.5
require_once('password_compat/lib/password.php');

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
class Xodx_ApplicationController extends Saft_Controller
{
    /**
     * This is the username of the currently logedin user
     */
    private $_user;

    /**
     * Create a new user account including a sioc:UserAccount, a foaf:PersonalProfileDocument and a
     * foaf:Person.
     *
     * @param personUri (post) optionally an already existing webid which will be imported to the
     *          newly created persion.
     * @param username (post) a username chosen by the user which should not be already registered
     * @param password (post) a password chosen by the user (this will be plain, TODO: encrypt)
     */
    public function newuserAction ($template) {
        $nsPingback = 'http://purl.org/net/pingback/';
        $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsRdfs = 'http://www.w3.org/2000/01/rdf-schema#';
        $nsFoaf = 'http://xmlns.com/foaf/0.1/';
        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsXodx = 'http://example.org/voc/xodx/';
        $nsDssn = 'http://purl.org/net/dssn/';

        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $store = $bootstrap->getResource('store');
        $request = $bootstrap->getResource('request');

        // get URI
        $personUri = $request->getValue('personUri', 'post');
        $username = $request->getValue('username', 'post');
        $password = $request->getValue('password', 'post');
        $passwordVerify = $request->getValue('passwordVerify', 'post');

        if ($personUri === null && $username === null && $password === null && $passwordVerify === null) {
            $template->addContent('templates/newuser.phtml');
        } else {

            // verify form data
            $formError = array();

            // TODO check if username is already taken

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

                $memModel = null;

                if (!empty($personUri)) {
                    $newStatements = Saft_Tools::getLinkedDataResource($this->_app, $personUri);

                    $memModel = new Erfurt_Rdf_MemoryModel($newStatements);

                    //$store->addMultipleStatements($graphUri, $newStatements);

                    $template->addDebug(var_export($newStatements, true));
                }

                // create new person
                $newPersonUri = $this->_app->getBaseUri() . '?c=person&id=' . urlencode($username);
                $newUserUri = $this->_app->getBaseUri() . '?c=user&id=' . urlencode($username);
                $newPersonFeed = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri='
                    . urlencode($newPersonUri);
                $newPerson = array(
                    $newPersonUri => array(
                        $nsRdf . 'type' => array(
                            array(
                                'type' => 'uri',
                                'value' => $nsFoaf . 'Person'
                            )
                        ),
                        $nsFoaf . 'account' => array(
                            array(
                                'type' => 'uri',
                                'value' => $newUserUri
                            )
                        ),
                        $nsFoaf . 'nick' => array(
                            array(
                                'type' => 'literal',
                                'value' => $username
                            )
                        ),
                        $nsDssn . 'activityFeed' => array(
                            array(
                                'type' => 'uri',
                                'value' => $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
                                    urlencode($newPersonUri)
                            )
                        )
                    )
                );

                // add seeAlso relation if a former uri was specified
                if (!empty($personUri)) {
                    $newPerson[$newPersonUri][$nsRdfs . 'seeAlso'] = array(
                        array(
                            'type' => 'uri',
                            'value' => $personUri
                        )
                    );
                }

                // add all statements about the former person to the new one
                if ($memModel !== null) {
                    $memStmt = $memModel->getPO($personUri);
                    foreach ($memStmt as $p => $o) {
                        if (isset($newPerson[$newPersonUri][$p])) {
                            $newPerson[$newPersonUri][$p] = array_merge($o, $newPerson[$newPersonUri][$p]);
                        } else {
                            $newPerson[$newPersonUri][$p] = $o;
                        }
                    }
                }

                $store->addMultipleStatements($graphUri, $newPerson);

                $newUser = array(
                     $newUserUri => array(
                        $nsRdf . 'type' => array(
                            array(
                                'type' => 'uri',
                                'value' => $nsSioc . 'UserAccount'
                            )
                        ),
                        $nsFoaf . 'accountName' => array(
                            array(
                                'type' => 'literal',
                                'value' => $username
                            )
                        ),
                        $nsXodx . 'hasPassword' => array(
                            array(
                                'type' => 'literal',
                                'value' => password_hash($password, PASSWORD_DEFAULT)
                            )
                        ),
                        $nsSioc . 'account_of' => array(
                            array(
                                'type' => 'uri',
                                'value' => $newPersonUri
                            )
                        )
                    )
                );
                $store->addMultipleStatements($graphUri, $newUser);

                $newProfile = array(
                    $this->_app->getBaseUri() . '?c=profile&id=' . urlencode($username) => array(
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
                        )
                    )
                );
                $store->addMultipleStatements($graphUri, $newProfile);

            } else {
                $template->formError = $formError;
                $template->addContent('templates/newuser.phtml');
            }
        }

        return $template;
    }

    /**
     * The login action takes the given credentials and calls the login method with them
     *
     * @param username (post) the username used for the login
     * @param password (post) the password used for the login (TODO: encrypt)
     */
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

    /**
     * The login method checks the given credentials and changes the session properties, if login
     * was successfull.
     *
     * @param $username the username to be verified and logged in
     * @param $password the password to be verified
     * @return boolean if the login was successfull
     */
    public function login ($username = null, $password = null)
    {
        if ($username == 'guest' || ($username !== null && $password !== null)) {
            $userController = $this->_app->getController('Xodx_UserController');

            if ($username == 'guest') {
                $_SESSION['username'] = $username;
                $_SESSION['logedin'] = false;

                $this->_user = $username;

                return true;
            } else if ($userController->verifyPasswordCredentials($username, $password)) {
                $_SESSION['username'] = $username;
                $_SESSION['logedin'] = true;

                $this->_user = $username;

                return true;
            } else {
                throw new Exception('The provided credentials are incorrect.');
            }
        } else {
            return false;
        }
    }

    /**
     * Checks if thre is a logged in user in the session, alse login as guest:guest
     */
    public function authenticate ()
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        if (!($request->getValue('logedin', 'session') === true)) {
            $this->login('guest');
            $this->_user = 'guest';
        } else {
            $this->_user = $request->getValue('username');
        }
    }

    /**
     * Returns the currently logged in user
     */
    public function getUser ()
    {
        return $this->_user;
    }
}
