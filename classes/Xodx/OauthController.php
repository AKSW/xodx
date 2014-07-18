<?php

class Xodx_OauthController extends Xodx_Oauth2Controller
{	
	public function __construct ($app) {
		parent::__construct($app);

		$this->oauth_uri = "http://symbolicdata.org/Login-Authorization/api";
		$this->client_id = "Xodx";
		$this->client_secret = "password";

		$this->redirect_uri = $this->_app->getBaseUri().'?c=oauth';
		$this->scope = "xodx";

		$this->auth_endpoint = $this->oauth_uri.'/request-token.php';
	 	$this->token_endpoint = $this->oauth_uri.'/access-token.php';

		$this->api_login_uri = $this->oauth_uri.'/get-login-data.php';
		$this->api_validate_uri = $this->oauth_uri.'/validate-token.php';
    }

	public function validate($controller)
	{
		$arr = ['scope' => $this->scope];
		$uri = $this->api_validate_uri;
		
		$array = $this->postRequest($uri, $arr);
		return $array['success'];
	}
}
