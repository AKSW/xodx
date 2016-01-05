<?php
/**
 * This file is part of the {@link https://github.com/DSSN-Practical DSSN-Practical} student project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */


/**
 * This class provides several functions to generate a node statistic for xodx
 * 
 */
class Xodx_StatController extends Saft_Controller
{

    /**
     * Function to request a turtle representation of all currently available Observations
     * @return string
     */
    public function getStatsAction ($template, $user = null, $time = null){

        //setting up time and user
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');    
        $time = $request->getValue('time');
        $user = urldecode($request->getValue('user'));
 
        //initiate observationString
        $observationString = "";

        // - followers
        $dataset = "xo:dataset-xoFollower";
        $measureProperty = "xo:follower";
        $value = $this->getFollowers($user);
        $observationString .= $this->buildObservation(
                                            $this->getObsId($time),
                                            $dataset,
                                            $user,
                                            $measureProperty,
                                            $time,
                                            $value); 
        // get messages sent
        $dataset = "xo:dataset-xoOUT";
        $measureProperty = "xo:outgoingMessages";
        $value = $this->getSentMessages($user);
        $observationString .= $this->buildObservation(
                                            $this->getObsId($time),
                                            $dataset,
                                            $user,
                                            $measureProperty,
                                            $time,
                                            $value);
        // get messages received
        $dataset = "xo:dataset-xoIN";
        $measureProperty = "xo:receivedMessages";
        $value = $this->getReceivedMessages($user);
        $observationString .= $this->buildObservation(
                                            $this->getObsId($time),
                                            $dataset,
                                            $user,
                                            $measureProperty,
                                            $time,
                                            $value);
        // get stored triples
        $dataset = "xo:dataset-xoTriples";
        $measureProperty = "xo:triples";
        $value = $this->getTriples();
        $observationString .= $this->buildObservation(
                                            $this->getObsId($time),
                                            $dataset,
                                            $user,
                                            $measureProperty,
                                            $time,
                                            $value);
        // get Access Time in microseconds
        $dataset = "xo:dataset-xoAccess";
        $measureProperty = "xo:Access";
        $value = $this->getAccessTime($user);
        $observationString .= $this->buildObservation(
                                            $this->getObsId($time),
                                            $dataset,
                                            $user,
                                            $measureProperty,
                                            $time,
                                            $value);

        $template->disableLayout();
        $template->setRawContent($observationString);

        return $template;
    }
    
    private function getFollowers($user){
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        // SPARQL-Query
        // maybe use <http://purl.org/net/dssn/subscribedTo> ?
        $query = 'SELECT COUNT(*) WHERE {<'.$user.'> <http://xmlns.com/foaf/0.1/knows> ?x}';

        $resultset = $model->sparqlQuery($query);
        $countResult = $resultset[0]['callret-0'];
        
        return ($countResult?$countResult:0);

    }

    private function getSentMessages($user){
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        // SPARQL-Query
        $query = 'SELECT COUNT(*) WHERE {';
        $query .= '         ?x a <http://xmlns.notu.be/aair#Activity>.';
        $query .= '         ?x <http://xmlns.notu.be/aair#activityVerb> <http://xmlns.notu.be/aair#Post>.';
        $query .= '         ?x <http://xmlns.notu.be/aair#activityActor> <'.$user.'>';
        $query .= '}';

        $resultset = $model->sparqlQuery($query);
        $countResult = $resultset[0]['callret-0'];
        
        return ($countResult?$countResult:0);

    }
    private function getReceivedMessages($user){

        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        // SPARQL-Query
        $query = 'SELECT COUNT(*) WHERE {';
        $query .= '         ?x a <http://xmlns.notu.be/aair#Activity>.';
        $query .= '         ?x <http://xmlns.notu.be/aair#activityVerb> <http://xmlns.notu.be/aair#Post>.';
        $query .= '         ?x <http://xmlns.notu.be/aair#activityActor> ?u.';
        $query .= '       <'.$user.'> <http://xmlns.com/foaf/0.1/knows> ?u';
        $query .= '}';

        $resultset = $model->sparqlQuery($query);
        $countResult = $resultset[0]['callret-0'];
        
        return ($countResult?$countResult:0);
    }
    private function getTriples(){
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');        
        $query = 'SELECT COUNT(*) WHERE {?s ?p ?o}';

        $resultset = $model->sparqlQuery($query);
        $triples = $resultset[0]['callret-0'];
        return $triples;
    }


    public function readStoreAction($template){
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');        
        $query = 'SELECT * WHERE {?s ?p ?o}';
        $resultset = $model->sparqlQuery($query);

        echo "<table><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
        foreach($resultset as $line){
            echo "<tr>";
            foreach($line as $value){
                echo "<td>{$value}</td>";
            }
            echo "<tr>";
        }
        echo "</table>";
    }

    private function getObsId($x){
        return 'xo:observation-'.$x.'-'.md5(rand());
    }

    private function getAccessTime($user){
        $starttime = microtime(true);

        $client = new Zend_Http_Client();
        $client->setUri($this->_app->getBaseUri());
        $client->setParameterGet(array(
            'c'  => 'feed',
            'a' => 'getFeed',
            'uri' => $user,
        ));
        $response = $client->request();
        $feed = $response->getBody();

        $endtime = microtime(true);
        return $endtime - $starttime;
    }

    /**
     * function to generate turtle code for the observation part of the datacube
     */
    private function buildObservation($id,$dataset,$user,$measureProperty,$time,$value){
        $observationString = "";
        $observationString .= $id." a qb:Observation;".PHP_EOL;
        $observationString .= "qb:dataSet ".$dataset.";".PHP_EOL;
        $observationString .= "xo:refAgent <".$user.">;".PHP_EOL;
        $observationString .= "xo:refTime ".$time.";".PHP_EOL;
        $observationString .= $measureProperty." ".$value.";".PHP_EOL;
        $observationString .= ".".PHP_EOL;
        return $observationString;
    }

}
