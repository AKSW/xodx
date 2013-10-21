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
