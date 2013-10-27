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

    public function listAction($template)
    {
        $model = $this->_app->getBootstrap()->getResource('Model');
        $configHelper = new Xodx_ConfigHelper($this->_app);
        $typeUri = $configHelper -> getEditorClass("conference");

        //Get all Conferences
        $profiles = $model->sparqlQuery('SELECT DISTINCT ?event ?p ?o WHERE { ?event a <'. $typeUri .'> . ?event ?p ?o}');

        //Reduce to Label
        foreach ($profiles as $key => $array)
        {
            if (strcmp($array["p"],"http://www.w3.org/2000/01/rdf-schema#label") !=0)
            {
                unset($profiles[$key]);
            }
        }

        //Add Values to $template
        $template->profiles = $profiles;
        $template->addContent('templates/list.phtml');
        return $template;
    }

    public function newAction($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $uid = uniqid();
        $conferenceId = $this->_app->getBaseUri() . '?c=conference&id=' . $uid;

        //Create Conference
        $valueToWrite = 'http://symbolicdata.org/Data/Model#Conference';
        $valueArray = array('type' => 'uri', 'value' => $valueToWrite);
        $keyToWrite = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

        //echo ("<br>Writing: $conferenceId, $keyToWrite, $valueToWrite");
        $model->addStatement($conferenceId, $keyToWrite, $valueArray);

        //Add Temporary Title
        $valueToWrite2 = $uid;
        $valueArray2 = array('type' => 'literal', 'value' => $valueToWrite2);
        $keyToWrite2 = 'http://www.w3.org/2000/01/rdf-schema#label';

        //echo ("<br>Writing: $conferenceId, $keyToWrite2, $valueToWrite2");
        $model->addStatement($conferenceId, $keyToWrite2, $valueArray2);

        $template -> id = $conferenceId;
        $template->addContent('templates/new.phtml');
        return $template;
    }
}
