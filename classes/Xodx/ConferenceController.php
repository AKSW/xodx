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

    public function testAction($template)
    {
        $template->addContent('templates/test.phtml');
        return $template;
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
            //Show editor with data from database
            $applicationController = $this->_app->getController('Xodx_ApplicationController');
            $userId = $applicationController->getUser();
            //$userUri = $this->_app->getBaseUri() . '?c=person&id=' . $userId;
            $stringArray = explode("id=", $userUri);
            $name = $stringArray[1];
            $eventUri = "http://symbolicdata.org/Data/Conference/s2am-2013";

            $typeUri = $configHelper -> getEditorClass("conference");

            $query = "PREFIX ns2: <http://symbolicdata.org/Data/Model#> SELECT ?p ?o WHERE { <" . $eventUri . "> a <" . $typeUri . "> . <" . $eventUri . "> ?p ?o }";
            //$query = "PREFIX  ical: <http://www.w3.org/2002/12/cal/ical#> SELECT ?event ?p ?o WHERE { ?event a ical:Event . ?event ?p ?o }";

            $profiles = $model->sparqlQuery( $query);
            $template->allowedSinglePrefixes = $allowedSinglePrefixes;
            $template->profile = $profiles;
            $template->addContent('templates/edit.phtml');
            //echo ("FOOO");
            return $template;
        }
        else
        {
            //Currently do nothing.
            echo ("Nope.");
        }
    }
}
