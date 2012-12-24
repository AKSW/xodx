<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_FeedController extends Saft_Controller
{

    /**
     * Returns a Feed in the spezified format (html, rss, atom)
     */
    public function getFeedAction($template, $uri = null, $format = null)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsFoaf = 'http://xmlns.com/foaf/0.1/';
        $nsAair = 'http://xmlns.notu.be/aair#';

        $uri = $request->getValue('uri');
        $format = $request->getValue('format');

        if ($uri !== null) {
            $activityController = $this->_app->getController('Xodx_ActivityController');
            $activities = $activityController->getActivities($uri);

            $pushController = $this->_app->getController('Xodx_PushController');

            $feedUri = $this->_app->getBaseUri() . '?c=feed&a=getFeed&uri=' . urlencode($uri);

            $updated = '0';

            foreach ($activities as $activity) {
                if (0 > strcmp($updated, $activity['pubDate'])) {
                    $updated = $activity['pubDate'];
                }
            }

            $resourceController = $this->_app->getController('Xodx_ResourceController');
            $type = $resourceController->getType($uri);

            $nameHelper = new Xodx_NameHelper($this->_app);

            $template->setLayout('templates/feed.phtml');
            $template->updated = $updated;
            $template->uri = $uri;
            $template->feedUri = $feedUri;
            $template->hub = $pushController->getDefaultHubUrl();
            $isPerson = false;

            if (!($nameHelper->getName($uri))) {
                $name = $uri;
            } else {
                $name = $nameHelper->getName($uri);
            }

            $template->name = $name;
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
        // load feedxml and display activities
        $feed = DSSN_Activity_Feed_Factory::newFromXml($feedData);
        $activityController = $this->_app->getController('Xodx_ActivityController');

        $nsXodx = 'http://xodx.org/ns#';
        $nsXsd = 'http://www.w3.org/2001/XMLSchema#';

        foreach ($feed->getActivities() as $key => $activity) {
            $date = $activity->getPublished();
            //$date = date('c');
            $title = 'FeedImport' . $activity->getTitle();
            $activityUri = $activity->getIri();
            //$title = 'Imported with DSSN-LIB';
            $actor = $activity->getActor();
            $actorUri = $actor->getIri();
            $verb = $activity->getVerb();
            $verbUri = $verb->getIri();
            $object = $activity->getObject();
            $objectUri = $object->getIri();
            //$contextUri = $activity->getTarget();
            $act[] = new Xodx_Activity($activityUri, $actorUri, $verbUri, $objectUri, $date, null);
            var_dump($act);
            $activityController->addActivities($act);
        }
    }

    public  function testFeedAction ()
    {
     $feed = '<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:activity="http://activitystrea.ms/schema/1.0/">
    <title>Activity Feed for Norman Radtke</title>
    <id>http://xodx.local/?c=person&amp;id=splatte</id>
    <link rel="hub" href="http://pubsubhubbub.appspot.com"/>
    <link rel="self" type="application/atom+xml" href="http://xodx.local/?c=feed&amp;a=getFeed&amp;uri=http%3A%2F%2Fxodx.local%2F%3Fc%3Dperson%26id%3Dsplatte"/>
    <updated>2012-10-29T19:57:26+01:00</updated>


    <entry>
      <title>&quot;Norman Radtke&quot; did &quot;http://xmlns.notu.be/aair#Post&quot; a &quot;http://rdfs.org/sioc/ns#Comment&quot;</title>
      <id>' . htmlentities($this->_app->getBaseUri() . '?c=resource&id=' . md5(rand())) . '</id>
      <link href="http://xodx.local/?c=resource&amp;id=e72f0e767aea9e952478ecef5973c8c3" />
      <published>2012-10-29T19:57:26+01:00</published>
      <updated>2012-10-29T19:57:26+01:00</updated>
      <author>
        <name>Norman Radtke</name>
        <uri>http://xodx.local/?c=person&amp;id=splatte</uri>
      </author>
      <activity:verb>http://xmlns.notu.be/aair#Post</activity:verb>
      <activity:object>
        <id>http://xodx.local/?c=resource&amp;id=35da4c92351534bc362fdbc5be62fe27</id>

        <content>Hallo</content>

        <published>2012-10-29T19:57:26+01:00</published>

        <activity:object-type>http://rdfs.org/sioc/ns#Comment</activity:object-type>
              </activity:object>

          </entry>

</feed>';
     $this->feedToActivity($feed);
    }
}
