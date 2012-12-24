<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * This class implements a pubsubhubbub publisher and subscriber
 */
class Xodx_PushController extends Saft_Controller
{

    private $_callbackUrl;
    private $_defaultHubUrl;

    public function __construct ($app)
    {
        parent::__construct($app);

        $bootstrap = $this->_app->getBootstrap();
        $config = $bootstrap->getResource('config');

        $this->_callbackUrl = $this->_app->getBaseUri() . '?c=push&a=callback';
        $this->_defaultHubUrl = $config['push.hub'];
    }

    /**
     * This is the subscribe method, which is called internally if some component wants to
     * be notified on updates of a feed
     * This method implements section 6.1 of the pubsubhubbub spec:
     *  http://pubsubhubbub.googlecode.com/svn/trunk/pubsubhubbub-core-0.3.html#anchor5
     */
    public function subscribe ($feedUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $logger = $bootstrap->getResource('logger');
        $store = $bootstrap->getResource('store');
        $model = $bootstrap->getResource('model');
        $graphUri = $model->getModelIri();

        // TODO implement events
        // TODO check if we are already subscribed to this feed

        if (!$this->_isSubscribed($feedUri)) {

            // else fetch feed, get hub url, subscribe to the hub
            $curlHandler = curl_init();

            //set the url
            curl_setopt($curlHandler, CURLOPT_URL, $feedUri);
            curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($curlHandler);
            $httpCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);
            $topicUri = curl_getinfo($curlHandler, CURLINFO_EFFECTIVE_URL);

            $logger->info('push subscribe: return code from feed: ' . $httpCode);

            curl_close($curlHandler);

            if ($httpCode-($httpCode%100) == 200) {
                $xml = simplexml_load_string($result);

                $hubUrl = null;

                if (count($xml) < 1) {
                    throw new Exception('Feed is empty');
                } else {
                    foreach ($xml->link as $link) {
                        $attributes = $link->attributes();
                        if ($attributes['rel'] == 'hub') {
                            $hubUrl = (string) $attributes['href'];
                            $debugArray[] = 'hub found at: ' . $hubUrl;
                            // TODO: maybe we could use multiple hubs if more than one is specified
                            break;
                        }
                    }
                }

                // TODO: read the rest of the feed and store the actions

                $logger->info('push subscribe: hub: ' . $hubUrl . ', callbackUrl: ' . $this->_callbackUrl);

                if ($hubUrl !== null) {
                    // subscribe to hub
                    $postData = array(
                        'hub.callback' => urlencode($this->_callbackUrl),
                        'hub.mode' => 'subscribe',
                        'hub.topic' => urlencode($topicUri),
                        'hub.verify' => 'async',
                        'hub.lease_seconds' => '',
                        'hub.verify_token' => '',
                    );

                    $postString = '';

                    foreach ($postData as $key => $value) {
                        $postString .= $key . '=' . $value . '&';
                    }
                    rtrim($postString, '&');

                    $curlHandler = curl_init();

                    //set the url
                    curl_setopt($curlHandler, CURLOPT_URL, $hubUrl);
                    curl_setopt($curlHandler, CURLOPT_POST, true);
                    curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $postString);
                    curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);

                    $result = curl_exec($curlHandler);
                    $httpCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);

                    curl_close($curlHandler);

                    $logger->info('push subscribe: return code from hub: ' . $httpCode . ', result: ' . $result);

                    if (($httpCode - ($httpCode % 100)) != 200) {
                        throw new Exception('Subscription to hub failed');
                    }

                    $nsXodx = 'http://example.org/voc/xodx/';

                    $hubObj = array(
                        'type' => 'uri',
                        'value' => $hubUrl
                    );

                    $store->addStatement($graphUri, $feedUri, $nsXodx . 'subscribedAt', $hubObj);
                } else {
                    throw new Exception('No hub found in feed');
                }
            } else {
                $logger->info('push subscribe: subscription error: ' . $result);
                throw new Exception('Error when requesting feed');
            }
        }

        $logger->info('push subscribe: subscription successfull');

        return true;
    }

    /**
     * This ist the publish method, which is called internally if a feed has been changed
     * This method implements section 7.1 of the pubsubhubbub spec:
     *  http://pubsubhubbub.googlecode.com/svn/trunk/pubsubhubbub-core-0.3.html#anchor9
     */
    public function publish ($topicUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $logger = $bootstrap->getResource('logger');

        $postData = array(
            'hub.mode' => 'publish',
            'hub.url' => urlencode($topicUri)
        );

        $postString = '';
        foreach ($postData as $key => $value) {
            $postString .= $key . '=' . $value . '&';
        }
        rtrim($postString, '&');

        $curlHandler = curl_init();

        //set the url
        curl_setopt($curlHandler, CURLOPT_URL, $this->_defaultHubUrl);
        curl_setopt($curlHandler, CURLOPT_POST, true);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curlHandler);
        $httpCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);

        curl_close($curlHandler);

        $logger->info('push publish: hub: ' . $this->_defaultHubUrl . ', topic: ' . $topicUri . ', return code: ' . $httpCode . ', result: ' . $result);

        if ($httpCode - ($httpCode % 100) != 200) {
            throw new Exception('Publishing to hub failed');
        }
    }

    /**
     * This action is used as callback for the subscriber and it will be triggered if the hub
     * notifies us about updates
     * The hub will call this action and give us the updates for the feed
     * This method implements section 6.2 of the pubsubhubbub spec:
     *  http://pubsubhubbub.googlecode.com/svn/trunk/pubsubhubbub-core-0.3.html#verifysub
     */
    public function callbackAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');
        $logger = $bootstrap->getResource('logger');

        $method = $request->getMethod();
        $logger->info('push callback: received request with method: ' . $method);

        if ($method == 'get') {
            // This is a subscription verification
            $logger->info('push callback: received get request, subscription verification');

            $mode = $request->getValue('hub.mode', 'get');
            $topic = $request->getValue('hub.topic', 'get');
            $challenge = $request->getValue('hub.challenge', 'get');
            $leaseSeconds = $request->getValue('hub.lease_seconds', 'get');
            $verifyToken = $request->getValue('hub.verify_token', 'get');

            $logger->info('push callback: mode: ' . $mode . ', topic: ' . $topic . ', challenge: ' . $challenge);

            // disable the layout
            $template->disableLayout();

            // return challenge
            $template->setRawContent($challenge);

            // TODO: make sure the return code is set correctly
        } else if ($method == 'post') {
            // This is a content distribution
            $logger->info('push callback: received post request, content distribution');

            // TODO get content type
            $body = $request->getBody();
            $logger->info('push callback: body: ' . $body);
            $feedController = $this->_app->getController('Xodx_FeedController');
            $feedController->feedToActivity($body);
        }

        return $template;
    }

    public function getDefaultHubUrl ()
    {
        return $this->_defaultHubUrl;
    }

    private function _isSubscribed ($feed)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        // 'PREFIX dssn: <http://purl.org/net/dssn/> ' .
        $query = '' .
            'PREFIX xodx: <http://example.org/voc/xodx/> ' .
            'SELECT ?hub ' .
            'WHERE { ' .
            '   <' . $feed . '> xodx:subscribedAt ?hub . ' .
            '}';
        $subscriptionResult = $model->sparqlQuery($query);

        return (count($subscriptionResult) > 0);
    }

    /**
     * This function gets a Request Body and tries to find a Feed URL
     *
     * @param Body of a Request
     * @return An URL of a Feed
     */
    private function _getFeedUriFromBody ($body) {
        //TODO If getBody() will work, check $body and get feedURI out of it
        //return $feedURI;
        return false;
    }
}
