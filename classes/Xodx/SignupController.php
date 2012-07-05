<?php

require_once 'Tools.php';
require_once 'Template.php';

class Xodx_SignupController
{
    public function newuserAction($template) {
        $nsPingback = 'http://purl.org/net/pingback/';
        $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsFoaf = 'http://xmlns.com/foaf/0.1/';

        if (isset($_POST['person'])) {
            // get URI
            $personUri = $_POST['person'];
            $app = Application::getInstance();
            $store = $app->getBootstrap()->getResource('Store');
            $model = $app->getBootstrap()->getResource('Model');
            $graphUri = $model->getModelIri();

            $newStatements = Tools::getLinkedDataResource($personUri);
            $store->addMultipleStatements($graphUri, $newStatements);

            $template->addDebug(var_export($newStatements, true));

            $newProfile = array(
                'http://localhost/~natanael/xodx/' . md5(rand()) . '/' => array(
                    $nsRdf . 'type' => array(
                        array(
                            'type' => 'uri',
                            'value' => $nsFoaf . 'PersonalProfileDocument'
                        )
                    ),
                    $nsFoaf . 'primaryTopic' => array(
                        array(
                            'type' => 'uri',
                            'value' => $personUri
                        )
                    ),
                )
            );
            $store->addMultipleStatements($graphUri, $newProfile);

        } else {
            $template->addContent('templates/newuser.phtml');
        }

        return $template;
    }
}
