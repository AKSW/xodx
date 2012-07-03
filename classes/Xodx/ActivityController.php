<?php

require_once 'Template.php';

class Xodx_ActivityController
{
    public function addactivityAction ()
    {
        $this->app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();

        $request = $bootstrap->getResource('request');

        $actorUri = $request->getValue('actor', 'post');
        $verbUri = $request->getValue('verb', 'post');
        $actTypeUri = $request->getValue('type', 'post');
        $actContent = $request->getValue('content', 'post');

        $object = array(
            'type' => $actTypeUri,
            'content' => $actContent
        );

        $this->addActivity($actorUri, $verbUri, $object);
    }

    public function addActivity ($actorUri, $verbUri, $object)
    {

        $this->app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();

        $store = $bootstrap->getResource('store');
        $model = $bootstrap->getResource('model');
        $graphUri = $model->getModelIri();

        $nsXsd = 'http://www.w3.org/2001/XMLSchema#';
        $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsAtom = 'http://www.w3.org/2005/Atom/';
        $nsAair = 'http://xmlns.notu.be/aair#';

        $activityUri = 'http://localhost/~natanael/xodx/activity/' . md5(rand()) . '/';
        $now = date('c');

        if ($object['type'] == 'uri') {
            $objectUri = $object['value'];
        } else {
            $objectUri = 'http://localhost/~natanael/xodx/object/' . md5(rand()) . '/';
        }

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
                $nsAair . 'activityObject' => array(
                    array(
                        'type' => 'uri',
                        'value' => $objectUri
                    )
                )
            )
        );

        if ($object['type'] != 'uri') {
            $actTypeUri = $object['type'];
            $actContent = $object['content'];

            $activity[$objectUri] = array(
                $nsRdf . 'type' => array(
                    array(
                        'type' => 'uri',
                        'value' => $actTypeUri
                    )
                ),
                $nsSioc . 'created_at' => array(
                    array(
                        'type' => 'literal',
                        'value' => $now,
                        'datatype' => $nsXsd . 'dateTime'
                    )
                ),
                $nsSioc . 'has_creator' => array(
                    array(
                        'type' => 'uri',
                        'value' => $actorUri
                    )
                ),
                $nsAair . 'content' => array(
                    array(
                        'type' => 'literal',
                        'value' => $actContent
                    )
                )
            );
        }

        $store->addMultipleStatements($graphUri, $activity);

        $pushController = new Xodx_PushController();
        $feedUri = $this->app->getBaseUri() . '?c=feed&amp;a=getFeed&amp;uri=' . urlencode($actorUri);
        $pushController->publish($feedUri);

        $template = Template::getInstance();
        $template->activity = $activity;
        $template->feedUri = $feedUri;
        $template->addContent('templates/newactivity.phtml');
    }
}
