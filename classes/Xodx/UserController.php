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

        $logger->info('subscribeToFeed: user: ' . $userUri . ', feed: ' . $feedUri);
        $type = $resourceController->getType($userUri);

        $userUri = $this->_getVerifiedUserAccount($userUri);

        if (!$this->_isSubscribed($userUri, $feedUri)) {
            $pushController = $this->_app->getController('Xodx_PushController');
            if ($pushController->subscribe($feedUri)) {

                $store    = $bootstrap->getResource('store');
                $model    = $bootstrap->getResource('model');
                $graphUri = $model->getModelIri();

                $nsSioc = 'http://rdfs.org/sioc/ns#';

                $subscribeStatement = array(
                    $userUri => array(
                        $nsSioc . 'subscriber_of' => array(
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

    /**
     * Get notifications for a user
     * @param $userUri the uri of the user whose notifications you want to get
     * @return an Array of Xodx_Notification objects
     */
    public function getNotifications ($userUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $query = 'SELECT ?uri' . PHP_EOL;
        $query.= 'WHERE {' . PHP_EOL;
        $query.= '  <' . $userUri . '> xodx:notification ?uri .' . PHP_EOL;
        $query.= '}' . PHP_EOL;

        $result = $model->sparqlQuery($query);

        $notificationFactory = new Xodx_NotificationFactory($this->app);
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
     * @param $userName a string with the username of the user
     * @param $password a string containing the password of the given user
     */
    public function verifyPasswordCredentials ($userName, $password)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        // TODO prevent sparql injection

        $query = '' .
            'PREFIX xodx: <http://example.org/voc/xodx/> ' .
            'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' .
            'PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' .
            'SELECT ?userUri ?passwordHash ' .
            'WHERE { ' .
            '   ?userUri a sioc:UserAccount ; ' .
            '       foaf:accountName "' . $userName . '" ; ' .
            '       xodx:hasPassword ?passwordHash . ' .
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

        $query = '' .
            'PREFIX sioc: <http://rdfs.org/sioc/ns#>' .
            'ASK { ' .
            '   <' . $userUri . '> sioc:subscriber_of <' . $feedUri . '> . ' .
            '}';
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
}
