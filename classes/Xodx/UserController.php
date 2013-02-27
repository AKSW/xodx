<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

// include password hash functions for 5.3.7 <= PHP < 5.5
require_once('password_compat/lib/password.php');

/**
 * This class manages instances of Xodx_User.
 * this includes:
 *  - subscribing to a feed
 *  - getting notifications
 */
class Xodx_UserController extends Xodx_ResourceController
{
    /**
     * A registry of already loaded Xodx_User objects
     */
    private $_users = array();

    /**
     * With this action a user can subscribe to a specified feed
     * @param user (post) the uri of the user, who wants to subscribe
     * @param feeduri (post) the uri of the feed where he want so subscribe to
     */
    public function subscribeAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        $userUri = $request->getValue('user', 'post');
        $feedUri = $request->getValue('feeduri', 'post');

        $this->subscribeToFeed($userUri, $feedUri);

        return $template;
    }

    /**
     * This action gets the notifications for the specified user
     * @param user (get) the uri of the user who wants to get its notifications
     * @return json representation of the Xodx_Notification objects
     */
    public function getNotificationsAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        $userUri = $request->getValue('user', 'get');

        if ($userUri === null) {
            $userUri = $this->getUser()->getUri();
        }

        $notifications = $this->getNotifications($userUri);

        $template->disableLayout();

        $notificationsJson = json_encode($notifications);
        $template->setRawContent($notificationsJson);

        return $template;
    }

    /**
     *
     * Method returns the Uri of the sioc:UserAccount
     * @param unknown_type $personUri
     */
    private function _getVerifiedUserAccount ($personUri)
    {
        $nsFoaf = 'http://xmlns.com/foaf/0.1/';
        $nsSioc = 'http://rdfs.org/sioc/ns#';

        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');
        $resourceController = $this->_app->getController('Xodx_ResourceController');

        $type = $resourceController->getType($personUri);

        // TODO replace str_replace with SPARQL query for foaf:Account with NS === BaseUri()
        if ($type === $nsFoaf . 'Person') {
            $personUri = str_replace('?c=person&', '?c=user&', $personUri);
        }

        return $personUri;
    }

    /**
     * This method subscribes a user to a feed
     * @param $userUri the uri of the user who wants to be subscribed
     * @param $feedUri the uri of the feed where she wants to subscribe
     */
    public function subscribeToFeed ($userUri, $feedUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $logger = $bootstrap->getResource('logger');
        $resourceController = $this->_app->getController('Xodx_ResourceController');

        $feed = DSSN_Activity_Feed_Factory::newFromUrl($feedUri);

        $logger->info('subscribeToFeed: user: ' . $userUri . ', feed: ' . $feedUri);
        $type = $resourceController->getType($userUri);

        $userUri = $this->_getVerifiedUserAccount($userUri);

        if (!$this->_isSubscribed($userUri, $feedUri)) {
            $pushController = $this->_app->getController('Xodx_PushController');
            if ($pushController->subscribe($feedUri)) {

                $store    = $bootstrap->getResource('store');
                $model    = $bootstrap->getResource('model');
                $graphUri = $model->getModelIri();

                $nsDssn = 'http://purl.org/net/dssn/';
                $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';

                $subUri = $this->_app->getBaseUri() . '&c=ressource&id=' . md5(rand);
                $cbUri  = $this->_app->getBaseUri() . '?c=push&a=callback';

                $subsription = array(
                    $subUri => array(
                        $nsRdf . 'typ' => array(
                            array(
                                'type' => 'uri',
                                'value' => $nsDssn . 'Subscription'
                            )
                        ),
                        $nsDssn . 'subscriptionCallback' => array(
                            array(
                                'type' => 'uri',
                                'value' => $cbUri
                            )
                        ),
                        $nsDssn . 'subscriptionHub' => array(
                            array(
                                'type' => 'uri',
                                'value' => $feed->getLinkHub()
                            )
                        ),
                        $nsRdf . 'subscriptionTopic' => array(
                            array(
                                'type' => 'uri',
                                'value' => $feed->getLinkSelf()
                            )
                        ),
                    )
                );

                $store->addMultipleStatements($graphUri, $subscription);

                $subscribeStatement = array(
                    $userUri => array(
                        $nsDssn . 'subscribedTo' => array(
                            array(
                                'type' => 'uri',
                                'value' => $subUri
                            )
                        )
                    )
                );

                $store->addMultipleStatements($graphUri, $subscribeStatement);
            }
        }
    }

    /**
     * Get notifications for a user
     * @param $userUri the uri of the user whose notifications you want to get
     * @return an Array of Xodx_Notification objects
     */
    public function getNotifications ($userUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $query = 'PREFIX dssn: <http://purl.org/net/dssn/> ' . PHP_EOL;
        $query.= 'SELECT ?uri' . PHP_EOL;
        $query.= 'WHERE {' . PHP_EOL;
        $query.= '  ?uri dssn:notify <' . $userUri . '> .' . PHP_EOL;
        $query.= '}' . PHP_EOL;

        $result = $model->sparqlQuery($query);

        $notificationFactory = new Xodx_NotificationFactory($this->_app);
        $notifications = array();
        foreach ($result as $notification) {
            $notificationUri = $notification['uri'];
            $notifications[$notificationUri] = $notificationFactory->fromModel($notificationUri);
        }

        return $notifications;
    }

    /**
     * This method creates a new object of the class Xodx_User
     * @param $userUri a string which contains the URI of the required user
     * @return Xodx_User instance with the specified URI
     */
    public function getUser ($userUri = null)
    {
        if ($userUri === null) {
            $applicationController = $this->_app->getController('Xodx_ApplicationController');
            $userId = $applicationController->getUser();
            $userUri = $this->_app->getBaseUri() . '?c=user&id=' . $userId;
        }

        if (!isset($this->_users[$userUri])) {

            if (!isset($userId)) {
                $bootstrap = $this->_app->getBootstrap();
                $model = $bootstrap->getResource('model');

                $query = 'PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' . PHP_EOL;
                $query.= 'SELECT ?name' . PHP_EOL;
                $query.= 'WHERE {' . PHP_EOL;
                $query.= '  <' . $userUri . '> foaf:accountName ?name .' . PHP_EOL;
                $query.= '}' . PHP_EOL;

                $result = $model->sparqlQuery($query);
                if (count($result) > 0) {
                    $userId = $result[0]['name'];
                } else {
                    $userId = 'unkown';
                }
            }

            $user = new Xodx_User($userUri);
            $user->setName($userId);

            $this->_users[$userUri] = $user;
        }

        return $this->_users[$userUri];
    }

    /**
     * This function verifies the given credentials for a user
     * @param $userName a string with the username of the user
     * @param $password a string containing the password of the given user
     */
    public function verifyPasswordCredentials ($userName, $password)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        // TODO prevent sparql injection

        $query = '' .
            'PREFIX ow: <http://ns.ontowiki.net/SysOnt/> ' .
            'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' .
            'PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' .
            'SELECT ?userUri ?passwordHash ' .
            'WHERE { ' .
            '   ?userUri a sioc:UserAccount ; ' .
            '       foaf:accountName "' . $userName . '" ; ' .
            '       ow:userPassword ?passwordHash . ' .
            '}';
        $passwordQueryResult = $model->sparqlQuery($query);

        if (count($passwordQueryResult) > 0) {
            $passwordHash = $passwordQueryResult[0]['passwordHash'];
            return password_verify($password, $passwordHash);
        } else {
            return false;
        }
    }

    /**
     * Check if a user is already subscribed to a feed
     * @param $userUri the uri of the user in question
     * @param $feedUri the uri of the feed in question
     */
    private function _isSubscribed ($userUri, $feedUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $query = 'PREFIX dssn: <http://purl.org/net/dssn/> ' . PHP_EOL;
        $query.= 'ASK  ' . PHP_EOL;
        $query.= 'WHERE { ' . PHP_EOL;
        $query.= '   <' . $userUri . '> dssn:subscribedTo      ?subUri. ' . PHP_EOL;
        $query.= '        ?subUri       dssn:subscriptionTopic <' . $feedUri. '> . ' . PHP_EOL;
        $query.= '}' . PHP_EOL;
        $subscribedResult = $model->sparqlQuery($query);

        if (is_array($subscribedResult)) {
            // Erfurt problem
            return empty($subscribedResult[0]['__ask_retval']);
        } else if (is_bool($subscribedResult)) {
            return $subscriptionResult;
        } else {
            $logger = $bootstrap->getResource('logger');
            $logger->info('isSubscribed: user: ' . $userUri . ', feed: ' . $feedUri . '. ASK Query returned unexpectedly: ' . var_export($subscriptionResult));

            throw new Exception('Erfurt returned an unexpected result to the ask query.');
        }
    }


        /**
     * Find all subscriptions of a user
     * @param $userUri the uri of the user in question
     * @return array $subscribedFeeds all feeds a user is subscribed to
     */
    public function getSubscriptions ($userUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        // SPARQL-Query
        $query = 'PREFIX dssn: <http://purl.org/net/dssn/> ' . PHP_EOL;
        $query.= 'SELECT  ?feedUri' . PHP_EOL;
        $query.= 'WHERE {' . PHP_EOL;
        $query.= '   <' . $userUri . '> dssn:subscribedTo        ?subUri. ' . PHP_EOL;
        $query.= '   ?subUri            dssn:subscriptionTopic   ?feedUri. ' . PHP_EOL;
        $query.= '}' . PHP_EOL;

        $feedResult = $model->sparqlQuery($query);

        $subscribedFeeds = array();

        // results in array
        foreach ($feedResult as $feed) {
            if (isset($feed['feedUri'])) {
                $subscribedFeeds[] = $act['activity'];
            }
        }

        return $subscribedFeeds;
    }

    /**
     * Get the Uri of a user account of a person
     * @param string $personUri the uri of the person
     * @return string $userUri uri of the found user account
     */
    public function getUserUri ($personUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        // SPARQL-Query
        $query = 'PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' . PHP_EOL;
        $query.= 'SELECT  ?userUri ' . PHP_EOL;
        $query.= 'WHERE {' . PHP_EOL;
        $query.= '   <' . $personUri . '> foaf:account ?userUri. ' . PHP_EOL;
        $query.= '}' . PHP_EOL;

        $userResult = $model->sparqlQuery($query);

        if (count($userResult[0])>0) {
            return $userResult[0]['userUri'];
        }
    }

    /**
     *
     */
    public function testSubscribeAction ($template){
        $user = $this->_app->getBaseDir() . '?c=user&id=splatte';
        //$feed = 'http://www.lvz-online.de/rss/nachrichten-rss.xml';
        $feed = 'http://xodx.local/?c=feed&a=getFeed&uri=http%3A%2F%2Fxodx.local%2F%3Fc%3Dresource%26id%3Dcfdff42e6f6d1efb9b5bed96854cba01';
        $this->subscribeToFeed($user,$feed);
        return $template;
    }

}
