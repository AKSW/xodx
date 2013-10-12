<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The ConferenceController is responsible for all action concerning Conferences.
 */
class Xodx_ConferenceController extends Xodx_ResourceController
{

    public function testAction()
    {
        echo ("Conference");
    }

    public function PrefixesAction()
    {
        $configHelper = new Xodx_ConfigHelper($this->_app);
        var_dump($configHelper -> loadPropertiesSingle("conference"));
    }

    public function editAction($template)
    {
        $model = $this->_app->getBootstrap()->getResource('Model');
        $configHelper = new Xodx_ConfigHelper($this->_app);
        $allowedSinglePrefixes = $configHelper->loadPropertiesSingle("conference");

        if (count ($_POST) == 0)
        {
            // Show editor with data from database
            $applicationController = $this->_app->getController('Xodx_ApplicationController');
            $userId = $applicationController->getUser();
            $userUri = $this->_app->getBaseUri() . '?c=person&id=' . $userId;
            $stringArray = explode("id=", $userUri);
            $name = $stringArray[1];

            $query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> SELECT ?p ?o WHERE { <" . $userUri . "> a foaf:Person. <" . $userUri . "> ?p ?o }";
            //$query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> SELECT ?p ?o WHERE { ?person a foaf:Person. ?person foaf:person '$userUri'. ?person ?p ?o }";

            $profiles = $model->sparqlQuery( $query);
            $template->allowedSinglePrefixes = $allowedSinglePrefixes;
            $template->allowedMultiplePrefixes = $allowedMultiplePrefixes;
            $template->profile = $profiles;
            $template->addContent('templates/conferenceeditor.phtml');
            //echo ("FOOO");
            return $template;
        }
        else
        {
            //Currently do nothing.
        }
    }
}
