<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_PingbackController extends Saft_Controller
{

    protected $_targetGraph = null;
    protected $_sourceRdf = null;

    /**
     *
     * Method offers a Semantic Pingback Service for resources of our model
     * @param unknown_type $template
     */
    public function pingAction($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');
        $model = $bootstrap->getResource('model');
        $store = $bootstrap->getResource('store');

        $source = $request->getValue('source', 'post');
        $target = $request->getValue('target', 'post');
        $comment = $request->getValue('comment', 'post');

        $template->addDebug($this->receivePing($source, $target));

        return $template;
    }


    /**
    * receive a ping API
    *
    * @param string $sourceUri The source URI
    * @param string $targetUri The target URI
    *
    * @return integer An integer (fault) code
    */
    public function receivePing($sourceUri, $targetUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request   = $bootstrap->getResource('request');
        $model     = $bootstrap->getResource('model');
        $store     = $bootstrap->getResource('store');
        $logger    = $bootstrap->getResource('logger');
        //$template->addDebug('Method ping was called.');

        // Is $targetUri a valid linked data resource in this namespace?
        if (!$this->_checkTargetExists($targetUri)) {
            //$this->addDebug('0x0021');
            return 'Error: 0x0021, $target is not a valid linked data resource';
        }

        //$config = $this->_privateConfig;
        $foundPingbackTriplesGraph = array();

        // 1. Try to dereference the source URI as RDF/XML, N3, Truples, Turtle
        $foundPingbackTriplesGraph = $this->_getResourceFromWrapper($sourceUri, $targetUri, 'Linkeddata');

        // 2. If nothing was found, try to use as RDFa service
        //if (((boolean) $config->rdfa->enabled) && (count($foundPingbackTriplesGraph) === 0)) {
        if ((count($foundPingbackTriplesGraph) === 0)) {
            $foundPingbackTriplesGraph = $this->_getResourceFromWrapper($sourceUri, $targetUri, 'Rdfa');
        }

        $foundPingbackTriples = array();
        foreach ($foundPingbackTriplesGraph as $s => $predicates) {
            foreach ($predicates as $p => $objects) {
                foreach ($objects as $o) {
                    $foundPingbackTriples[] = array(
                        's' => $s,
                        'p' => $p,
                        'o' => $o['value']
                    );
                }
            }
        }

        // 3. If still nothing was found, try to find a link in the html
        if (count($foundPingbackTriples) === 0) {
            $client = Erfurt_App::getInstance()->getHttpClient(
                $sourceUri,
                array(
                    'maxredirects' => 10,
                    'timeout' => 30
                )
            );

            try {
                $response = $client->request();
            } catch (Exception $e) {
                $logger->error($e->getMessage());
                return 0x0000;
            }
            if ($response->getStatus() === 200) {
                $htmlDoc = new DOMDocument();
                $result = @$htmlDoc->loadHtml($response->getBody());
                $aElements = $htmlDoc->getElementsByTagName('a');

                foreach ($aElements as $aElem) {
                    $a = $aElem->getAttribute('href');
                    if (strtolower($a) === $targetUri) {
                        $foundPingbackTriples[] = array(
                            's' => $sourceUri,
                            'p' => $config->generic_relation,
                            'o' => $targetUri
                        );
                        break;
                    }
                }
            } else {
                $logger->error('receive ping: 0x0010');
                //$versioning->endAction();
                return 'error: 0x0010';
            }
        }

        // 4. If still nothing was found, the sourceUri does not contain any link to targetUri
        if (count($foundPingbackTriples) === 0) {
            // Remove all existing pingback triples from that sourceUri.
            $removed = $this->_deleteInvalidPingbacks($sourceUri, $targetUri);

            if (!$removed) {
                $logger->error('0x0011');
                return 0x0011;
            } else {
                $logger->info('All existing Pingbacks removed.');
                return 'Existing Pingbacks from ' . $sourceUri . ' have been removed.';
            }
        }

        // 6. Iterate through pingback triples candidates and add those, who are not already registered.
        $added = false;
        foreach ($foundPingbackTriples as $triple) {
            //$store = Erfurt_App::getInstance()->getStore();

            if (!$this->_pingbackExists($triple['s'], $triple['p'], $triple['o'])) {
                $this->_addPingback($triple['s'], $triple['p'], $triple['o']);
                $added = true;
            }
        }

        // Remove all existing pingbacks from that source uri, that were not found this time.
        $removed = $this->_deleteInvalidPingbacks($sourceUri, $targetUri, $foundPingbackTriples);

        if (!$added && !$removed) {
            $logger->error('0x0030');
            return 0x0030;
        }

        $logger->info('Pingback registered.');
        var_dump($foundPingbackTriples);
        $activityController = $this->_app->getController('Xodx_ActivityController');

        foreach ($foundPingbackTriples as $triple) {
            $act[] = new Xodx_Activity(null, $triple['s'], $triple['p'], $triple['o']);
            $activityController->addActivities($act);
        }
        var_dump($act);

        return;
    }


    /**
     * Parts of this method are taken from messages.php of the my-profile project
     * Method sends a ping with help of cURL if $target is a resource with given
     * Pingback Server
     *
     * @param string $source
     * @param string $target
     * @param string $comment
     */
    public function sendPing($source, $target, $comment = null)
    {
        $targetStatements = Saft_Tools::getLinkedDataResource($this->_app, $target);

        if ($targetStatements === null) {
            throw new Exception('Can\'t send pingback because target doesn\'t contain RDF data.');
        }

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

    /**
     *
     * Adds a ping resource of rdf:type http://purl.org/net/pingback/Item' to model
     * if incoming ping succeeded
     * @param unknown_type $source
     * @param unknown_type $relation
     * @param unknown_type $target
     */
    protected function _addPingback($source, $relation, $target)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $pingUri = $this->_app->getBaseUri() . '?c=resource&id=' . md5(rand());
        $nsPingback = 'http://purl.org/net/pingback/';
        $nsRdf =      'http://www.w3.org/1999/02/22-rdf-syntax-ns#';

        $model->addStatement($pingUri, $nsRdf . 'type',
            array('type' => 'uri', 'value' => $nsPingback . 'Item'));
        $model->addStatement($pingUri, $nsPingback . 'source',
            array('type' => 'uri', 'value' => $source));
        $model->addStatement($pingUri, $nsPingback . 'target',
            array('type' => 'uri', 'value' => $target));
        $model->addStatement($pingUri, $nsPingback . 'relation',
            array('type' => 'uri', 'value' => $relation));

        return true;
    }

    /**
     *
     * Method deletes stored statements of rdf:type http://purl.org/net/pingback/Item if no
     * triples were found while analysing an in coming ping
     * @param string $sourceUri
     * @param string $targetUri
     * @param array $foundPingbackTriples
     */
    function _deleteInvalidPingbacks($sourceUri, $targetUri, $foundPingbackTriples = array())
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $query  = 'PREFIX pingback: <http://purl.org/net/pingback/>' . PHP_EOL;
        $query .= 'SELECT ?ping ?relation' . PHP_EOL;
        $query .= 'WHERE {' . PHP_EOL;
        $query .= '?ping a pingback:Item;' . PHP_EOL;
        $query .= 'pingback:relation ?relation;' . PHP_EOL;
        $query .= 'pingback:source <' . $sourceUri . '>;' . PHP_EOL;
        $query .= 'pingback:target <' . $targetUri . '>.}';

        $result = $model->sparqlQuery($query);
        $removed = false;
        if (count($result) > 0) {
            foreach ($result as $row) {
                $found = false;
                foreach ($foundPingbackTriples as $triple) {
                    if ($triple['p'] === $row['relation']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $model->deleteMultipleStatements($row['ping'], null, null);

                    $oSpec = array(
                        'value' => $targetUri,
                        'type' => 'uri'
                    );

                    $store->deleteMatchingStatements(
                        $this->_targetGraph,
                        $sourceUri,
                        $row['relation'],
                        $oSpec,
                        array('use_ac' => false)
                    );
                    $removed = true;
                }
            }
        }
        return $removed;
    }


    /**
     *
     * Checks if $targetUri is a resource in our model
     * @param unknown_type $targetUri
     * @return true or false
     */
    protected function _checkTargetExists($targetUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $query = '' .
            'ASK ' .
            'WHERE { ' .
            ' <' . $targetUri . '> ?p  ?o  .} ';

        $exist = $model->sparqlQuery($query);
        if (count($exist) > 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     *
     * Methods checks if $sourceUri is reachable with help of LinkeddataWrapper and if successfull
     * it also checks if statements with $targetUri as object exist.
     * @param string $sourceUri
     * @param string $targetUri
     * @param string $wrapperName
     * @return array $newStatements
     */
    private function _getResourceFromWrapper($sourceUri, $targetUri, $wrapperName = 'Linkeddata')
    {
        $r = new Erfurt_Rdf_Resource($sourceUri);

        // Try to instanciate the requested wrapper
        new Erfurt_Wrapper_Manager();
        $wrapper = Erfurt_Wrapper_Registry::getInstance()->getWrapperInstance($wrapperName);

        $wrapperResult = null;
        $wrapperResult = $wrapper->run($r, null, true);

        $newStatements = null;
        if ($wrapperResult === false) {
            // IMPORT_WRAPPER_NOT_AVAILABLE;
        } else if (is_array($wrapperResult)) {
            $newStatements = $wrapperResult['add'];
            // TODO make sure to only import the specified resource
            $newModel = new Erfurt_Rdf_MemoryModel($newStatements);
            $newStatements = array();
            $object = array('type' => 'uri', 'value' => $targetUri);
            $newStatements = $newModel->getP($sourceUri, $object);
        } else {
            // IMPORT_WRAPPER_ERR;
        }

        return $newStatements;
    }


    /**
     *
     * Method checks with help of SPARQL if a Ping exists
     * @param unknown_type $source
     * @param unknown_type $relation
     * @param unknown_type $target
     */
    protected function _pingbackExists($source, $relation, $target)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $query  = 'PREFIX pingback: <http://purl.org/net/pingback/>' . PHP_EOL;
        $query .= 'ASK' . PHP_EOL;
        $query .= 'WHERE {' . PHP_EOL;
        $query .= '?ping a pingback:Item;' . PHP_EOL;
        $query .= 'pingback:source <' . $source . '>;' . PHP_EOL;
        $query .= 'pingback:target <' . $target . '>.}';

        $exist = $model->sparqlQuery($query);

        if (count($exist) > 0) {
            return true;
        } else {
            return false;
        }
    }



    public function testPingAction () {

        echo $this->receivePing(
            'http://xodx.local/?c=resource&id=1b8c874744236dcdfcaaf08c817aa633',
            'http://xodx.local/?c=resource&id=805e60023b6f23384929d7869bd24185'
        );
        return;

     }
}
