<?php

/**
 * This Class represents a foaf:Person or any other class like that
 */
class Xodx_Person
{
    private $_uri;
    private $_name;
    private $_contacts;

    public static function getPerson ($uri)
    {
        // TODO: get Person from Model
        $person = new Xodx_Person();
        $person->_uri = $uri;
        return $person;
    }

    public function addFriend($contactUri)
    {
        $app = Application::getInstance();
        $model = $app->getBootstrap()->getResource('Model');

        $model->addStatement($this->_uri, 'http://xmlns.com/foaf/0.1/knows', array('type' => 'uri', 'value' => $contactUri));

        // TODO ping the new contact
        $pingbackController = new Xodx_PingbackController();
        $pingbackController->sendPing($this->_uri, $contactUri);
    }

    public function addFriendRequest($contactUri)
    {
        $app = Application::getInstance();
        $model = $app->getBootstrap()->getResource('Model');

        $model->addStatement($this->_uri, 'http://ns.xodx.org/friendRequest', array('type' => 'uri', 'value' => $contactUri));

        // TODO send notification mail
    }

}
