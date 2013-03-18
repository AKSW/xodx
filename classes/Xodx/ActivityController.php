<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The ActivityController provides methods to interact with Activity objects
 * TODO: @splattater could you please add documentation to this class
 * @deprecated this should be moved to lib-dssn-php
 */
class Xodx_ActivityController extends Saft_Controller
{
    /**
     * Add a new activity after user action.
     * Activities are:  - Friendig
     *                  - Post a status note
     *                  - Post an image
     *                  - Reply to an activity
     */
    public function addactivityAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();

        $request = $bootstrap->getResource('request');
        $actorUri = $request->getValue('actor', 'post');
        $verb = $request->getValue('verb', 'post');
        $actType = $request->getValue('type', 'post');
        $actContent = $request->getValue('content', 'post');
        $replyObject = $request->getValue('reply', 'post');

        $nsAair = 'http://xmlns.notu.be/aair#';

        switch (strtolower($verb)) {
            case 'post':
                $verbUri = $nsAair . 'Post';
                break;
            case 'share':
                $verbUri = $nsAair . 'Share';
                break;
        }

        switch ($actType) {
            case 'Note';
                $object = array(
                    'type' => $actType,
                    'content' => $actContent,
                    'replyObject' => $replyObject,
                );
                $debugStr = $this->addActivity($actorUri, $verbUri, $object);
            break;
            case 'Comment';
                $object = array(
                    'type' => $actType,
                    'content' => $actContent,
                    'replyObject' => $replyObject,
                );
                $debugStr = $this->addActivity($actorUri, $verbUri, $object);
            break;
            case 'Bookmark';
                $object = array(
                    'type' => 'Uri',
                    'content' => $actContent,
                    'replyObject' => $replyObject,
                );
                $debugStr = $this->addActivity($actorUri, $verbUri, $object);
            break;
            case 'Photo';
                $fieldName = 'content';
                $mediaController = $this->_app->getController('Xodx_MediaController');
                $fileInfo = $mediaController->uploadImage($fieldName);
                $object = array(
                    'type' => $actType,
                    'content' => $actContent,
                    'fileName' => $fileInfo['fileId'],
                    'mime' => $fileInfo['mimeType'],
                    'replyObject' => $replyObject,
                );
                $debugStr = $this->addActivity($actorUri, $verbUri, $object);
            break;
        }
        $template->addDebug($debugStr);

        return $template;
    }

    /**
     * This method adds a new activity to the store
     * TODO should be replaced by a method which takes a DSSN_Activity object
     */
    public function addActivity ($actorUri, $verbUri, $object)
    {

        $bootstrap = $this->_app->getBootstrap();

        $store = $bootstrap->getResource('store');
        $model = $bootstrap->getResource('model');
        $config = $bootstrap->getResource('config');
        $graphUri = $model->getModelIri();
        $nsXsd =      'http://www.w3.org/2001/XMLSchema#';
        $nsRdf =      'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsSioc =     'http://rdfs.org/sioc/ns#';
        $nsSioct =    'http://rdfs.org/sioc/types#';
        $nsAtom =     'http://www.w3.org/2005/Atom/';
        $nsAair =     'http://xmlns.notu.be/aair#';
        $nsFoaf =     'http://xmlns.com/foaf/0.1/';
        $nsOv =       'http://open.vocab.org/docs/';
        $nsPingback = 'http://purl.org/net/pingback/';
        $nsDssn =     'http://purl.org/net/dssn/';

        $now = date('c');
        $postId = md5(rand());
        $postUri = $this->_app->getBaseUri() . '?c=resource&id=' . $postId;
        $pingbackServer = $this->_app->getBaseUri() . 'index.php?c=pingback&a=ping';
        $activityUri = $this->_app->getBaseUri() . '?c=resource&id=' . md5(rand());
        $feedUri[$actorUri] = $this->_app->getBaseUri() .
            '?c=feed&a=getFeed&uri=' . urlencode($actorUri);
        $objectId = md5(rand());
        $objectUri = $this->_app->getBaseUri() . '?c=resource&id=' . $objectId;
        // TODO: Notice: Undefined index: replyObject
        $replyUri = $object['replyObject'];

        if ($object['type'] == 'Photo') {
            $object['type'] = $nsFoaf . 'Image';
            $object['aairType'] = $nsAair . 'Photo';
            $type = 'Photo';
            // Take photo filename as objectname
            $content =   $object['content'];
            $objectId =  $object['fileName'];
            $objectUri = $this->_app->getBaseUri() . '?c=resource&id=' . $objectId;
            $imageUri =  $this->_app->getBaseUri() . '?c=resource&a=img&id=' . $objectId;

        } else if ($object['type'] == 'Uri') {
            $type =      'Uri';
            $objectUri = $object['content'];

        } else if ($object['type'] == 'Note') {
            $type               = 'Note';
            $object['type']     = $nsSioc . 'Note';
            $object['aairType'] = $nsAair . 'Note';
            $content            = $object['content'];

        } else if ($object['type'] == 'Comment') {
            $object['type']     = $nsSioct . 'Comment';
            $object['aairType'] = $nsAair . 'Comment';
            $type               = 'Comment';
            $content            = $object['content'];

            if (!($object['replyObject'])) {
                $replyUri = $object['replyObject'];
            }
        }

        $pingbackController = $this->_app->getController('Xodx_PingbackController');

        // Creating resources
        // I. activity resource
        // contains all triples activities have in common (e.g. date of publish)
        $activity = array(
            $activityUri => array(
                $nsRdf . 'type' => array(
                    array(
                        'type' =>  'uri',
                        'value' => $nsAair . 'Activity'
                    )
                ),
                $nsAtom . 'published' => array(
                    array(
                        'type' =>     'literal',
                        'value' =>    $now,
                        'datatype' => $nsXsd . 'dateTime'
                    )
                ),
                $nsAair . 'activityActor' => array(
                    array(
                        'type' =>     'uri',
                        'value' =>    $actorUri
                    )
                ),
                $nsAair . 'activityVerb' => array(
                    array(
                        'type' =>     'uri',
                        'value' =>    $verbUri
                    )
                ),
                $nsAair . 'activityObject' => array(
                    array(
                        'type' =>     'uri',
                        'value' =>    $objectUri
                    )
                ),
                $nsPingback . 'to' => array(
                    array(
                        'type' =>     'uri',
                        'value' =>    $pingbackServer
                    )
                ),
                $nsDssn . 'activityFeed' => array(
                    array(
                        'type' =>     'uri',
                        'value' =>    $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
                            urlencode($activityUri)
                    )
                )
            )
        );
        $feedUri[$activityUri] = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
            urlencode($activityUri);

        // If this activity contains a reply, add this statement, too
        if ($replyUri !== 'false') {
            $activity[$activityUri][$nsAair . 'activityContext'][] = array(
                'type' => 'uri', 'value' => $replyUri
            );

            $feedUri[replyUri] = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
                urlencode($replyUri);

            // Ping the object we commented
            $pingbackController->sendPing($activityUri, $replyUri,
                'You were pinged from an Activity with verb: ' . $verbUri);
        }

        // II. general statements of object resource
        // if $type == 'Uri' the ressource of aair:activityObject statement allready exists
        // e.g. 'Sharing a Bookmark (URI)' and 'Friending'
        if ($type != 'Uri') {
            $activity[$objectUri][$nsRdf . 'type'][] = array(
                'type' => 'uri', 'value' => $object['type']
            );

            $activity[$objectUri][$nsRdf . 'type'][] = array(
                'type' => 'uri', 'value' => $object['aairType']
            );

            $activity[$objectUri][$nsSioc . 'created_at'][] = array(
                'type' => 'literal', 'value' => $now, 'datatype' => $nsXsd . 'dateTime'
            );

            $activity[$objectUri][$nsSioc . 'has_creator'][] = array(
                'type' => 'uri', 'value' => $actorUri
            );

            $activity[$objectUri][$nsPingback . 'to'][] = array(
                'type' => 'uri', 'value' => $pingbackServer
            );

            $activity[$objectUri][$nsDssn . 'activityFeed'][] = array(
                'type' => 'uri', 'value' => $this->_app->getBaseUri()
                . '?c=feed&a=getFeed&uri=' . urlencode($objectUri)
            );

            // Triples of note resource
            if ($type == 'Note') {
                $activity[$objectUri][$nsSioc . 'content'][] = array(
                    'type' => 'literal', 'value' => $object['content']
                );

                $activity[$objectUri][$nsAair . 'content'][] = array(
                    'type' => 'literal', 'value' => $object['content']
                );
            }

            // Triples of comment resource
            if ($type == 'Comment') {
                $activity[$objectUri][$nsSioc . 'content'][] = array(
                    'type' => 'literal', 'value' => $object['content']
                );

                $activity[$objectUri][$nsAair . 'content'][] = array(
                    'type' => 'literal', 'value' => $object['content']
                );

                $activity[$objectUri][$nsAair . 'commenter'][] = array(
                    'type' => 'uri', 'value' => $actorUri
                );
            }

            // Triples of photo resource
            if ($type == 'Photo') {
                $activity[$objectUri][$nsOv .   'hasContentType'][] = array(
                    'type' => 'literal', 'value' => $object['mime']
                );

                $activity[$objectUri][$nsAair . 'largerImage'][] = array(
                    'type' => 'uri', 'value' => $imageUri
                );
            }

            $feedUri[$objectUri] = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
                urlencode($objectUri);

        // processes to perform if activityObject is a given ressource
        } else if ($type = 'Uri') {

            // try to ping the ressource
            $pingbackController->sendPing($activityUri, $objectUri, 'You were pinged from an' .
                ' Activity with verb: ' . $verbUri);

            // try to add the activity feed of the ressource to the feeds we want to subscribe
            $resourceController = $this->_app->getController('Xodx_ResourceController');
            $foundFeedUri = $resourceController->getActivityFeedUri($objectUri);

            if ($foundFeedUri) {
                $feedUri[$objectUri] = $foundFeedUri;
            }

        }

        // proceed and subsribe to feed
        $store->addMultipleStatements($graphUri, $activity);

        // Subscribe user to activity feeds
        $userController = $this->_app->getController('Xodx_UserController');
        $pushController = $this->_app->getController('Xodx_PushController');
        $actorUri = urldecode($actorUri);

        foreach ($feedUri as $feed) {
            if ($config['push.enable'] == true) {
                $pushController->publish($feed);
            }
        }

        foreach ($feedUri as $resourceUri => $feedUri) {
            $userController->subscribeToResource($actorUri, $resourceUri, $feedUri);
        }
    }

    /**
     * This method adds multiple activities to the store
     * @param $activities is an array of Xodx_Activity objects
     * @deprecated should be replaced by according DSSN_Activity methods
     */
    public function addActivities (array $activities)
    {
        $bootstrap = $this->_app->getBootstrap();

        $store = $bootstrap->getResource('store');
        $model = $bootstrap->getResource('model');
        $graphUri = $model->getModelIri();

        foreach ($activities as $activity) {
            $store->addMultipleStatements($graphUri, $activity->toGraphArray());
        }
    }

    /**
     * @param $template the template
     * @returns the template
     */
    public function replyFormAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');
        $objectUri = $request->getValue('object', 'post');
        $actorUri = $request->getValue('actor', 'post');
        $template->replyObject = $objectUri;
        $template->replyActor = $actorUri;
        $template->addContent('templates/reply.phtml');

        return $template;
    }

    /**
     * @param $template the template
     * @returns the template
     */
    public function replyAction ($template)
    {

    }

    /**
     * @param $personUri the uri of the person whoes activities should be returned
     * @return an array of activity arrays
     * TODO return an array of DSSN_Activity objects
     * TODO getActivity by objectURI
     */
    public function getActivities ($resourceUri)
    {
        // There are two namespaces, one is used in atom files the other one for RDF
        $nsAairAtom = 'http://activitystrea.ms/schema/1.0/';
        $nsAair = 'http://xmlns.notu.be/aair#';

        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsFoaf = 'http://xmlns.com/foaf/0.1/';
        $nsOv = 'http://open.vocab.org/docs/';

        // Queries

        // Get activity of an ActivityObject and all activities
        // containing objects replying to this ActivityObject
        $objectQuery = 'PREFIX atom: <http://www.w3.org/2005/Atom/> ' . PHP_EOL;
        $objectQuery.= 'PREFIX aair: <http://xmlns.notu.be/aair#> ' . PHP_EOL;
        $objectQuery.= 'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' . PHP_EOL;
        $objectQuery.= 'SELECT DISTINCT ?activity ?date ?verb ?object ?person ' . PHP_EOL;
        $objectQuery.= 'WHERE { ' . PHP_EOL;
        $objectQuery.= '   ?activity a                   aair:Activity ; ' . PHP_EOL;
        $objectQuery.= '             ?p             <' . $resourceUri . '> ; ' . PHP_EOL;
        $objectQuery.= '             aair:activityActor  ?person ; ' . PHP_EOL;
        $objectQuery.= '             atom:published      ?date ; ' . PHP_EOL;
        $objectQuery.= '             aair:activityVerb   ?verb ; ' . PHP_EOL;
        $objectQuery.= '             aair:activityObject ?object . ' . PHP_EOL;
        $objectQuery.= '} ' . PHP_EOL;
        $objectQuery.= 'ORDER BY DESC(?date)'; PHP_EOL;

        // Get given activity and activities containing activityObjects replying
        // to activityOjects included in this Actitivity
        $activityQuery = 'PREFIX atom: <http://www.w3.org/2005/Atom/> ' . PHP_EOL;
        $activityQuery.= 'PREFIX aair: <http://xmlns.notu.be/aair#> ' . PHP_EOL;
        $activityQuery.= 'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' . PHP_EOL;
        $activityQuery.= 'SELECT DISTINCT ?date ?verb ?object ?person ?context ' . PHP_EOL;
        $activityQuery.= 'WHERE { ' . PHP_EOL;
        $activityQuery.= '<' . $resourceUri . '> a                    aair:Activity ; ' . PHP_EOL;
        $activityQuery.= '                       aair:activityActor   ?person ; ' . PHP_EOL;
        $activityQuery.= '                       atom:published       ?date ; ' . PHP_EOL;
        $activityQuery.= '                       aair:activityVerb    ?verb ; ' . PHP_EOL;
        $activityQuery.= '                       aair:activityObject  ?object . ' . PHP_EOL;
        $activityQuery.= 'OPTIONAL { ' . PHP_EOL;
        $activityQuery.= '    <' . $resourceUri . '> aair:activityContext ?context . } ' . PHP_EOL;
        $activityQuery.= '} ';

        // Get all activity with an activityActor given in $resourceUri
        $personQuery = 'PREFIX atom: <http://www.w3.org/2005/Atom/> ' . PHP_EOL;
        $personQuery.= 'PREFIX aair: <http://xmlns.notu.be/aair#> ' . PHP_EOL;
        $personQuery.= 'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' . PHP_EOL;
        $personQuery.= 'SELECT DISTINCT ?activity ?date ?verb ?object ?context ' . PHP_EOL;
        $personQuery.= 'WHERE { ' . PHP_EOL;
        $personQuery.= '     ?activity  a                    aair:Activity ; ' . PHP_EOL;
        $personQuery.= '                aair:activityActor   <' . $resourceUri . '> ; ' . PHP_EOL;
        $personQuery.= '                atom:published       ?date ; ' . PHP_EOL;
        $personQuery.= '                aair:activityVerb    ?verb ; ' . PHP_EOL;
        $personQuery.= '                aair:activityObject  ?object . ' . PHP_EOL;
        $personQuery.= 'OPTIONAL { ' . PHP_EOL;
        $personQuery.= '<' . $resourceUri . '> aair:activityContext ?context . } ' . PHP_EOL;
        $personQuery.= '} ';

        $model = $this->_app->getBootstrap()->getResource('model');

        if ($resourceUri === null) {
            return null;
        }

        // get Type of Ressource and go on
        $resourceController = $this->_app->getController('Xodx_ResourceController');
        $type = $resourceController->getType($resourceUri);

        if ($type == $nsAair . 'Activity') {
            $query = $activityQuery;
        } else if ($type == $nsFoaf . 'Person') {
            $query = $personQuery;
        } else {
            $query = $objectQuery;
        }

        $activitiesResult = $model->sparqlQuery($query);

        $activities = array();

        foreach ($activitiesResult as $act) {
            if (!isset($act['activity'])) {
                $activityUri = $resourceUri;
            } else {
                $activityUri = $act['activity'];
            }
            if (!isset($act['person'])) {
                $personUri = $resourceUri;
            } else {
                $personUri = $act['person'];
            }

            //$verbUri = $act['verb'];
            $objectUri = $act['object'];
            $act['date'] = self::_issueE24fix($act['date']);

            $nameHelper = new Xodx_NameHelper($this->_app);
            $resourceType = $resourceController->getType($objectUri);

            $objectName = $nameHelper->getName($objectUri);
            if ($objectName === false) {
                if ($resourceType === false) {
                    $titleObject = $objectUri;
                } else {
                    $titleObject = $resourceType;
                }
            } else {
                $titleObject = $objectName;
            }

            $personName = $nameHelper->getName($personUri);
            if ($personName === false) {
                $personName = $personUri;
            }
            $title = '"' . $personName . ' ';
            $title.= Saft_Tools::getSpokenWord($act['verb']);
            $title.= ' ' . $titleObject . '"';

            $activity = array(
                'title'     => $title,
                'uri'       => $activityUri,
                'author'    => $personName,
                'authorUri' => $personUri,
                'pubDate'   => $act['date'],
                'verb'      => $act['verb'],
                'object'    => $objectUri,
                'type'      => $type,
            );

            if (isset($act['context'])) {
                $activity['context'] = $act['context'];
            }

            $objectResult = $model->sparqlQuery(
                'PREFIX atom: <http://www.w3.org/2005/Atom/> ' .
                'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
                'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' .
                'PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' .
                'SELECT ?type ?content ?date ' .
                'WHERE { ' .
                '   <' . $objectUri . '> a ?type ; ' .
                '        sioc:created_at ?date . ' .
                '   OPTIONAL {<' . $objectUri . '> sioc:content ?content .} ' .
                '} '
                );

            if (count($objectResult) > 0) {
                $activity['objectType']    = $objectResult[0]['type'];
                $activity['objectPubDate'] = self::_issueE24fix($objectResult[0]['date']);
                if (!empty($objectResult[0]['content'])) {
                    $activity['objectContent'] = $objectResult[0]['content'];
                }
            // set data from activity to get valid feed
            } else {
                $activity['objectPubDate'] = $activity['pubDate'];
            }

            $activity['objectFeed'] = htmlentities($this->_app->getBaseUri() .
                '?c=feed&a=getFeed&uri=') . urlencode($objectUri);
            $activities[$activityUri] = $activity;
        }

        return $activities;
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
