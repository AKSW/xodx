<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_ResourceController extends Saft_Controller
{
    // Array of Accept Header values (keys) for serialised view
    // TODO: get this list from Erfurt
    public $rdfTypes = array(
        'application/sparql-results+xml',
        'application/json',
        'rdf/json',
        'application/sparql-results+json',
        'application/x-turtle',
        'application/rdf+xml',
        'text/turtle',
        'text/rdf+n3',
        'text/n3',
        'rdf/turtle',
    );

    /**
     *
     * indexAction decides to show a html or a serialized view of a resource if no action is given
     * @param unknown_type $template
     */
    public function indexAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        // Array of Accept Header values
        $otherType = array(
            '*/*' => 'show'
        );

        $supportedTypes = array_merge($this->rdfTypes, array_keys($otherType));

        $mimetypeHelper = $this->_app->getHelper('Saft_Helper_MimetypeHelper');
        $match = $mimetypeHelper->matchFromRequest($request, $supportedTypes);

        $template->disableLayout();
        $template->setRawContent('');

        $location = new Saft_Url($request);

        if (in_array($match, $this->rdfTypes)) {
            $location->setParameter('a', 'rdf');
            $template->redirect($location);
        } else if ($supportedTypes[$match] = 'show') {
            $location->setParameter('a', 'show');
            $template->redirect($location);
        } else {
            $template->setResponseCode(404);
        }

        return $template;
    }

    /**
     *
     * Method returns all tuples of a resource to html template
     * @param $template
     */
    public function showAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        $objectId = $request->getValue('id', 'get');
        $controller = $request->getValue('c', 'get');
        $objectUri = $this->_app->getBaseUri() . '?c=' . $controller . '&id=' . $objectId;

        $query = '' .
            'SELECT ?p ?o ' .
            'WHERE { ' .
            '   <' . $objectUri . '> ?p ?o . ' .
            '} ';
        $properties = $model->sparqlQuery($query);

        $activityController = $this->_app->getController('Xodx_ActivityController');
        $activities = $activityController->getActivities($objectUri);

        $template->addContent('templates/resourceshow.phtml');
        $template->properties = $properties;
        $template->activities = $activities;

        // TODO getActivity with objectURI from Xodx_ActivityController

        return $template;
    }

    /**
     *
     * rdfAction returns a serialized view of a resource according to content type
     * (default is turtle)
     * @param unknown_type $template
     */
    public function rdfAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        $objectId = $request->getValue('id', 'get');
        $controller = $request->getValue('c', 'get');
        $objectUri = $this->_app->getBaseUri() . '?c=' . $controller . '&id=' . $objectId;

        $mimetypeHelper = $this->_app->getHelper('Saft_Helper_MimetypeHelper');
        $mime = $mimetypeHelper->matchFromRequest($request, $this->rdfTypes);

        $modelUri = $model->getModelIri();
        $format = Erfurt_Syntax_RdfSerializer::normalizeFormat($mime);
        $serializer = Erfurt_Syntax_RdfSerializer::rdfSerializerWithFormat($format);
        $rdfData = $serializer->serializeResourceToString($objectUri, $modelUri);
        $template->setHeader('Content-type', $mime);

        $template->setRawContent($rdfData);

        return $template;
    }

    /**
     *
     * rdfAction returns a serialized view of a resource according to content type
     * (default is turtle)
     * @param unknown_type $template
     */
    public function imgAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        $objectId = $request->getValue('id', 'get');
        $controller = $request->getValue('c', 'get');
        $objectUri = $this->_app->getBaseUri() . '?c=' . $controller . '&id=' . $objectId;

        $query = '' .
            'PREFIX foaf: <http://xmlns.com/foaf/0.1/> ' .
            'PREFIX ov: <http://open.vocab.org/docs/> ' .
            'SELECT ?mime ' .
            'WHERE { ' .
            '   <' . $objectUri . '> a foaf:Image ; ' .
            '   ov:hasContentType    ?mime . ' .
            '} ';
        $properties = $model->sparqlQuery($query);
        $mediaController = $this->_app->getController('Xodx_MediaController');

        $mimeType = $properties[0]['mime'];

        $filePath = $mediaController->getImage($objectId, $mimeType);
        $template->setHeader('Cache-Control', 'min-fresh = 120');
        $template->setHeader('Expires', gmdate('D, d M Y H:i:s', time()+120) . ' GMT');
        $template->setHeader('Pragma', '');
        $template->setRawFile($filePath);

        return $template;
    }

    /**
     *
     * get the type of a ressource
     * @param $resourceUri a URI of a ressource
     */
    public function getType ($resourceUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        //TODO return array of all found types

        $query = '' .
            'SELECT ?type ' .
            'WHERE { ' .
            ' <' . $resourceUri . '> a  ?type  .} ';

        $type = $model->sparqlQuery($query);
        //TODO get linked data if resource is not in out namespace
        if (isset($type[0]['type'])) {
            return $type[0]['type'];
        } else {
            return false;
        }
    }


    /**
     *
     * methods looks up a ressource to get the Uri of the activity feed
     * and returns it if succesfull
     * @param $resourceUri - the URI of the ressource to be looked up
     */
    public function getActivityFeedUri($resourceUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $logger = $bootstrap->getResource('logger');

        $linkeddataHelper = $this->_app->getHelper('Saft_Helper_LinkeddataHelper');
        $statements = $linkeddataHelper->getResource($resourceUri);

        if ($statements !== null && !empty($statements)) {
            $memModel = new Erfurt_Rdf_MemoryModel($statements);

            // get dssn:activityFeed for resource
            $feedUri = $memModel->getValue($resourceUri, 'http://purl.org/net/dssn/activityFeed');
            return $feedUri;
        } else {
            $logger->debug('ResourceController/getActivityFeedUri: no statements found for resource: "' . $resourceUri . '"');
            return null;
        }
    }

    /**
     * method imports a resource into the own model
     * @param string $resourceUri - URI of resource that should be imported
     */
    public function importResource($resourceUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $logger = $bootstrap->getResource('logger');

        $linkeddataHelper = $this->_app->getHelper('Saft_Helper_LinkeddataHelper');
        $statements = $linkeddataHelper->getResource($resourceUri);

        if ($statements !== null) {
            $memModel = new Erfurt_Rdf_MemoryModel($statements);
            $importStatements = $memModel->getStatements($resourceUri);

            $model = $bootstrap->getResource('model');
            $model->addMultipleStatements($importStatements);
            $logger->info('Import of resource: ' . $resourceUri . ' successfull');
        } else {
            $logger->error('Import of resource: ' . $resourceUri . ' failed');
        }
        return;
    }

    /**
     *
     * Testing of importResource
     */
    public function testImportResourceAction($template)
    {
        //$template->disableLayout();
        echo $this->importResource('http://dbpedia.org/resource/Hamburger_SV');
        return $template;
    }
}
