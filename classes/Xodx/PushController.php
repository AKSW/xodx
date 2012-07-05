<?php

/**
 * This class implements a pubsubhubbub publisher and subscriber
 */
class Xodx_PushController extends Xodx_Controller
{

    private $_callbackUrl;
    private $_defaultHubUrl;

    public function __construct ()
    {
        $this->app = Application::getInstance();
        $this->_callbackUrl = $this->app->getBaseUri() . '?c=push&amp;a=callback';
        $this->_defaultHubUrl = 'http://pubsubhubbub.appspot.com';
    }

    public function subscribeAction ($template)
    {
        $this->app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();
        $request = $bootstrap->getResource('request');

        $subscribeResult =  $this->subscribe($request->getValue('feeduri', 'post'));

        $template->addDebug(var_export($subscribeResult, true));

        return $template;
    }

    /**
     * This is the subscribe method, which is called internally if some component wants to
     * be notified on updates of a feed
     */
    public function subscribe ($feedUri)
    {
        $debugArray = array();

        // TODO implement events
        // TODO check if we are already subscribed to this feed
        // else fetch feed, get hub url, subscribe to the hub
        $curlHandler = curl_init();

        //set the url
        curl_setopt($curlHandler, CURLOPT_URL, $feedUri);
        curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curlHandler);
        $httpCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);
        $topicUri = curl_getinfo($curlHandler, CURLINFO_EFFECTIVE_URL);

        curl_close($curlHandler);

        if ($httpCode-($httpCode%100) == 200) {
            $xml = simplexml_load_string($result);

            $hubUrl = null;

            if (count($xml) < 1) {
                // TODO: throw Exception
                throw new Exception('Feed is empty');
            } else {
                foreach ($xml->link as $link) {
                    $attributes = $link->attributes();
                    if ($attributes['rel'] == 'hub') {
                        $hubUrl = $attributes['href'];
                        $debugArray[] = 'hub found at: ' . $hubUrl;
                        // TODO: maybe we could use multiple hubs if more than one is specified
                        break;
                    }
                }
            }

            // TODO: read the rest of the feed and store the actions

            if ($hubUrl !== null) {
                // subscribe to hub
                $postData = array(
                    'hub.mode' => 'subscribe',
                    'hub.callback' => $this->_callbackUrl,
                    'hub.verify' => 'async',
                    'hub.verify_token' => '',
                    'hub.lease_seconds' => '',
                    'hub.topic' => urlencode($topicUri)
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

                if ($httpCode-($httpCode%100) != 200) {
                    throw new Exception('Subscription to hub failed');
                }
            } else {
                throw new Exception('No hub found in feed');
            }
        } else {
            throw new Exception('Error when requesting feed');
        }

        $debugArray[] = 'success';

        return $debugArray;
    }

    /**
     * This ist the publish method, which is called internally if a feed has been changed
     */
    public function publish ($topicUri)
    {
        // TODO publish our changes to the hub
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

        if ($httpCode-($httpCode%100) != 200) {
            throw new Exception('Publishing to hub failed');
        }
    }

    /**
     * This action is used as endpoint for the publisher
     * @obsolete
     */
    public function endpointAction ($template)
    {
        $this->app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();
        $request = $bootstrap->getResource('request');

        $request->getValue();

        return $template;
    }

    /**
     * This action is used as callback for the subscriber and it will be triggered if the hub
     * notifies us about updates
     * The hub will call this action and give us the updates for the feed
     */
    public function callbackAction ($template)
    {
        $this->app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();
        $request = $bootstrap->getResource('request');
        $logger = $bootstrap->getResource('logger');

        $subscriptionKey = $request->getValue('xhub_subscription');

        $logger->info('SubscriptionKey: ' . $subscriptionKey);

        // TODO: disable the layout
        $template->disableLayout();

        $result = $request->getResource('hub.challenge', 'post');

        $template->setRawContent($result);

        // TODO: read this response and process it
    }

    public function getDefaultHubUrl ()
    {
        return $this->_defaultHubUrl;
    }
}
