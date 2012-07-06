<?php

require_once 'Tools.php';

class Xodx_PingbackController extends Xodx_Controller
{
    public function pingAction($template)
    {
        $source = $_POST['source'];
        $target = $_POST['target'];
        $comment = $_POST['comment'];

        // TODO store and interprete ping
        $sourceStatements = Tools::getLinkedDataResource($source);

        if ($sourceStatements !== null) {
            $memModel = new Erfurt_Rdf_MemoryModel($sourceStatements);

            $o = array('type' => 'uri', 'value' => $target);
            $spo = $memModel->getSP($o);

            $template->addDebug(var_export($spo, true));

            if (count($spo) > 0) {
                $predicates = array_keys($spo[$source]);

                $diff = var_export($predicates, true);
            } else {
                $diff = 'invalide';
            }
            $bootstrap = $this->_app->getBootstrap();
            $model = $bootstrap->getResource('model');
            $store = $bootstrap->getResource('store');

            $nsPingback = 'http://purl.org/net/pingback/';
            $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';

            // TODO use some other identification
            $pingUri = 'http://localhost/' . md5(rand()) . '/';

            // TODO user better URIs
            $newProfile = array(
                $target => array(
                    $nsPingback . 'ping' => array(
                        array(
                            'type' => 'uri',
                            'value' => $pingUri
                        )
                    )
                ),
                $pingUri => array(
                    $nsRdf . 'type' => array(
                        array(
                            'type' => 'uri',
                            'value' => $nsPingback . 'Item'
                        )
                    ),
                    $nsPingback . 'source' => array(
                        array(
                            'type' => 'uri',
                            'value' => $source
                        )
                    ),
                    $nsPingback . 'target' => array(
                        array(
                            'type' => 'uri',
                            'value' => $target
                        )
                    ),
                    $nsPingback . 'comment' => array(
                        array(
                            'type' => 'literal',
                            'value' => $comment
                        )
                    ),
                    $nsPingback . 'changeset' => array(
                        array(
                            'type' => 'literal',
                            'value' => $diff
                        )
                    )
                )
            );
            $store->addMultipleStatements($model->getModelIri(), $newProfile);
/*
            $model->addStatement($target, $nsPingback . 'ping', array('type' => 'uri', 'value' => $pingUri));
            $model->addStatement($pingUri, $nsRdf . 'type', array('type' => 'uri', 'value' => $nsPingback . 'Item'));
            $model->addStatement($pingUri, $nsPingback . 'source', array('type' => 'uri', 'value' => $source));
            $model->addStatement($pingUri, $nsPingback . 'target', array('type' => 'uri', 'value' => $target));
            $model->addStatement($pingUri, $nsPingback . 'comment', array('type' => 'literal', 'value' => $comment));
*/
//            $model->addStatement($pingUri, $nsPingback . 'changeset', array('type' => 'literal', 'value' => $diff));
        }

        return $template;
    }

    /**
     * Parts of this method are taken from messages.php of the my-profile project
     */
    public function sendPing($source, $target, $comment = null)
    {
        $targetStatements = Tools::getLinkedDataResource($target);

        // TODO check for X-PINGBACK in header

        $memModel = new Erfurt_Rdf_MemoryModel($targetStatements);

        // get pingback:service or pingback:to from resource
        $pingbackTo = $memModel->getValue($target, 'http://purl.org/net/pingback/to');

        if ($pingbackTo !== null) {
            // send post to service
            if ($comment === null) {
                $comment = 'Do you want to be my friend?';
            }

            $fields = array (
                'source' => $source,
                'target' => $target,
                'comment' => $comment
            );

            // Should really replace curl with an ajax call
            // open connection to pingback service
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $pingbackTo);
            curl_setopt($ch, CURLOPT_POST, count($fields));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //execute post
            $return = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //close connection
            curl_close($ch);
        } else {
            $pingbackService = $memModel->getValue($target, 'http://purl.org/net/pingback/service');
            // TODO support XML-RPC pingbacks
        }
    }
}
