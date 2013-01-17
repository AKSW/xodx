<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * @deprecated we don't need this controller, beacause the person things should be done in the
 * Xodx_PersonController and the list action can move somewhere else.
 */
class Xodx_ProfileController extends Xodx_ResourceController
{
    public function listAction($template)
    {
        $model = $this->_app->getBootstrap()->getResource('Model');

        $profiles = $model->sparqlQuery(
            'PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' .
            'SELECT DISTINCT ?person ' .
            'WHERE { ' .
            '   ?person a foaf:Person . ' .
            '}'
        );

        $persons = array();

        $nameHelper = new Xodx_NameHelper($this->_app);

        foreach ($profiles as $profile) {
            $persons[] = array(
                'person' => $profile['person'],
                'name' => $nameHelper->getName($profile['person'])
            );
        }

        $template->profilelistList = $persons;
        $template->addContent('templates/profilelist.phtml');

        $template->addDebug(var_export($profiles, true));

        return $template;
    }

    public function addfriendAction($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        // get URI
        $personUri = $request->getValue('person', 'post');
        $friendUri = $request->getValue('friend', 'post');

        $personController = $this->_app->getController('Xodx_PersonController');

        $personController->addFriend($personUri, $friendUri);

        return $template;
    }
}
