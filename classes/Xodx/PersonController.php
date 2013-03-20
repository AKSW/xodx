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

            $person = new DSSN_Foaf_Person($personUri);

            $this->_persons[$personUri] = $person;
        }

        return $this->_persons[$personUri];
    }

    public function addFriend ($personUri, $contactUri)
    {
        $model = $this->_app->getBootstrap()->getResource('model');
        $userController = $this->_app->getController('Xodx_UserController');

        // update WebID
        $model->addStatement($personUri, 'http://xmlns.com/foaf/0.1/knows', array('type' => 'uri', 'value' => $contactUri));

        $nsAair = 'http://xmlns.notu.be/aair#';
        $activityController = $this->_app->getController('Xodx_ActivityController');

        // add Activity to activity Stream
        $object = array(
            'type' => 'Uri',
            'content' => $contactUri,
            'replyObject' => 'false'
        );
        $activityController->addActivity($personUri, $nsAair . 'MakeFriend', $object);
        $userUri = $userController->getUserUri($personUri);
        $feedUri = $this->getActivityFeedUri($contactUri);
        $userController->subscribeToResource ($userUri, $contactUri, $feedUri);
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
     * TODO return an array of DSSN_Activity objects
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
        $bootstrap  = $this->_app->getBootstrap();
        $model      = $bootstrap->getResource('model');
        $request    = $bootstrap->getResource('request');
        $id         = $request->getValue('id', 'get');
        $controller = $request->getValue('c', 'get');

        // get URI
        $personUri = $this->_app->getBaseUri() . '?c=' . $controller . '&id=' . $id;

        $nsFoaf = 'http://xmlns.com/foaf/0.1/';

        $profileQuery = 'PREFIX foaf: <' . $nsFoaf . '> ' . PHP_EOL;
        $profileQuery.= 'SELECT ?depiction ?name ?nick ' .  PHP_EOL;
        $profileQuery.= 'WHERE { ' .  PHP_EOL;
        $profileQuery.= '   <' . $personUri . '> a foaf:Person . ' . PHP_EOL;
        $profileQuery.= 'OPTIONAL {<' . $personUri . '> foaf:depiction ?depiction .} ' . PHP_EOL;
        $profileQuery.= 'OPTIONAL {<' . $personUri . '> foaf:name ?name .} ' . PHP_EOL;
        $profileQuery.= 'OPTIONAL {<' . $personUri . '> foaf:nick ?nick .} ' . PHP_EOL;
        $profileQuery.= '}'; PHP_EOL;

        // TODO deal with language tags
        $contactsQuery = 'PREFIX foaf: <' . $nsFoaf . '> ' . PHP_EOL;
        $contactsQuery.=     'SELECT ?contactUri ?name ' . PHP_EOL;
        $contactsQuery.= 'WHERE { ' . PHP_EOL;
        $contactsQuery.= '   <' . $personUri . '> foaf:knows ?contactUri . ' . PHP_EOL;
        $contactsQuery.= '   OPTIONAL {?contactUri foaf:name ?name .} ' . PHP_EOL;
        $contactsQuery.= '}';

        $profile = $model->sparqlQuery($profileQuery);

        if (count($profile) < 1) {
            $linkeddataHelper = $this->_app->getHelper('Saft_Helper_LinkeddataHelper');
            $newStatements = $linkeddataHelper->getResource($personUri);
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

        $userController  = $this->_app->getController('Xodx_UserController');
        $userUri         = $userController->getUserUri($personUri);
        $subResources    = $userController->getSubscriptionResources($userUri);

        $activityController = $this->_app->getController('Xodx_ActivityController');
        $activities = array();

        foreach ($subResources as $resourceUri) {
            $act = $activityController->getActivities($resourceUri);
            $activities = array_merge($activities, $act);
        }

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
