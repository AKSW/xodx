<?php

class Xodx_Oauth2Controller extends Saft_Controller
{
	protected $oauth_uri;	
	protected $redirect_uri;
	protected $client_id;
	protected $client_secret;
	protected $response_type;
	protected $scope;
	protected $auth_endpoint;
	protected $token_endpoint;
	protected $name;
	protected $api_login_uri;
	protected $api_validate_uri;

	public function __construct ($app) {
		parent::__construct($app);
		
		$this->name = get_class($this);
		$this->name = str_replace("Xodx_", "", $this->name);
		$this->name = str_replace("Controller", "", $this->name);
		$this->name = strtolower($this->name);
	}

	public function redirectAction($template)
	{
		header('Location: '.$this->auth_endpoint.'?client_id='.$this->client_id.'&scope='.$this->scope.'&response_type='.$this->response_type.'&redirect_uri='.$this->redirect_uri);
	}

	protected function getAccessToken()
	{
		$client = new Zend_Http_Client($this->token_endpoint);
		$client->setParameterPost(array(
    		'client_id' => $this->client_id,
        	'client_secret' => $this->client_secret,
			'grant_type' => 'authorization_code',
			'code' => $_GET['code'],
			'redirect_uri'=> $this->redirect_uri
		));
		$response = $client->request('POST');
		$this->readTokenResponseData($response);
	}

	protected function readTokenResponseData($response)
	{
		$array = json_decode($response->getBody(), true);
		if(isset($array['error'])) {
			throw new Exception($array['error_description']);		
		} elseif($response->getMessage() == 'Not Found') {
			throw new Exception('The token endpoint '.$this->token_endpoint.' could not be found.');
		} else {
			$name = strtoupper($this->name).'_ACCESS_TOKEN';
			$this->addToken($name, $array['access_token']);
		}
	}

	public function deleteTokens()
	{
		$name = $_SESSION['TOKEN_NAME'];
		$_SESSION[$name] = null;
		$_SESSION['TOKEN_NAME'] = null;
	}

	public function addToken($name, $token)	
	{
		$_SESSION['TOKEN_NAME'] = $name;
		$_SESSION[$name] = $token;
	}

	public function validate($controller)
	{
		$bootstrap = $this->_app->getBootstrap();
       	$request = $bootstrap->getResource('request');
		if(is_a($controller, 'Xodx_ResourceController')) {
			if($request->getValue('TOKEN_NAME','session') !== null) {
					
				/*$oauth_controller_name = ucfirst(strtolower(str_replace('_ACCESS_TOKEN', '',$_SESSION['TOKEN_NAME'])));
				$oauth_controller_name = 'Xodx_'.$oauth_controller_name.'Controller';
				$oauth_controller = $this->_app->getController($oauth_controller_name);			

				$oauth_controller->validate();*/

				return;
			} else {
				throw new Exception('No valid token was found');
			}
		}		
		return;			
	}

	public function postRequest($uri, $array=null)
	{
		$client = new Zend_Http_Client($uri);
		if(isset($array)) {
			$client->setParameterPost(array_merge(
    			['access_token' => $_SESSION[strtoupper($this->name).'_ACCESS_TOKEN']],
				$array
			));
		} else {
			$client->setParameterPost(
    			['access_token' => $_SESSION[strtoupper($this->name).'_ACCESS_TOKEN']]
			);
		}
		$response = $client->request('POST');
		$result = json_decode($response->getBody(), true);	
		if(isset($array['error'])) {
			throw new Exception($array['error_description']);		
		} elseif($response->getMessage() == 'Not Found') {
			throw new Exception('The site '.$uri.' could not be found.');
		} else {
			return $result;
		}
	}

	public function getRequest($uri, $array=null)
	{
		$client = new Zend_Http_Client($uri);
		if(isset($array)) {
			$client->setParameterGet(array_merge(
    			['access_token' => $_SESSION[strtoupper($this->name).'_ACCESS_TOKEN']],
				$array
			));
		} else {
			$client->setParameterGet(
    			['access_token' => $_SESSION[strtoupper($this->name).'_ACCESS_TOKEN']]
			);
		}
		$response = $client->request('GET');
		$result = json_decode($response->getBody(), true);	
		if(isset($array['error'])) {
			throw new Exception($array['error_description']);		
		} elseif($response->getMessage() == 'Not Found') {
			throw new Exception('The site '.$uri.' could not be found.');
		} else {
			return $result;
		}
	}

	public function indexAction($template)
	{
		if(isset($_GET['code'])) {
			$location = new Saft_Url($this->_app->getBaseUri());
        	$location->setParameter('c', $this->name);
        	$location->setParameter('a', 'token');
			$location->setParameter('code', $_GET['code']);

        	$template->redirect($location);
		
			return $template;
		} elseif(isset($_GET['error'])) {
			throw new Exception($_GET['error_description']);
		}
			
	}

	public function tokenAction($template)
	{	
		$this->getAccessToken();

		$location = new Saft_Url($this->_app->getBaseUri());
        $location->setParameter('c', $this->name);
        $location->setParameter('a', 'login');
        $template->redirect($location);
		
		return $template;
	}

	protected function getLoginData()
	{
		$uri = $this->api_login_uri;
		return $this->postRequest($uri);
	}

	public function loginAction($template)
	{

		if(!isset($_SESSION[strtoupper($this->name).'_ACCESS_TOKEN'])) {
			$template->addContent('templates/login.phtml');
			return $template;
		}
		$array = $this->getLoginData();		

		if(!empty($array['user_name'])) {
			$username = $array['user_name'];
			$userUri = $this->_app->getBaseUri() . '?c=user&id=' . urlencode($username);
			$userController = $this->_app->getController('Xodx_UserController');

			if ($userController->getUser($userUri)->getName() === 'unkown') {

				$location = new Saft_Url($this->_app->getBaseUri());
        		$location->setParameter('c', $this->name);
        		$location->setParameter('a', 'newuser');

        		$template->redirect($location);
	
				return $template;
			} else {

				$_SESSION['username'] = $username;
        		$_SESSION['logedin'] = true;

				$location = new Saft_Url($this->_app->getBaseUri());
        		$location->setParameter('c', 'user');
        		$location->setParameter('a', 'home');

        		$template->redirect($location);
	
				return $template;
			}
		}
	}

	public function newuserAction ($template) {
		
        $nsPingback = 'http://purl.org/net/pingback/';
        $nsFoaf = 'http://xmlns.com/foaf/0.1/';
        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsOw = 'http://ns.ontowiki.net/SysOnt/';
        $nsDssn = 'http://purl.org/net/dssn/';

        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');
        $logger = $bootstrap->getResource('logger');

		$array = $this->getLoginData();	

        // get URI
        $personUri = $array['foaf_uri'];
        $username = $array['user_name'];

        // create new person
        $newPersonUri = $this->_app->getBaseUri() . '?c=person&id=' . urlencode($username);
        $newUserUri = $this->_app->getBaseUri() . '?c=user&id=' . urlencode($username);
        $newPersonFeed = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri='
            . urlencode($newPersonUri);

        $newPerson = array(
        	$newPersonUri => array(
            	EF_RDF_TYPE => array(
                	array('type' => 'uri', 'value' => $nsFoaf . 'Person')
                ),
                $nsFoaf . 'account' => array(
                    array('type' => 'uri', 'value' => $newUserUri)
                ),
                $nsFoaf . 'nick' => array(
                    array('type' => 'literal', 'value' => $username)
                ),
                $nsDssn . 'activityFeed' => array(
                    array('type' => 'uri', 'value' => $newPersonFeed)
                )
            )
       );

      //add some statements from a former existing foaf:Person which was specified
	  if (!empty($personUri)) {
      		$linkeddataHelper = $this->_app->getHelper('Saft_Helper_LinkeddataHelper');
                    $newStatements = $linkeddataHelper->getResource($personUri);
					
                    $logger->debug(var_export($newStatements, true));
                    if ($newStatements === null) {
                        $formError['personUri'] = true;
                        $template->formError = $formError;
                        $template->addContent('templates/newuser.phtml');
                        return $template;
                    }
                    $memModel = new Erfurt_Rdf_MemoryModel($newStatements);
                    $types = $memModel->getValues($personUri, EF_RDF_TYPE);

                    // extract the primaryTopic if the resource is a personalProfileDocument
                    foreach ($types as $type) {
                        if ($type['type'] == 'uri') {
                            $type = $type['value'];
                        } else {
                            continue;
                        }
                        if ($type == $nsFoaf . 'Person') {
                            break;
                        } else if ($type == $nsFoaf . 'PersonalProfileDocument') {
                            $personUri = $memModel->getValue($personUri, $nsFoaf . 'primaryTopic');
                            $newStatements = $linkeddataHelper->getResource($personUri);
                            $logger->debug(var_export($newStatements, true));
                            $memModel = new Erfurt_Rdf_MemoryModel($newStatements);
                            break;
                        }
                    }

                    // add seeAlso relation if a former uri was specified
                    $newPerson[$newPersonUri][EF_RDFS_NS . 'seeAlso'] = array(
                        array('type' => 'uri', 'value' => $personUri
                        )
                    );

                    // add all statements about the former person to the new one
                    if ($memModel !== null) {
                        $memStmt = $memModel->getPO($personUri);
                        foreach ($memStmt as $p => $o) {
                            if (isset($newPerson[$newPersonUri][$p])) {
                                $newPerson[$newPersonUri][$p] = array_merge(
                                    $o, $newPerson[$newPersonUri][$p]
                                );
                            } else {
                                $newPerson[$newPersonUri][$p] = $o;
                            }
                        }
                    }
                }
                

       $model->addMultipleStatements($newPerson);

       $newUser = array(
       		$newUserUri => array(
            	EF_RDF_TYPE => array(
                	array('type' => 'uri', 'value' => $nsSioc . 'UserAccount')
                ),
            	$nsFoaf . 'accountName' => array(
                    array('type' => 'literal', 'value' => $username)
            	),
            	$nsSioc . 'account_of' => array(
                    array('type' => 'uri', 'value' => $newPersonUri)
                )
            )         
       ); 
        
       $model->addMultipleStatements($newUser);

       $location = new Saft_Url($this->_app->getBaseUri());
       $location->setParameter('c', $this->name);
       $location->setParameter('a', 'login');
	   $template->redirect($location);

       return $template;
    }
}
