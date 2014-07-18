<?php

class Xodx_GoogleController extends Xodx_Oauth2Controller
{	
	public function __construct ($app) {
		parent::__construct($app);

		$this->oauth_uri = "https://accounts.google.com/o/oauth2";
 		$this->response_type= 'code';

		$this->client_id = "454577022654-apn9ft4ncsf8tv6q72vvtnnsjjvr3g79.apps.googleusercontent.com";
		$this->client_secret = "x2RecSwlMwSKA4iWyjpmuvih";
		$this->redirect_uri = $this->_app->getBaseUri().'?c=google';

		$this->scope = "openid profile";

		$this->auth_endpoint = $this->oauth_uri.'/auth';
	 	$this->token_endpoint = $this->oauth_uri.'/token';

		$this->api_login_uri = 'https://www.googleapis.com/plus/v1/people/me';
    }

	protected function getLoginData()
	{
		$uri = $this->api_login_uri;
		$arr = $this->getRequest($uri);
		$username = str_replace(" ", "", $arr['displayName']);
		$res = array('user_name' => $username, 'foaf_uri' => $arr['url'], 'statements' => $arr);
		return $res;
	}

	public function newuserAction ($template) 
	{
		
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
		$statements = $array['statements'];

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
      		
      $memModel = new Erfurt_Rdf_MemoryModel($statements);
      $types = $memModel->getValues($personUri, EF_RDF_TYPE);

      // add seeAlso relation if a former uri was specified
      $newPerson[$newPersonUri][EF_RDFS_NS . 'seeAlso'] = array(
      	array('type' => 'uri', 'value' => $personUri
             )
        );

   	 
      $newPerson[$newPersonUri][$nsFoaf . 'familyName'] = array(array('type' => 'literal', 'value' => $statements['name']['familyName']));
      $newPerson[$newPersonUri][$nsFoaf . 'givenName'] = array(array('type' => 'literal', 'value' => $statements['name']['givenName']));
	  $newPerson[$newPersonUri][$nsFoaf . 'name'] = array(array('type' => 'literal', 'value' => $statements['name']['givenName'].' '.$statements['name']['familyName']));

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
