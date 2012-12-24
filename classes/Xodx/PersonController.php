<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_PersonController extends Xodx_ResourceController
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
        $activityController = $this->_app->getController('Xodx_ActivityController');

        // add Activity to activity Stream
        $object = array(
            'type' => 'uri',
            'value' => $contactUri
        );
        $activityController->addActivity($personUri, $nsAair . 'MakeFriend', $object);

        // ping the new contact
        $pingbackController = $this->_app->getController('Xodx_PingbackController');
        $pingbackController->sendPing($personUri, $contactUri);

        // Subscribe user to feed of activityObject (photo, post, note)
        $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' . urlencode($objectUri);
        $personUri = urlencode($personUri);
        $userController = $this->_app->getController('Xodx_UserController');
        $userController->subscribeToFeed($personUri, $feedUri);
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
        // There are two namespaces, one is used in atom files the other one for RDF
        $nsAairAtom = 'http://activitystrea.ms/schema/1.0/';
        $nsAair = 'http://xmlns.notu.be/aair#';

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
            '} ' .
            'ORDER BY DESC(?date)';
        $activitiesResult = $model->sparqlQuery($query);

        $activities = array();

        foreach ($activitiesResult as $activity) {
            $activityUri = $activity['activity'];
            $verbUri = $activity['verb'];
            $objectUri = $activity['object'];

            $activity['date'] = self::_issueE24fix($activity['date']);

            $nameHelper = new Xodx_NameHelper($this->_app);
            $personName = $nameHelper->getName($personUri);

            $activity = array(
                'title' => '"' . $personName . '" did "' . $activity['verb'] . '".',
                'uri' => $activityUri,
                'author' => 'Natanael',
                'authorUri' => $personUri,
                'pubDate' => $activity['date'],
                'verb' => $activity['verb'],
                'object' => $activity['object'],
            );

            if ($verbUri == $nsAair . 'Post') {
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

    public function showAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');
        $id = $request->getValue('id', 'get');
        $controller = $request->getValue('c', 'get');

        // get URI
        $personUri = $this->_app->getBaseUri() . '?c=' . $controller . '&id=' . $id;

        $nsFoaf = 'http://xmlns.com/foaf/0.1/';

        $profileQuery = 'PREFIX foaf: <' . $nsFoaf . '> ' .
            'SELECT ?depiction ?name ?nick ' .
            'WHERE { ' .
            '   <' . $personUri . '> a foaf:Person . ' .
            '   OPTIONAL {<' . $personUri . '> foaf:depiction ?depiction .} ' .
            '   OPTIONAL {<' . $personUri . '> foaf:name ?name .} ' .
            '   OPTIONAL {<' . $personUri . '> foaf:nick ?nick .} ' .
            '}';

        // TODO deal with language tags
        $contactsQuery = 'PREFIX foaf: <' . $nsFoaf . '> ' .
            'SELECT ?contactUri ?name ' .
            'WHERE { ' .
            '   <' . $personUri . '> foaf:knows ?contactUri . ' .
            '   OPTIONAL {?contactUri foaf:name ?name .} ' .
            '}';

        $profile = $model->sparqlQuery($profileQuery);

        if (count($profile) < 1) {
            $newStatements = Saft_Tools::getLinkedDataResource($this->_app, $personUri);
            if ($newStatements !== null) {
                $template->addDebug('Import Profile with LinkedDate');

                $modelNew = new Erfurt_Rdf_MemoryModel($newStatements);
                $newStatements = $modelNew->getStatements();

                $template->addDebug(var_export($newStatements, true));

                $profile = array();
                $profile[0] = array(
                    'depiction' => $modelNew->getValue($personUri, $nsFoaf . 'depiction'),
                    'name' => $modelNew->getValue($personUri, $nsFoaf . 'name'),
                    'nick' => $modelNew->getValue($personUri, $nsFoaf . 'nick')
                );
            }
            //$knows = $modelNew->sparqlQuery($contactsQuery);

            $knows = array();
        } else {
            $knows = $model->sparqlQuery($contactsQuery);
        }

        $activityController = $this->_app->getController('Xodx_ActivityController');
        $activities = $activityController->getActivities($personUri);
        $news = $this->getNotifications($personUri);

        $template->profileshowPersonUri = $personUri;
        $template->profileshowDepiction = $profile[0]['depiction'];
        $template->profileshowName = $profile[0]['name'];
        $template->profileshowNick = $profile[0]['nick'];
        $template->profileshowActivities = $activities;
        $template->profileshowKnows = $knows;
        $template->profileshowNews = $news;
        $template->addContent('templates/profileshow.phtml');

        return $template;
    }

    /**
     * Quick fix for Erfurt issue #24 (https://github.com/AKSW/Erfurt/issues/24)
     */
    private static function _issueE24fix ($date)
    {
        if (substr($date, 11, 1) != 'T') {
            $dateObj = date_create($date);
            return date_format($dateObj, 'c');
        } else {
            return $date;
        }
    }
}
