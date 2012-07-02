<?php

require_once 'Template.php';

class Xodx_ActivityController
{
    public function AddactivityAction ()
    {
        $this->app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();

        $request = $bootstrap->getResource('request');
        $store = $bootstrap->getResource('store');
        $model = $bootstrap->getResource('model');
        $graphUri = $model->getModelIri();

        $actorUri = $request->getValue('actor', 'post');
        $verbUri = $request->getValue('verb', 'post');
        $actTypeUri = $request->getValue('type', 'post');
        $actContent = $request->getValue('content', 'post');

        $nsXsd = 'http://www.w3.org/2001/XMLSchema#';
        $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsAtom = 'http://purl.org/atom/ns#';
        $nsAair = 'http://xmlns.notu.be/aair#';

        $activityUri = 'http://localhost/~natanael/xodx/activity/' . md5(rand()) . '/';
        $objectUri = 'http://localhost/~natanael/xodx/object/' . md5(rand()) . '/';
        $now = '2012-01-01T00:00:01';

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
            ),
            $objectUri => array(
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
            )
        );
        $store->addMultipleStatements($graphUri, $activity);

        $template = Template::getInstance();
        $template->activity = $activity;
        $template->addContent('templates/newactivity.phtml');
    }
}
