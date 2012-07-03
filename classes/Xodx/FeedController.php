<?php

require_once 'Template.php';

class Xodx_FeedController extends Xodx_Controller
{

    /**
     * Returns a Feed in the spezified format (html, rss, atom)
     */
    public function getFeedAction($uri = null, $format = null)
    {
        $this->app = Application::getInstance();
        $bootstrap = $this->app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');


        $uri = $request->getValue('uri');
        $format = $request->getValue('format');

        if ($uri !== null) {
            $activitiesResult = $model->sparqlQuery(
                'PREFIX atom: <http://purl.org/atom/ns#> ' .
                'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
                'SELECT ?activity ?date ?verb ?object ' .
                'WHERE { ' .
                '   ?activity a                   aair:Activity ; ' .
                '             aair:activityActor  <' . $uri . '> ; ' .
                '             atom:published      ?date ; ' .
                '             aair:activityVerb   ?verb ; ' .
                '             aair:activityObject ?object . ' .
                '}'
            );

            $activities = array();

            foreach ($activitiesResult as $activity) {
                $activityUri = $activity['activity'];
                $objectUri = $activity['object'];

                $objectResult = $model->sparqlQuery(
                    'PREFIX atom: <http://purl.org/atom/ns#> ' .
                    'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
                    'PREFIX sioc: <http://rdfs.org/sioc/ns#> ' .
                    'SELECT ?type ?content ?date ' .
                    'WHERE { ' .
                    '   <' . $objectUri . '> a ?type ; ' .
                    '        sioc:created_at ?date ; ' .
                    '        aair:content ?content . ' .
                    '} '
                );

                $activity = array(
                    'title' => '"' . $uri . '" did "' . $activity['verb'] . '" a new "' . $objectResult[0]['type'] . '".',
                    'uri' => $activityUri,
                    'author' => 'Natanael',
                    'authorUri' => $uri,
                    'pubDate' => $activity['date'],
                    'verb' => $activity['verb'],
                    'object' => $activity['object'],
                    'objectType' => $objectResult[0]['type'],
                    'objectPubDate' => $objectResult[0]['date'],
                    'objectContent' => $objectResult[0]['content'],
                );

                $activities[] = $activity;
            }

            $pushController = new Xodx_PushController();

            $template = Template::getInstance();
            $template->setLayout('templates/feed.phtml');
            $template->uri = $uri;
            $template->hub = $pushController->getDefaultHubUrl();
            $template->name = $uri;
            $template->activities = $activities;
            //    $template->render();
        } else {
            // No URI given
        }
    }

}
