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
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $configHelper = new Xodx_ConfigHelper($this->_app);
        $allowedSinglePrefixes = $configHelper->loadPropertiesSingle("conference");
        $request = $bootstrap->getResource('request');

        if (count ($_POST) == 0)
        {
            $objectId = $request->getValue('id', 'get');
            //Show editor with data from database
            $applicationController = $this->_app->getController('Xodx_ApplicationController');
            $userId = $applicationController->getUser();
            //$userUri = $this->_app->getBaseUri() . '?c=person&id=' . $userId;
            $stringArray = explode("id=", $userUri);
            $name = $stringArray[1];
            if (!is_null($objectId))
            {
                $eventUri = $objectId;
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
                //this will create a new conference
                return $template;
            }
        }
        else
        {
            //Currently do nothing.
            echo ("Nope.");
        }
    }

    public function listAction($template)
    {
        $model = $this->_app->getBootstrap()->getResource('Model');
        $configHelper = new Xodx_ConfigHelper($this->_app);
        $typeUri = $configHelper -> getEditorClass("conference");
        $profiles = $model->sparqlQuery('SELECT DISTINCT ?event ?p ?o WHERE { ?event a <'. $typeUri .'> . ?event ?p ?o}');

        foreach ($profiles as $key => $array)
        {
            //var_dump($array);
            //echo ($array["p"]);
            //echo ("<br>");
            if ( strcmp($array["p"],"http://www.w3.org/2000/01/rdf-schema#label") !=0)
            {
                unset($profiles[$key]);
            }
        }

        //var_dump($profiles);
        $template->profiles = $profiles;
        $template->addContent('templates/list.phtml');
        return $template;
    }
}
