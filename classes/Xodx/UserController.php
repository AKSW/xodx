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
        $store = $bootstrap->getResource('store');
        $model = $bootstrap->getResource('model');

        $query = '' .
            'PREFIX xodx: <http://example.org/voc/xodx/> ' .
            'ASK { ' .
            '   <' . $userUri . '> xodx:subscribedTo <' . $feedUri . '> . ' .
            '}';
        $subscribedResult = $model->sparqlQuery($query);

        if (empty($subscribedResult[0]['__ask_retval'])) {
            $pushController = new Xodx_PushController($this->_app);
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
