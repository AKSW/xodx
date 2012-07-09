<?php

class Xodx_PersonController extends Xodx_Controller
{
    private $_persons = array();

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

    /**
     * This method returns all activities of a person
     * @param $personUri the uri of the person whoes activities should be returned
     * @return an array of activities
     * TODO return an array of Xodx_Activity objects
     */
    public function getActivities ($personUri)
    {
        $nsAair = 'http://activitystrea.ms/schema/1.0/';

        $model = $this->_app->getBootstrap()->getResource('model');

        if ($personUri === null) {
            return null;
        }

        $query = '' .
            'PREFIX atom: <http://www.w3.org/2005/Atom/> ' .
            'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
            'SELECT ?activity ?date ?verb ?object ' .
            'WHERE { ' .
            '   ?activity a                   aair:Activity ; ' .
            '             aair:activityActor  <' . $personUri . '> ; ' .
            '             atom:published      ?date ; ' .
            '             aair:activityVerb   ?verb ; ' .
            '             aair:activityObject ?object . ' .
            '}';
        $activitiesResult = $model->sparqlQuery($query);

        $activities = array();

        foreach ($activitiesResult as $activity) {
            $activityUri = $activity['activity'];
            $verbUri = $activity['verb'];
            $objectUri = $activity['object'];

            $activity['date'] = self::_issueE24fix($activity['date']);

            $activity = array(
                'title' => '"' . $personUri . '" did "' . $activity['verb'] . '".',
                'uri' => $activityUri,
                'author' => 'Natanael',
                'authorUri' => $personUri,
                'pubDate' => $activity['date'],
                'verb' => $activity['verb'],
                'object' => $activity['object'],
            );

            if ($verbUri == $nsAair . 'Post') {
                //echo 'betrete' . "\n";
                $objectResult = $model->sparqlQuery(
                    'PREFIX atom: <http://www.w3.org/2005/Atom/> ' .
                    'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
                    'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' .
                    'SELECT ?type ?content ?date ' .
                    'WHERE { ' .
                    '   <' . $objectUri . '> a ?type ; ' .
                    '        sioc:created_at ?date ; ' .
                    '        aair:content ?content . ' .
                    '} '
                );

                if (count($objectResult) > 0) {
                    $activity['objectType'] = $objectResult[0]['type'];
                    $activity['objectPubDate'] = self::_issueE24fix($objectResult[0]['date']);
                    $activity['objectContent'] = $objectResult[0]['content'];
                }
            } else {
            }

            $activities[] = $activity;
        }

        return $activities;
    }

    /**
     * Get an array of new notifications for the person
     */
    public function getNotifications ($personUri)
    {
        $model = $this->_app->getBootstrap()->getResource('model');

        $pingResult = $model->sparqlQuery(
            'PREFIX pingback: <http://purl.org/net/pingback/> ' .
            'SELECT ?ping ?source ?target ?comment ' .
            'WHERE { ' .
            '   <' . $personUri . '> pingback:ping ?ping . ' .
            '   ?ping a                pingback:Item ; ' .
            '         pingback:source  ?source ; ' .
            '         pingback:target  ?target ; ' .
            '         pingback:comment ?comment . ' .
            '} '
        );

        return $pingResult;
    }

    /**
     * Quick fix for Erfurt issue #24 (https://github.com/AKSW/Erfurt/issues/24)
     */
    private static function _issueE24fix ($date)
    {
        if (strstr($date, 11, 1) != 'T') {
            $dateObj = date_create($date);
            return date_format($dateObj, 'c');
        } else {
            return $date;
        }
    }
}
