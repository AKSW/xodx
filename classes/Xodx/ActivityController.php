<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The ActivityController provides methods to interact with Activity objects
 * TODO: @splattater could you please add documentation to this class
 */
class Xodx_ActivityController extends Saft_Controller
{
    /**
     * Add a new activity …
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
                    'type' => $actType,
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
     * TODO should be replaced by a method with takes a DSSN_Activity object
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
        $nsXodx =     'http://xodx.org/ns#';
        $nsFoaf =     'http://xmlns.com/foaf/0.1/';
        $nsOv =       'http://open.vocab.org/docs/';
        $nsPingback = 'http://purl.org/net/pingback/';
        $nsDssn =     'http://purl.org/net/dssn/';

        $now = date('c');
        $postId = md5(rand());
        $postUri = $this->_app->getBaseUri() . '?c=resource&id=' . $postId;
        $pingbackServer = $this->_app->getBaseUri() . 'index.php?c=pingback&a=ping';
        $activityUri = $this->_app->getBaseUri() . '?c=resource&id=' . md5(rand());
        $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' . urlencode($actorUri);
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

        } else if ($object['type'] == 'Bookmark') {
            $type =      'Bookmark';
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
        $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' . urlencode($activityUri) .
            ';' . $feedUri;

        // If this activity contains a reply, add this statement, too
        if ($replyUri !== 'false') {
            $activity[$activityUri][$nsAair . 'activityContext'][0]['type'] = 'uri';
            $activity[$activityUri][$nsAair . 'activityContext'][0]['value'] = $replyUri;

            $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
                urlencode($replyUri) . ';' . $feedUri;

            // Ping the object we commented
            $pingbackController->sendPing($activityUri, $replyUri,
                'You were pinged from an Anctivity with verb: ' . $verbUri);
        }

           // II. general statements of object resource
        if ($type != 'Bookmark') {
            $activity[$objectUri][$nsRdf . 'type'][0]['type'] = 'uri';
            $activity[$objectUri][$nsRdf . 'type'][0]['value'] = $object['type'];

            $activity[$objectUri][$nsRdf . 'type'][1]['type'] = 'uri';
            $activity[$objectUri][$nsRdf . 'type'][1]['value'] = $object['aairType'];

            $activity[$objectUri][$nsSioc . 'created_at'][0]['type'] = 'literal';
            $activity[$objectUri][$nsSioc . 'created_at'][0]['value'] = $now;
            $activity[$objectUri][$nsSioc . 'created_at'][0]['datatype'] = $nsXsd . 'dateTime';

            $activity[$objectUri][$nsSioc . 'has_creator'][0]['type'] = 'uri';
            $activity[$objectUri][$nsSioc . 'has_creator'][0]['value'] = $actorUri;

            $activity[$objectUri][$nsPingback . 'to'][0]['type'] = 'uri';
            $activity[$objectUri][$nsPingback . 'to'][0]['value'] = $pingbackServer;

            $activity[$objectUri][$nsDssn . 'activityFeed'][0]['type'] = 'uri';
            $activity[$objectUri][$nsDssn . 'activityFeed'][0]['value'] = $this->_app->getBaseUri()
                . '?c=feed&a=getFeed&uri=' . urlencode($objectUri);

                // If this activity contains a reply, add this statement, too
            /*if ($object['replyObject'] !== 'false') {
                $activity[$objectUri][$nsAair . 'activityContext'][0]['type'] = 'uri';
                $activity[$objectUri][$nsAair . 'activityContext'][0]['value'] = $object['replyObject'];
            }**/

            // Triples of note resource
            if ($type == 'Note') {
                $activity[$objectUri][$nsSioc . 'content'][0]['type']  = 'literal';
                $activity[$objectUri][$nsSioc . 'content'][0]['value'] = $object['content'];
                $activity[$objectUri][$nsAair . 'content'][0]['type']  = 'literal';
                $activity[$objectUri][$nsAair . 'content'][0]['value'] = $object['content'];
            }

            // Triples of comment resource
            if ($type == 'Comment') {
                $activity[$objectUri][$nsSioc . 'content'][0]['type']    = 'literal';
                $activity[$objectUri][$nsSioc . 'content'][0]['value']   = $object['content'];
                $activity[$objectUri][$nsAair . 'content'][0]['type']    = 'literal';
                $activity[$objectUri][$nsAair . 'content'][0]['value']   = $object['content'];
                $activity[$objectUri][$nsAair . 'commenter'][0]['type']  = 'uri';
                $activity[$objectUri][$nsAair . 'commenter'][0]['value'] = $actorUri;

            }

            // Triples of photo resource
            if ($type == 'Photo') {
                $activity[$objectUri][$nsOv .   'hasContentType'][0]['type']  = 'literal';
                $activity[$objectUri][$nsOv .   'hasContentType'][0]['value'] = $object['mime'];
                $activity[$objectUri][$nsAair . 'largerImage'][0]['type']     = 'uri';
                $activity[$objectUri][$nsAair . 'largerImage'][0]['value']    = $imageUri;
            }

            $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
                urlencode($objectUri) . ';' . $feedUri;
        }

        // proceed and subsribe to feed
        $store->addMultipleStatements($graphUri, $activity);

        if ($config['push.enable'] == true) {
            $pushController = $this->_app->getController('Xodx_PushController');
            //$pushController->publish($feedUri);
        }

        // Subscribe user to feed of activityObject (photo, post, note)
        $userController = $this->_app->getController('Xodx_UserController');
        $pushController = $this->_app->getController('Xodx_PushController');
        $actorUri = urldecode($actorUri);
        $feeds = explode(';', $feedUri);

        foreach ($feeds as $feed) {
            if ($config['push.enable'] == true) {
                $pushController->publish($feed);
            }
        }

        foreach ($feeds as $feed) {
            $userController->subscribeToFeed($actorUri, $feed);
        }
    }

    /**
     * This method adds multiple activities to the store
     * @param $activities is an array of Xodx_Activity objects
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
     * @return an array of activities
     * TODO return an array of Xodx_Activity objects
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
        $objectQuery = '' .
            'PREFIX atom: <http://www.w3.org/2005/Atom/> ' .
            'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
            'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' .
            'SELECT ?activity ?date ?verb ?object ?person ' .
            'WHERE { ' .
            '   ?activity a                   aair:Activity ; ' .
            '             ?p             <' . $resourceUri . '> ; ' .
            '             aair:activityActor  ?person ; ' .
            '             atom:published      ?date ; ' .
            '             aair:activityVerb   ?verb ; ' .
            '             aair:activityObject ?object . ' .
            '} ' .
            'ORDER BY DESC(?date)';

        // Get given Activity and activities containing activityObjects replying
        // to activityOjects included in this Actitivity
        $activityQuery = '' .
            'PREFIX atom: <http://www.w3.org/2005/Atom/> ' .
            'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
            'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' .
            'SELECT DISTINCT ?date ?verb ?object ?person ?context ' .
            'WHERE { ' .
            '    <' . $resourceUri . '> a                    aair:Activity ; ' .
            '                           aair:activityActor   ?person ; ' .
            '                           atom:published       ?date ; ' .
            '                           aair:activityVerb    ?verb ; ' .
            '                           aair:activityObject  ?object . ' .
            'OPTIONAL { ' .
            '    <' . $resourceUri . '> aair:activityContext ?context . } ' .
            '} ';

        $model = $this->_app->getBootstrap()->getResource('model');

        if ($resourceUri === null) {
            return null;
        }

        // get Type of Ressource and go on
        $resourceController = $this->_app->getController('Xodx_ResourceController');
        $type = $resourceController->getType($resourceUri);

        if ($type == $nsAair . 'Activity') {
            $query = $activityQuery;
        } else {
            $query = $objectQuery;
        }

        $isPerson = false;

        if ($type == $nsFoaf . 'Person') {
            $isPerson = true;
        }

        $activitiesResult = $model->sparqlQuery($query);

        $activities = array();

        foreach ($activitiesResult as $act) {
            if (!isset($act['activity'])) {
                $activityUri = $resourceUri;
            } else {
                $activityUri = $act['activity'];
            }

            //$verbUri = $act['verb'];
            $objectUri = $act['object'];
            $act['date'] = self::_issueE24fix($act['date']);

            $nameHelper = new Xodx_NameHelper($this->_app);
            if (!($resourceController->getType($objectUri))) {
                $type = $objectUri;
            } else {
                $type = $resourceController->getType($objectUri);
            }
            $personName = $nameHelper->getName($act['person']);
            $title = '"' . $personName . ' ' . Saft_Tools::getSpokenWord($act['verb']) . ' ' . $type . '"';

            $activity = array(
                'title'     => $title,
                'uri'       => $activityUri,
                'author'    => $personName,
                'authorUri' => $act['person'],
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
            $activities[] = $activity;
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
