<?php

require_once 'Template.php';

class Xodx_FeedController extends Xodx_Controller
{

    /**
     * Returns a Feed in the spezified format (html, rss, atom)
     */
    public function getFeedAction($template, $uri = null, $format = null)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        $nsAair = 'http://xmlns.notu.be/aair#';

        $uri = $request->getValue('uri');
        $format = $request->getValue('format');

        if ($uri !== null) {
            $query = '' .
                'PREFIX atom: <http://www.w3.org/2005/Atom/> ' .
                'PREFIX aair: <http://xmlns.notu.be/aair#> ' .
                'SELECT ?activity ?date ?verb ?object ' .
                'WHERE { ' .
                '   ?activity a                   aair:Activity ; ' .
                '             aair:activityActor  <' . $uri . '> ; ' .
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
                    'title' => '"' . $uri . '" did "' . $activity['verb'] . '".',
                    'uri' => $activityUri,
                    'author' => 'Natanael',
                    'authorUri' => $uri,
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

            $pushController = new Xodx_PushController($this->_app);

            $template->setLayout('templates/feed.phtml');
            $template->uri = $uri;
            $template->hub = $pushController->getDefaultHubUrl();
            $template->name = $uri;
            $template->activities = $activities;
        } else {
            // No URI given
        }

        return $template;
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
