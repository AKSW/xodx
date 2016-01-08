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
class Xodx_ActivityController extends Xodx_ResourceController
{
    /**
     *
     * Method returns all tuples of a resource to html template
     * @param $template
     */
    public function showAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        $objectId = $request->getValue('id', 'get');
        $controller = $request->getValue('c', 'get');
        $objectUri = $this->_app->getBaseUri() . '?c=' . $controller . '&id=' . $objectId;

        $nsAair = 'http://xmlns.notu.be/aair#';
        $nsDssn = 'http://purl.org/net/dssn/';
        $nsAtom = 'http://www.w3.org/2005/Atom/';

        $query = 'PREFIX aair: <' . $nsAair . '>' . PHP_EOL;
        $query.= 'PREFIX dssn: <' . $nsDssn . '>' . PHP_EOL;
        $query.= 'PREFIX atom: <' . $nsAtom . '>' . PHP_EOL;
        $query.= 'SELECT ?actor ?verb ?object ?date ?feed ' . PHP_EOL;
        $query.= 'WHERE { ' . PHP_EOL;
        $query.= '   <' . $objectUri . '> aair:activityActor ?actor ; ' . PHP_EOL;
        $query.= '       aair:activityVerb ?verb ; ' . PHP_EOL;
        $query.= '       aair:activityObject ?object ; ' . PHP_EOL;
        $query.= '       atom:published ?date ; ' . PHP_EOL;
        $query.= '       dssn:activityFeed ?feed . ' . PHP_EOL;
        $query.= '} ';

        $properties = $model->sparqlQuery($query);

        $activityController = $this->_app->getController('Xodx_ActivityController');
        $activities = $activityController->getActivities($objectUri);

        $template->addContent('templates/resourceshow.phtml');
        $template->properties = $properties;
        $template->actor = $properties[0]['actor'];
        $template->verb = $properties[0]['verb'];
        $template->object = $properties[0]['object'];
        $template->date = $properties[0]['date'];
        $template->feed = $properties[0]['feed'];

        $template->activities = $activities;

        return $template;
    }

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

        $logger = $bootstrap->getResource('logger');
        $request = $bootstrap->getResource('request');

        $actType = strtolower($request->getValue('type', 'post'));
        $actContent = $request->getValue('content', 'post');
        $replyObject = $request->getValue('reply', 'post');

        $nsAair = 'http://xmlns.notu.be/aair#';

        // Get Person of current User
        $userController = $this->_app->getController('Xodx_UserController');
        $actorUri = $userController->getUser()->getPerson();

        $logger->debug('Actor URI is: ' . $actorUri);

        $object = array('content' => $actContent);

        if (Erfurt_Uri::check($replyObject)) {
            $object['replyObject'] = $replyObject;
        }

        switch ($actType) {
            case 'post';
            case 'note';
                $verbUri = $nsAair . 'Post';
                $object['type'] = 'note';
            break;
            case 'comment';
                $verbUri = $nsAair . 'Post';
                $object['type'] = 'comment';
            break;
            case 'bookmark';
                $verbUri = $nsAair . 'Share';
                $object['type'] = 'uri';
            break;
            case 'photo';
                $fieldName = 'content';
                $mediaController = $this->_app->getController('Xodx_MediaController');
                $fileInfo = $mediaController->uploadImage($fieldName);

                $verbUri = $nsAair . 'Post';
                $object['type'] = 'photo';
                $object['fileName'] = $fileInfo['fileId'];
                $object['mime'] = $fileInfo['mimeType'];
            break;
            default:
                $message = 'The given activity type ("' . $actType . '") is unknown.';
                $logger->error($message);
                throw new Exception($message);
            break;
        }

        $this->addActivity($actorUri, $verbUri, $object);

        return $template;
    }

    /**
     * This method adds a new activity to the store
     * TODO should be replaced by a method which takes a DSSN_Activity object
     */
    public function addActivity ($actorUri, $verbUri, $object)
    {
        $bootstrap = $this->_app->getBootstrap();

        $model = $bootstrap->getResource('model');
        $config = $bootstrap->getResource('config');
        $logger = $bootstrap->getResource('logger');

        $baseUri = $this->_app->getBaseUri();

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
        $pingbackServer = $baseUri . '?c=pingback&a=ping';

        // Generate URIs for the post, activity, actorFeed and object
        $postUri = $baseUri . '?c=resource&id=' . md5(rand());
        $objectUri = $baseUri . '?c=resource&id=' . md5(rand());
        $activityUri = $baseUri . '?c=activity&id=' . md5(rand());
        $actorFeedUri = $baseUri .  '?c=feed&a=getFeed&uri=' . urlencode($actorUri);
        $activityFeedUri = $baseUri . '?c=feed&a=getFeed&uri=' .  urlencode($activityUri);

        $publishFeeds = array(
            $actorUri => $actorFeedUri
        );
        $subscribeFeeds = array();

        $object['type'] = strtolower($object['type']);

        if ($object['type'] == 'photo') {
            $object['type'] = $nsFoaf . 'Image';
            $object['aairType'] = $nsAair . 'Photo';
            $type = 'Photo';
            // Take photo filename as objectname
            $content =   $object['content'];
            $objectId =  $object['fileName'];
            $objectUri = $this->_app->getBaseUri() . '?c=media&id=' . $objectId;
            $imageUri =  $this->_app->getBaseUri() . '?c=media&a=img&id=' . $objectId;

        } else if ($object['type'] == 'uri') {
            $type =      'Uri';
            $objectUri = $object['content'];

        } else if ($object['type'] == 'note') {
            $type               = 'Note';
            $object['type']     = $nsSioc . 'Post';
            $object['aairType'] = $nsAair . 'Note';
            $content            = $object['content'];

        } else if ($object['type'] == 'comment') {
            $object['type']     = $nsSioct . 'Comment';
            $object['aairType'] = $nsAair . 'Comment';
            $type               = 'Comment';
            $content            = $object['content'];
        }

        // Creating resources
        // I. activity resource
        // contains all triples activities have in common (e.g. date of publish)
        $activityTriples = array(
            $activityUri => array(
                $nsRdf . 'type' => array(
                    array('type' => 'uri', 'value' => $nsAair . 'Activity')
                ),
                $nsAair . 'activityActor' => array(
                    array('type' => 'uri', 'value' => $actorUri)
                ),
                $nsAair . 'activityVerb' => array(
                    array('type' => 'uri', 'value' => $verbUri)
                ),
                $nsAair . 'activityObject' => array(
                    array('type' => 'uri', 'value' => $objectUri)
                ),
                $nsAtom . 'published' => array(
                    array(
                        'type' =>     'literal',
                        'value' =>    $now,
                        'datatype' => $nsXsd . 'dateTime'
                    )
                ),
                $nsPingback . 'to' => array(
                    array('type' => 'uri', 'value' => $pingbackServer)
                ),
                $nsDssn . 'activityFeed' => array(
                    array('type' => 'uri', 'value' => $activityFeedUri)
                )
            )
        );

        // If this activity contains a reply, add this statement, too
        if (isset($object['replyObject']) && Erfurt_Uri::check($object['replyObject'])) {
            $replyUri = $object['replyObject'];

            $activityTriples[$activityUri][$nsAair . 'activityContext'][] = array(
                'type' => 'uri', 'value' => $replyUri
            );

            $subscribeFeeds[$replyUri] = null;
        }

        $model->addMultipleStatements($activityTriples);
        $publishFeeds[$activityUri] = $activityFeedUri;
        $subscribeFeeds[$activityUri] = $activityFeedUri;

        // II. general statements of object resource
        // if $type == 'Uri' the resource of aair:activityObject statement allready exists
        // e.g. 'Sharing a Bookmark (URI)' and 'Friending'
        if ($type != 'Uri') {
            $objectFeedUri = $baseUri . '?c=feed&a=getFeed&uri=' . urlencode($objectUri);
            $feedUri[$objectUri] = $objectFeedUri;

            $objectTriples = array(
                $objectUri => array(
                    $nsRdf . 'type' => array(
                        array('type' => 'uri', 'value' => $object['type']),
                        array('type' => 'uri', 'value' => $object['aairType'])
                    ),
                    $nsSioc . 'created_at' => array(
                        array(
                            'type' => 'literal',
                            'value' => $now,
                            'datatype' => $nsXsd . 'dateTime'
                        ),
                    ),
                    $nsFoaf . 'maker' => array(
                        array('type' => 'uri', 'value' => $actorUri)
                    ),
                    $nsPingback . 'to' => array(
                        array('type' => 'uri', 'value' => $pingbackServer)
                    ),
                    $nsDssn . 'activityFeed' => array(
                        array('type' => 'uri', 'value' => $objectFeedUri)
                    )
                )
            );

            // Triples of note resource
            if ($type == 'Note' || $type == 'Comment') {
                $objectTriples[$objectUri][$nsSioc . 'content'][] = array(
                    'type' => 'literal', 'value' => $object['content']
                );

                $objectTriples[$objectUri][$nsAair . 'content'][] = array(
                    'type' => 'literal', 'value' => $object['content']
                );
            }

            // Triples of comment resource
            if ($type == 'Comment') {
                $objectTriples[$objectUri][$nsAair . 'commenter'][] = array(
                    'type' => 'uri', 'value' => $actorUri
                );

                if (isset($object['replyObject']) && Erfurt_Uri::check($object['replyObject'])) {
                    $objectTriples[$objectUri][$nsSioc . 'reply_of'][] = array(
                        'type' => 'uri', 'value' => $object['replyObject']
                    );
                }
            }

            // Triples of photo resource
            if ($type == 'Photo') {
                $objectTriples[$objectUri][$nsOv .   'hasContentType'][] = array(
                    'type' => 'literal', 'value' => $object['mime']
                );

                $objectTriples[$objectUri][$nsAair . 'largerImage'][] = array(
                    'type' => 'uri', 'value' => $imageUri
                );
            }

            $model->addMultipleStatements($objectTriples);
            $publishFeeds[$objectUri] = $objectFeedUri;
            $subscribeFeeds[$objectUri] = $objectFeedUri;
        } else {
            // If the object is a URI send a ping
            $pingbackController = $this->_app->getController('Xodx_PingbackController');
            $pingbackController->sendPing(
                $activityUri, $objectUri,
                'You were pinged from an' .  ' Activity with verb: ' . $verbUri
            );

            // maybe we should also subscribe to this resource
        }

        // III. send a ping if it was a reply
        if (isset($object['replyObject']) && Erfurt_Uri::check($object['replyObject'])) {
            $pingbackController = $this->_app->getController('Xodx_PingbackController');
            $pingbackController->sendPing(
                $activityUri, $object['replyObject'], 'You were pinged from an Activity with verb: ' . $verbUri
            );
        }

        // IV. Subscribe user to activity feeds
        $userController = $this->_app->getController('Xodx_UserController');
        $pushController = $this->_app->getController('Xodx_PushController');

        foreach ($publishFeeds as $resource => $feed) {
            if ($config['push.enable']) {
                $pushController->publish($feed);
            }
        }

        foreach ($subscribeFeeds as $resourceUri => $feedUri) {
            if ($feedUri == null) {
                $userController->subscribeToResource($actorUri, $resourceUri);
            } else {
                $userController->subscribeToResource($actorUri, $resourceUri, $feedUri, true);
            }
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
        $model = $bootstrap->getResource('model');

        foreach ($activities as $activity) {
            $mode->addMultipleStatements($activity->toGraphArray());
        }
    }

    /**
     * @param $template the template
     * @returns the template
     */
    public function replyAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');
        $objectUri = $request->getValue('object', 'post');
        $actorUri = $request->getValue('actor', 'post');
        $template->replyObject = $objectUri;
        $template->replyActor = $actorUri;

        return $template;
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
        $activityQuery.= 'ORDER BY DESC(?date)'; PHP_EOL;

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
        $personQuery.= 'ORDER BY DESC(?date)'; PHP_EOL;

        $model = $this->_app->getBootstrap()->getResource('model');

        if ($resourceUri === null) {
            return null;
        }

        // get Type of resource and go on
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

            if (isset($act['context']) && !empty($act['context'])) {
                $activity['context'] = $act['context'];
            }

            $objectQuery = 'PREFIX atom: <http://www.w3.org/2005/Atom/> ' . PHP_EOL;
            $objectQuery.= 'PREFIX aair: <http://xmlns.notu.be/aair#> ' . PHP_EOL;
            $objectQuery.= 'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' . PHP_EOL;
            $objectQuery.= 'PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' . PHP_EOL;
            $objectQuery.= 'SELECT ?type ?content ?image ?date ' . PHP_EOL;
            $objectQuery.= 'WHERE { ' . PHP_EOL;
            $objectQuery.= '   <' . $objectUri . '> a ?type ; ' . PHP_EOL;
            $objectQuery.= '        sioc:created_at ?date . ' . PHP_EOL;
            $objectQuery.= '   OPTIONAL {<' . $objectUri . '> sioc:content ?content .} ' . PHP_EOL;
            $objectQuery.= '   OPTIONAL {<' . $objectUri . '> aair:largerImage ?image .} ' . PHP_EOL;
            $objectQuery.= '} ' . PHP_EOL;
            $objectQuery.= 'ORDER BY DESC(?date)' . PHP_EOL;

            $objectResult = $model->sparqlQuery($objectQuery);

            if (count($objectResult) > 0) {
                $activity['objectType']    = $objectResult[0]['type'];
                $activity['objectPubDate'] = self::_issueE24fix($objectResult[0]['date']);
                if (!empty($objectResult[0]['content'])) {
                    $activity['objectContent'] = $objectResult[0]['content'];
                }
                if (!empty($objectResult[0]['image'])) {
                    $activity['objectImage'] = $objectResult[0]['image'];
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
