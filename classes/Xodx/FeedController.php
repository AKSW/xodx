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

        $uri = $request->getValue('uri');
        $format = $request->getValue('format');

        if ($uri !== null) {

            $personController = new Xodx_PersonController($this->_app);
            $activities = $personController->getActivities($uri);

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
     * This method reads feed data and extracts the specified activities in order to insert or
     * update them in the model
     */
    public function feedToActivity ($feedData)
    {
        $nsAtom = 'http://www.w3.org/2005/Atom';
        $nsAair = 'http://activitystrea.ms/schema/1.0/';

        $xml = simplexml_load_string($feedData);

        $atom = $xml->children($nsAtom);
        $aair = $xml->children($nsAair);

        if (count($atom) < 1 && count($aair) < 1) {
            throw new Exception('Feed is empty');
        } else {
            $activities = array();
            foreach ($atom->entry as $entry) {
                // getActivitystrea.ms namespace
                $entryAtom = $entry->children($nsAtom);
                $entryAair = $entry->children($nsAair);

                $date = (string) $entryAtom->published;

                $actorNode = $entryAtom->author;
                $actorAtom = $actorNode->children($nsAtom);
                $actorUri = (string) $actorAtom->uri;

                $verbUri = (string) $entryAair->verb;

                $objectNode = $entryAair->object;
                $objectAtom = $objectNode->children($nsAtom);
                $objectUri = (string) $objectAtom->id;

                // TODO create new Activity with the data specified in the entry
                $activities[] = new Activity(null, $actorUri, $verbUri, $objectUri, $date);
            }
        }

        $activityController = new Xodx_ActivityController($this->_app);

        $activityController->addActivities($activities);
    }
}
