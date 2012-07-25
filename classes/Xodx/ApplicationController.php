<?php

require_once 'Tools.php';

class Xodx_ApplicationController extends Xodx_Controller
{
    public function newuserAction($template) {
        $nsPingback = 'http://purl.org/net/pingback/';
        $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsFoaf = 'http://xmlns.com/foaf/0.1/';

        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $store = $bootstrap->getResource('store');
        $request = $bootstrap->getResource('request');

        // get URI
        $personUri = $request->getValue('person', 'post');

        if ($personUri !== null) {
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

    public function loginAction($template) {

    }
}
