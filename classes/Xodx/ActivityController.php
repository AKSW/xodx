<?php

class Xodx_ActivityController extends Xodx_Controller
{
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
                $verbUri = $nsAair . $verb;
                break;
            case 'share':
                $verbUri = $nsAair . $verb;
                break;
        }

        switch ($actType) {
            case 'Note':
                $object = array(
                    'type' => $actType,
                    'content' => $actContent,
                    'replyObject' => $replyObject
                );
                $debugStr = $this->addActivity($actorUri, $verbUri, $object);
            break;
            case 'Bookmark':
                $object = array(
                    'type' => $actType,
                    'about' => $request->getValue('about', 'post'),
                    'content' => $actContent,
                    'replyObject' => $replyObject
                );
                $debugStr = $this->addActivity($actorUri, $verbUri, $object);
            break;
            case 'Photo':
                $fieldName = 'content';
                $mediaController = new Xodx_MediaController($this->_app);
                $fileInfo = $mediaController->uploadImage($fieldName);
                $object = array(
                    'type' => $actType,
                    'about' => $request->getValue('about', 'post'),
                    'content' => $actContent,
                    'fileName' => $fileInfo['fileId'],
                    'mimeType' => $fileInfo['mimeType'],
                    'replyObject' => $replyObject
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

        $nsXsd = 'http://www.w3.org/2001/XMLSchema#';
        $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsAtom = 'http://www.w3.org/2005/Atom/';
        $nsAair = 'http://xmlns.notu.be/aair#';
        $nsXodx = 'http://xodx.org/ns#';
        $nsFoaf = 'http://xmlns.com/foaf/0.1/';
        $nsOv = 'http://open.vocab.org/docs/';
        $nsPingback = 'http://purl.org/net/pingback/';
        $nsDssn = 'http://purl.org/net/dssn/';

        $now = date('c');
        $postId = md5(rand());
        $pingbackServer = $this->_app->getBaseUri() . 'index.php?c=pingback&a=ping';
        $activityUri = $this->_app->getBaseUri() . '?c=resource&id=' . md5(rand());
        $postUri = $this->_app->getBaseUri() . '?c=resource&id=' . $postId;
        $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' . urlencode($actorUri);

        if ($object['type'] == 'Photo') {
            $object['type'] = $nsFoaf . 'Image';
            $type = 'Photo';
            // Take photo filename as objectname
            $objectId = $object['fileName'];
            $objectUri = $this->_app->getBaseUri() . '?c=resource&id=' . $objectId;

            if (!empty($object['about'])) {
                $commentId = md5(rand());
                $commentUri = $this->_app->getBaseUri() . '?c=resource&id=' . $commentId;
                $object['content'] = $object['about'];
            }
        } else if ($object['type'] == 'Bookmark') {
            $object['type'] = $nsFoaf . 'Document';
            $type = 'Bookmark';
            //$objectId = md5(rand());
            //$objectUri = $this->_app->getBaseUri() . '?c=resource&id=' . $objectId;
            $objectUri = $object['content'];
            if (!empty($object['about'])) {
                $commentId = md5(rand());
                $commentUri = $this->_app->getBaseUri() . '?c=resource&id=' . $commentId;
                $object['content'] = $object['about'];
            }
        } else if ($object['type'] == 'Note') {
            $object['type'] = $nsSioc . 'Comment';
            $type = 'Comment';
            $objectId = md5(rand());
            $objectUri = $this->_app->getBaseUri() . '?c=resource&id=' . $objectId;
        }

        $pingbackController = $this->_app->getController('Xodx_PingbackController');

        // Creating resources
        // I. activity resource
        $activity = array(
            $activityUri => array(
                $nsRdf . 'type' => array(
                    array(
                        'type' => 'uri',
                        'value' => $nsAair . 'Activity'
                    )
                ),
                $nsAtom . 'published' => array(
                    array(
                        'type' => 'literal',
                        'value' => $now,
                        'datatype' => $nsXsd . 'dateTime'
                    )
                ),
                $nsAair . 'activityActor' => array(
                    array(
                        'type' => 'uri',
                        'value' => $actorUri
                    )
                ),
                $nsAair . 'activityVerb' => array(
                    array(
                        'type' => 'uri',
                        'value' => $verbUri
                    )
                ),
                $nsPingback . 'to' => array(
                    array(
                        'type' => 'uri',
                        'value' => $pingbackServer
                    )
                ),
                $nsDssn . 'activityFeed' => array(
                    array(
                        'type' => 'uri',
                        'value' => $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
                            urlencode($activityUri)
                    )
                )
            )
        );
        $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' . urlencode($activityUri) .
            ';' . $feedUri;

        // If this activity is a reply, add this statement, too
        if ($object['replyObject'] !== 'false') {
            $activity[$activityUri][$nsAair . 'activityContext'][0]['type'] = 'uri';
            $activity[$activityUri][$nsAair . 'activityContext'][0]['value'] = $object['replyObject'];
            $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
                urlencode($object['replyObject']) . ';' . $feedUri;
            // Ping the object we commented
            $pingbackController->sendPing($activityUri, $object['replyObject'],
                'You were pinged from an Anctivity with verb: ' . $verbUri);
        }

        //If activity is posting/sharing a thing by commenting it, add this
        if (($type == 'Bookmark' || $type == 'Photo') && !empty($object['about']))
        {
            $activity[$activityUri][$nsAair . 'activityContext'][0]['type'] = 'uri';
            $activity[$activityUri][$nsAair . 'activityContext'][0]['value'] = $objectUri;
            $activity[$activityUri][$nsAair . 'activityObject'][0]['type'] = 'uri';
            $activity[$activityUri][$nsAair . 'activityObject'][0]['value'] = $commentUri;
        } else {
            $activity[$activityUri][$nsAair . 'activityObject'][0]['type'] = 'uri';
            $activity[$activityUri][$nsAair . 'activityObject'][0]['value'] = $objectUri;
        }

        if ($type != 'Bookmark') {
            // II. general statements of object resource
            $activity[$objectUri][$nsRdf . 'type'][0]['type'] = 'uri';
            $activity[$objectUri][$nsRdf . 'type'][0]['value'] = $object['type'];

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
            if ($object['replyObject'] !== 'false') {
                $activity[$objectUri][$nsRdf . 'about'][0]['type'] = 'uri';
                $activity[$objectUri][$nsRdf . 'about'][0]['value'] = $object['replyObject'];
            }
            // Triples of Comment resource
            if ($type == 'Comment') {
                $activity[$objectUri][$nsSioc . 'content'][0]['type'] = 'literal';
                $activity[$objectUri][$nsSioc . 'content'][0]['value'] = $object['content'];
            }
            // Triples of photo object
            if ($type == 'Photo') {
                $activity[$objectUri][$nsOv . 'hasContentType'][0]['type'] = 'literal';
                $activity[$objectUri][$nsOv . 'hasContentType'][0]['value'] = $object['mimeType'];
            }
            $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
                urlencode($objectUri) . ';' . $feedUri;
        }


        // III Create comment resource
        // actually only for posting Images
        if (!empty($object['about'])) {
            $now = date('c');
            $activity[$commentUri][$nsRdf . 'type'][0]['type'] = 'uri';
            $activity[$commentUri][$nsRdf . 'type'][0]['value'] = $nsSioc . 'Comment';

            $activity[$commentUri][$nsSioc . 'created_at'][0]['type'] = 'literal';
            $activity[$commentUri][$nsSioc . 'created_at'][0]['value'] = $now;
            $activity[$commentUri][$nsSioc . 'created_at'][0]['datatype'] = $nsXsd . 'dateTime';

            $activity[$commentUri][$nsSioc . 'has_creator'][0]['type'] = 'uri';
            $activity[$commentUri][$nsSioc . 'has_creator'][0]['value'] = $actorUri;

            $activity[$commentUri][$nsSioc . 'content'][0]['type'] = 'literal';
            $activity[$commentUri][$nsSioc . 'content'][0]['value'] = $object['content'];

            $activity[$commentUri][$nsPingback . 'to'][0]['type'] = 'uri';
            $activity[$commentUri][$nsPingback . 'to'][0]['value'] = $pingbackServer;

            $activity[$commentUri][$nsDssn . 'activityFeed'][0]['type'] = 'uri';
            $activity[$commentUri][$nsDssn . 'activityFeed'][0]['value'] =
                $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' . urlencode($commentUri) .
                ';' . $feedUri;

            $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' .
                urlencode($commentUri) . ';' . $feedUri;
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
            '             ?p <' . $resourceUri . '> ; ' .
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
            'SELECT DISTINCT ?activity ?date ?verb ?object ?person ' .
            'WHERE { ' .
            '    <' . $resourceUri . '>       a aair:Activity ; ' .
            '             aair:activityActor  ?person ; ' .
            '             atom:published      ?date ; ' .
            '             aair:activityVerb   ?verb ; ' .
            '             aair:activityObject ?object . ' .
            /**' OPTIONAL { ' .
            '    ?activity a aair:Activity ; ' .
            '              ?p <' . $resourceUri . '> ; ' .
            '              aair:activityObject ?object ; ' .
            '              aair:activityActor  ?person ; ' .
            '              atom:published      ?date ; ' .
            '              aair:activityVerb   ?verb ; ' .
            '} ' . **/
            '} ' .
            'ORDER BY DESC(?date)';

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

        if ($type == $nsFoaf . 'PersonalProfileDocument') {
            $isPerson = true;
        }

        $activitiesResult = $model->sparqlQuery($query);

        $activities = array();

        foreach ($activitiesResult as $activity) {
            if ($activity['activity'] === null) {
                $activityUri = $resourceUri;
            } else {
                $activityUri = $activity['activity'];
            }

            $verbUri = $activity['verb'];
            $objectUri = $activity['object'];
            $activity['date'] = self::_issueE24fix($activity['date']);

            $nameHelper = new Xodx_NameHelper($this->_app);
            $type = $resourceController->getType($objectUri);
            $personName = $nameHelper->getName($activity['person']);
            $title = '"' . $personName . '" did "' . $activity['verb'] . '" a "' . $type . '"';

            $activity = array(
                'title' => $title,
                'uri' => $activityUri,
                'author' => $personName,
                'authorUri' => $activity['person'],
                'pubDate' => $activity['date'],
                'verb' => $activity['verb'],
                'object' => $activity['object'],
                'type' => $type,
            );

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
                $activity['objectType'] = $objectResult[0]['type'];
                $activity['objectPubDate'] = self::_issueE24fix($objectResult[0]['date']);
                $activity['objectFeed'] = htmlentities($this->_app->getBaseUri() . '
                	?c=feed&a=getFeed&uri=') . urlencode($objectUri);
                if (!empty($objectResult[0]['content'])) {
                    $activity['objectContent'] = $objectResult[0]['content'];
                }
            }

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
