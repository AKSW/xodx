<?php

class Xodx_UserController extends Xodx_Controller
{
    private $_users = array();

    public function getUser ($userUri)
    {
        if (!isset($this->_users[$userUri])) {

            $user = new Xodx_User($userUri);

            $this->_users[$userUri] = $user;
        }

        return $this->_users[$userUri];
    }

    public function subscribeAction ($template)
    {
        $this->app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();
        $request = $bootstrap->getResource('request');

        $subscribeResult =  $this->subscribe($request->getValue('feeduri', 'post'));
        $userUri =  $request->getValue('user', 'post');

        $this->subscribeToFeed($userUri, $feedUri);

        $template->addDebug(var_export($subscribeResult, true));

        return $template;
    }


    public function subscribeToFeed ($userUri, $feedUri)
    {
        $this->_app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();
        $store = $bootstrap->getResource('store');
        $model = $bootstrap->getResource('model');

        $query = '' .
            'PREFIX xodx: <http://example.org/voc/xodx/> ' .
            'ASK ' .
            'WHERE { ' .
            '   <' . $userUri . '> xodx:subscribedTo <' . $feedUri . '> . ' .
            '}';
        $subscribedResult = $model->sparqlQuery($query);

        if (!$subscribedResult) {
            $pushController = new Xodx_PushController();
            if ($pushController->subscribe($feedUri)) {
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
}
