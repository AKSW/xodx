<?php

class Xodx_PersonController extends Xodx_Controller
{
    private $_pseronss = array();

    public function getPerson ($personUri)
    {
        if (!isset($this->_persons[$psersonUri])) {

            $person = new Xodx_Person($personUri);

            $this->_persons[$personUri] = $person;
        }

        return $this->_persons[$personUri];
    }

    public function addFriend ($personUri, $contactUri)
    {
        $model = $this->_app->getBootstrap()->getResource('model');

        $model->addStatement($personUri, 'http://xmlns.com/foaf/0.1/knows', array('type' => 'uri', 'value' => $contactUri));

        $nsAair = 'http://xmlns.notu.be/aair#';
        $activityController = new Xodx_ActivityController($this->_app);

        // add Activity to activity Stream
        $object = array(
            'type' => 'uri',
            'value' => $contactUri
        );
        $activityController->addActivity($personUri, $nsAair . 'MakeFriend', $object);

        // ping the new contact
        $pingbackController = new Xodx_PingbackController($this->_app);
        $pingbackController->sendPing($personUri, $contactUri);

        // TODO subscribe to contacts activity stream
    }

    public function addFriendRequest ($personUri, $contactUri)
    {
        $model = $this->_app->getBootstrap()->getResource('model');

        $model->addStatement($personUri, 'http://ns.xodx.org/friendRequest', array('type' => 'uri', 'value' => $contactUri));

        // TODO trigger notification
    }

    /**
     * Returns the feed of the specified $type of the person
     */
    public function getFeed ($personUri, $type = 'activity')
    {
        $model = $this->_app->getBootstrap()->getResource('model');

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
            '   <' . $personUri . '> <' . $feedProp . '> ?feed . ' .
            '}'
        );

        return $feedResult[0]['feed'];
    }

}
