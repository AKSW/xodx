<?php

/**
 * This class manages instances of Xodx_User
 */
class Xodx_UserController extends Xodx_Controller
{
    private $_users = array();

    /**
     * This method creates a new object of the class Xodx_User
     * @param $userUri a string which contains the URI of the required user
     * @return Xodx_User instance with the specified URI
     */
    public function getUser ($userUri)
    {
        if (!isset($this->_users[$userUri])) {

            $user = new Xodx_User($userUri);

            $this->_users[$userUri] = $user;
        }

        return $this->_users[$userUri];
    }

    /**
     * This function verifies the given credentials for a user
     * @param $user a string with the URI or Xodx_User object representing the user
     * @param $password a string containing the password of the given user
     */
    public function verifyPasswordCredentials ($user, $password)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $userUri = null;
        $passwordHash = md5($password);

        if (!is_string($user) && get_class($user) === 'Xodx_User') {
            $userUri = $user->getUri();
        } else {
            $userUri = $user;
        }

        $query = '' .
            'PREFIX xodx: <http://example.org/voc/xodx/> ' .
            'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' .
            'SELECT ?pwd ' .
            'WHERE { ' .
            '   <' . $userUri . '> a sioc:UserAccount . ' .
            '   <' . $userUri . '> sioc:hasPassword ?pwd . ' .
            '}';
        $passwordQueryResult = $model->sparqlQuery($query);

        if (
            !empty($passwordQueryResult[0]['pwd']) &&
            $passwordHash == $passwordQueryResult[0]['pwd']
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function subscribeAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        $userUri = $request->getValue('user', 'post');
        $feedUri = $request->getValue('feeduri', 'post');

        $this->subscribeToFeed($userUri, $feedUri);

        return $template;
    }

    public function subscribeToFeed ($userUri, $feedUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $logger = $bootstrap->getResource('logger');

        $logger->info('subscribeToFeed: user: ' . $userUri . ', feed: ' . $feedUri);

        if (!$this->_isSubscribed($userUri, $feedUri)) {
            $pushController = new Xodx_PushController($this->_app);
            if ($pushController->subscribe($feedUri)) {

                $store = $bootstrap->getResource('store');
                $model = $bootstrap->getResource('model');
                $graphUri = $model->getModelIri();

                $nsXodx = 'http://example.org/voc/xodx/';

                $subscribeStatement = array(
                    $userUri => array(
                        $nsXodx . 'subscribedTo' => array(
                            array(
                                'type' => 'uri',
                                'value' => $feedUri
                            )
                        )
                    )
                );

                $store->addMultipleStatements($graphUri, $subscribeStatement);
            }
        }
    }

    private function _isSubscribed ($userUri, $feedUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $query = '' .
            'PREFIX xodx: <http://example.org/voc/xodx/> ' .
            'ASK { ' .
            '   <' . $userUri . '> xodx:subscribedTo <' . $feedUri . '> . ' .
            '}';
        $subscribedResult = $model->sparqlQuery($query);

        return empty($subscribedResult[0]['__ask_retval']);
    }
}
