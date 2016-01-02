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
     * This is the username of the currently logedin user
     */
    private $_user;


    private function getObsId($user){
        return $user.'observation-'.md5(rand());
    }
    /*
     * function to generate turtle code for the observation part of the datacube
     * TODO finish
    */
    private function buildObservationTTL($id,$dataset,$value){
        return "";
    }
    /**
     * Function to request a turtle representation of all currently available Observations
     * @return string
     */
    public function getStatsAction ($template){
//TODO nach User fragen.
        $user = "?user";

        //setting up user and userUri
//        $applicationController = $this->_app->getController('Xodx_ApplicationController');
//        $userId = $applicationController->getUser();
//        $userUri = $this->_app->getBaseUri() . '?c=user&id=' . $userId;   
//        echo $userId;

        echo "Observation : ".$this->getObsId($user);
        // - followers
        $dataset = "xo:dataset-xoFollower";
        $followers = $this->getFollowers($user);
        echo "Followers = ".$followers."\n";

//TODO        
    // get messages sent

        $dataset = "xo:dataset-xoOUT";

    // get messages received
        $dataset = "xo:dataset-xoIN";

    // get stored triples
        $dataset = "xo:dataset-xoTriples";
        $triples = $this->getTriples();
        echo "Triples = ".$triples."\n";
    // - size in kb? - no
    // - Access Time in microseconds?
//TODO END

//        $template->disableLayout();
//        $template->setRawContent($output);

//        return $template;
    }
    
    private function getFollowers($user){
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        // SPARQL-Query
        $query = 'SELECT COUNT(*) WHERE {'.$user.' <http://purl.org/net/dssn/subscribedTo> ?feed}';

        $resultset = $model->sparqlQuery($query);
        $countResult = $resultset[0]['callret-0'];
        
        return $countResult;

    }

    private function getSentMessages($user){
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        // SPARQL-Query
        //http://xmlns.notu.be/aair#activityVerb	http://xmlns.notu.be/aair#Post
        //http://xmlns.notu.be/aair#activityVerb	http://xmlns.notu.be/aair#Share
        // http://xmlns.notu.be/aair#activityActor ?user
        $query = 'SELECT COUNT(*) WHERE {'.$user.' <http://purl.org/net/dssn/subscribedTo> ?feed}';
        $countResult = $resultset[0]['callret-0'];
        
        return $countResult;

    }
    private function getReceivedMessages(){

    return 0;
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

}
