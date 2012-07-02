<?php

require_once 'Tools.php';

class Xodx_ProfileController extends Xodx_Controller
{
    public function listAction()
    {
        $this->app = Application::getInstance();
        $model = $this->app->getBootstrap()->getResource('Model');

        $profiles = $model->sparqlQuery(
            'PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' . 
            'SELECT ?profile ?person ?name ' . 
            'WHERE { ' .
            '   ?profile a foaf:PersonalProfileDocument . ' .
            '   ?profile foaf:primaryTopic ?person . ' .
            '   ?person foaf:name ?name . ' .
            '}'
        );

        $template = Template::getInstance();
        $template->profilelistList = $profiles;
        $template->addContent('templates/profilelist.phtml');
    }

    public function showAction()
    {
        $personUri = $_GET['person'];

        $app = Application::getInstance();
        $model = $app->getBootstrap()->getResource('Model');

        $nsFoaf = 'http://xmlns.com/foaf/0.1/';

        $profileQuery = 'PREFIX foaf: <' . $nsFoaf . '> ' . 
            'SELECT ?depiction ?name ?nick ' . 
            'WHERE { ' .
            '   <' . $personUri . '> a foaf:Person . ' .
            '   OPTIONAL {<' . $personUri . '> foaf:depiction ?depiction .} ' .
            '   OPTIONAL {<' . $personUri . '> foaf:name ?name .} ' .
            '   OPTIONAL {<' . $personUri . '> foaf:nick ?nick .} ' .
            '}';
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
                $modelNew = new Erfurt_Rdf_MemoryModel($newStatements);
                $newStatements = $modelNew->getStatements();
                var_dump($newStatements);

                $profile = array();
                $profile[0] = array(
                    'depiction' => $modelNew->getValue($personUri, $nsFoaf . 'depiction'),
                    'name' => $modelNew->getValue($personUri, $nsFoaf . 'name'),
                    'nick' => $modelNew->getValue($personUri, $nsFoaf . 'nick')
                );
            }
            //$knows = $modelNew->sparqlQuery($contactsQuery);
        } else {
            $knows = $model->sparqlQuery($contactsQuery);
        }

        $template = Template::getInstance();
        $template->profileshowPersonUri = $personUri;
        $template->profileshowDepiction = $profile[0]['depiction'];
        $template->profileshowName = $profile[0]['name'];
        $template->profileshowNick = $profile[0]['nick'];
        $template->profileshowKnows = $knows;
        $template->addContent('templates/profileshow.phtml');
    }

    public function addfriendAction()
    {
        $personUri = $_POST['person'];
        $friendUri = $_POST['friend'];

        $person = Xodx_Person::getPerson($personUri);

        // TODO check rights
        $allowed = true;

        if ($allowed) {
            $person->addFriend($friendUri);
        } else {
            $person->addFriendRequest($friendUri);
        }
    }

    public function getfriendlistAction()
    {
        $personUri = $_GET['person'];

        $person = new Person($personUri);

        // show only public Friends
        $allowed = false;

        $friendList = null;
        if ($allowed) {
            $friendList = $person->getFriends();
        } else {
            $friendList = $person->getPublicFriends();
        }

        // TODO render Template
    }

    public function getprofileAction()
    {
        $personUri = $_GET['person'];

        $person = new Person($personUri);

        // show only public Profile

        $allowed = false;

        $profile = null;
        if ($allowed) {
            $profile = $person->getProfile();
        } else {
            $profile = $person->getPublicProfile();
        }

        // TODO render Template
    }

    public function testAction()
    {
        echo 'ProfileController->test()';
    }
}
