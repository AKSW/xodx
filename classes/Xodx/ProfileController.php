<?php

require_once 'Tools.php';
require_once 'Template.php';

class Xodx_ProfileController extends Xodx_Controller
{
    public function listAction($template)
    {
        $model = $this->_app->getBootstrap()->getResource('Model');

        $profiles = $model->sparqlQuery(
            'PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' . 
            'SELECT ?profile ?person ?name ' . 
            'WHERE { ' .
            '   ?profile a foaf:PersonalProfileDocument . ' .
            '   ?profile foaf:primaryTopic ?person . ' .
            '   ?person foaf:name ?name . ' .
            '}'
        );

        $template->profilelistList = $profiles;
        $template->addContent('templates/profilelist.phtml');

        $template->addDebug(var_export($profiles, true));

        return $template;
    }

    public function showAction($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        // get URI
        $personUri = $request->getValue('person', 'get');

        $nsFoaf = 'http://xmlns.com/foaf/0.1/';

        $profileQuery = 'PREFIX foaf: <' . $nsFoaf . '> ' . 
            'SELECT ?depiction ?name ?nick ' . 
            'WHERE { ' .
            '   <' . $personUri . '> a foaf:Person . ' .
            '   OPTIONAL {<' . $personUri . '> foaf:depiction ?depiction .} ' .
            '   OPTIONAL {<' . $personUri . '> foaf:name ?name .} ' .
            '   OPTIONAL {<' . $personUri . '> foaf:nick ?nick .} ' .
            '}';

        // TODO deal with language tags
        $contactsQuery = 'PREFIX foaf: <' . $nsFoaf . '> ' . 
            'SELECT ?contactUri ?name ' . 
            'WHERE { ' .
            '   <' . $personUri . '> foaf:knows ?contactUri . ' .
            '   OPTIONAL {?contactUri foaf:name ?name .} ' .
            '}';

        $profile = $model->sparqlQuery($profileQuery);

        if (count($profile) < 1) {
            $newStatements = Tools::getLinkedDataResource($personUri);
            if ($newStatements !== null) {
                $template->addDebug('Import Profile with LinkedDate');

                $modelNew = new Erfurt_Rdf_MemoryModel($newStatements);
                $newStatements = $modelNew->getStatements();

                $template->addDebug(var_export($newStatements, true));

                $profile = array();
                $profile[0] = array(
                    'depiction' => $modelNew->getValue($personUri, $nsFoaf . 'depiction'),
                    'name' => $modelNew->getValue($personUri, $nsFoaf . 'name'),
                    'nick' => $modelNew->getValue($personUri, $nsFoaf . 'nick')
                );
            }
            //$knows = $modelNew->sparqlQuery($contactsQuery);


            $knows = array();
        } else {
            $knows = $model->sparqlQuery($contactsQuery);
        }

        $template->profileshowPersonUri = $personUri;
        $template->profileshowDepiction = $profile[0]['depiction'];
        $template->profileshowName = $profile[0]['name'];
        $template->profileshowNick = $profile[0]['nick'];
        $template->profileshowActivities = null;
        $template->profileshowKnows = $knows;
        $template->addContent('templates/profileshow.phtml');

        return $template;
    }

    public function addfriendAction($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        // get URI
        $personUri = $request->getValue('person', 'post');
        $friendUri = $request->getValue('friend', 'post');

        $personController = new Xodx_PersonController($this->_app);

        // TODO check rights
        $allowed = true;

        if ($allowed) {
            $personController->addFriend($personUri, $friendUri);
        } else {
            $personController->addFriendRequest($personUri, $friendUri);
        }

        return $template;
    }

    public function getfriendlistAction($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        // get URI
        $personUri = $request->getValue('person', 'get');

        $person = new Person($personUri);

        // show only public Friends
        $allowed = false;

        $friendList = null;
        if ($allowed) {
            $friendList = $person->getFriends();
        } else {
            $friendList = $person->getPublicFriends();
        }

        return $template;
    }

    public function getprofileAction($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        // get URI
        $personUri = $request->getValue('person', 'get');

        $person = new Person($personUri);

        // show only public Profile

        $allowed = false;

        $profile = null;
        if ($allowed) {
            $profile = $person->getProfile();
        } else {
            $profile = $person->getPublicProfile();
        }

        return $template;
    }
}
