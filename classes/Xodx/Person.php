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

    public function addFriend ($contactUri)
    {
        $app = Application::getInstance();
        $model = $app->getBootstrap()->getResource('Model');

        $model->addStatement($this->_uri, 'http://xmlns.com/foaf/0.1/knows', array('type' => 'uri', 'value' => $contactUri));

        $nsAair = 'http://xmlns.notu.be/aair#';
        $activityController = new Xodx_ActivityController();

        // add Activity to activity Stream
        $object = array(
            'type' => 'uri',
            'value' => $contactUri
        );
        $activityController->addActivity($this->_uri, $nsAair . 'MakeFriend', $object);

        // ping the new contact
        $pingbackController = new Xodx_PingbackController();
        $pingbackController->sendPing($this->_uri, $contactUri);
    }

    public function addFriendRequest ($contactUri)
    {
        $app = Application::getInstance();
        $model = $app->getBootstrap()->getResource('Model');

        $model->addStatement($this->_uri, 'http://ns.xodx.org/friendRequest', array('type' => 'uri', 'value' => $contactUri));

        // TODO send notification mail
    }

    /**
     * Returns the feed of the specified $type of the person
     */
    public function getFeed ($type = 'activity')
    {
        $app = Application::getInstance();
        $model = $app->getBootstrap()->getResource('Model');

        $nsDssn = 'http://purl.org/net/dssn/';

        $feedProp = '';
        if ($type == 'activity') {
            $feedProp = $nsDssn . 'activityFeed';
        } else if ($type == 'sync') {
            $feedProp = $nsDssn . 'syncFeed';
        }

        $feedResult = $model->sparqlQuery(
            'PREFIX atom: <http://www.w3.org/2005/Atom/> ' .
            'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
            'SELECT ?feed ' .
            'WHERE { ' .
            '   <' . $this->_uri . '> <' . $feedProp . '> ?feed . ' .
            '}'
        );

        return $feedResult[0]['feed'];
    }
}
