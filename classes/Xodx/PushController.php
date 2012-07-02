<?php

/**
 * This class implements a pubsubhubbub publisher and subscriber
 */
class Xodx_PushController
{

    /**
     * This is the subscribe method, which is called internally if some component wants to
     * be notified on updates of a feed
     */
    public function subscribe ($feedUri)
    {
        // TODO implement events
        // TODO check if we are already subscribed to this feed
        // else fetch feed, get hub url, subscribe to the hub
    }

    /**
     * This ist the publish method, which is called internally if a feed has been changed
     */
    public function publish ($feedUri)
    {
        // TODO publish our changes to the hub
    }

    /**
     * This action is used as endpoint for the publisher
     */
    public function endpointAction ()
    {
        $this->app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();
        $request = $bootstrap->getResource('request');

        $request->getValue();
    }

    /**
     * This action is used as callback for the subscriber
     * The hub will call this action and give us the updates for the feed
     */
    public function callbackAction ()
    {
    }
}
