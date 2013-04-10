<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_PingbackController extends Saft_Controller
{

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

        $ping = new Xodx_Ping($this->_app, array('write_data' => false));

        if ($ping->receive($source, $target)) {
            $template->addDebug('proccessing ping data …');

            $foundPingbackTriples = $ping->getReceivedData();

            $notificationFactory = new Xodx_NotificationFactory($this->_app);
            $personController = $this->_app->getController('Xodx_PersonController');

            // TODO get user which is in charge of the pinged resource
            $userUri = $personController->getUserForPerson($target)->getUri();
            $template->addDebug('ping useruri: ' . $userUri);

            foreach ($foundPingbackTriples['add'] as $triple) {
                $text = 'Ping received: s="' . $triple['s'] . '", p="' . $triple['p'] . '", o="' . $triple['o'] . '"';
                $notificationFactory->forUser($userUri, $text);
            }
        }

        $template->addDebug($ping->getReturnValue());
        return $template;
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
        $nsPing = 'http://purl.org/net/pingback/';

        $bootstrap = $this->_app->getBootstrap();
        $logger    = $bootstrap->getResource('logger');

        $linkeddataHelper = $this->_app->getHelper('Saft_Helper_LinkeddataHelper');
        $targetStatements = $linkeddataHelper->getResource($target);

        if ($targetStatements !== null) {
            $memModel = new Erfurt_Rdf_MemoryModel($targetStatements);

            // get pingback:service or pingback:to from resource
            $pingbackTo = $memModel->getValue($target, $nsPing . 'to');

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
                $logger->info('ping send: Ping to ' . $target . ' @ ' . $pingbackTo . ' from ' . $source . ' successfull.');
                //close connection
                curl_close($ch);
                return true;
            } else {
                $pingbackService = $memModel->getValue($target, $nsPing . 'service');
                // TODO support XML-RPC pingbacks
            }
        } else {
            $logger->info('ping send: Ping to ' . $target . ' not possible. No pingback server found.');
            return null;
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

    public function testPingAction ($template) {

        echo $this->receivePing(
            'http://xodx.local/?c=resource&id=1b8c874744236dcdfcaaf08c817aa633',
            'http://xodx.local/?c=resource&id=805e60023b6f23384929d7869bd24185'
        );
        return $template;

     }
}
