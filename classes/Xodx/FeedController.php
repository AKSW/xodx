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


    if ($request->hasValue('uri')) {
        $uri = $request->getValue('uri');
    }

    if ($request->hasValue('format')) {
        $format = $request->getValue('format');
    }

    $activities = $model->sparqlQuery(
        'PREFIX aair: <http://xmlns.notu.be/aair#> ' . 
        'SELECT ?activity ' . 
        'WHERE { ' .
        '   ?activity a aair:Activity ; ' .
        '             aair:activityActor <' . $uri . '> . ' .
        '}'
    );

    $template = Template::getInstance();
    $template->setLayout('templates/feed.phtml');
    $template->uri = $uri;
    $template->name = $uri;
    $template->activities = $activities;
//    $template->render();
  }

}
