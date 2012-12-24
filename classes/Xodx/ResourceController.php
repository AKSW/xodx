<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_ResourceController extends Saft_Controller
{
    /**
     *
     * indexAction decides to show a html or a serialized view of a resource if no action is given
     * @param unknown_type $template
     */
    public function indexAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');
        $objectId = $request->getValue('id', 'get');
        $controller = $request->getValue('c', 'get');
        //$header = $request->getHeader();
        //$accept = explode(',',$header['Accept']);
        header('HTTP/1.1 302 Found');

        // Array of Accept Header values
        $otherType = array(
            'text/html' => 'html',
            'image/jpg' => 'imagejpg'
        );

        // Array of Accept Header values (keys) for serialised view
        $rdfType = array(
            'application/sparql-results+xml' => 'rdfxml',
            'application/json' => 'rdfjson',
            'application/sparql-results+json' => 'rdfjson',
            'application/rdf+xml' => 'rdfxml',
            'text/rdf+n3' => 'rdfn3',
            'application/x-turtle' => 'turtle',
            'application/rdf+xml' => 'rdfxml',
            'text/turtle' => 'turtle',
            'rdf/turtle' => 'turtle',
            'rdf/json' => 'rdfjson'
        );

        $supportedTypes = array_merge($rdfType, $otherType);
        $match = Saft_Tools::matchMimetypeFromRequest($request, array_keys($supportedTypes));
        $template->disableLayout();
        $template->setRawContent('');

        if ($match != '') {
            if (array_key_exists($match, $rdfType)) {
                header('Location: ' . $this->_app->getBaseUri() . '?c=' . $controller .
                '&a=rdf&id=' . $objectId . '&mime=' . urlencode($match));
                return $template;
            } else if (strpos($match, 'image') !== false) {
                header('Location: ' . $this->_app->getBaseUri() . '?c=' . $controller .
                '&a=img&id=' . $objectId);
                return $template;
            } else if (strpos($match, 'text') !== false) {
                // TODO change name of showAction in ProfileController so it won't be overwritten
                header('Location: ' . $this->_app->getBaseUri() . '?c=' . $controller .
                '&a=show&id=' . $objectId);
                return $template;
            }
        }

        // default
        //header('Location: ' . $this->_app->getBaseUri() . '?c=resource&a=show&id=' . $objectId);

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
        //$personController = new Xodx_PersonController($this->_app);
        //$activities = $personController->getActivities($personUri);
        //$news = $personController->getNotifications($personUri);

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
        $mime = $request->getValue('mime', 'get');
        $controller = $request->getValue('c', 'get');
        $objectUri = $this->_app->getBaseUri() . '?c=' . $controller . '&id=' . $objectId;

        if ($mime === null) {
            throw new Exception('Please specify a mime type');
        }

        //$format = Erfurt_Syntax_RdfSerializer::normalizeFormat($format);

        $modelUri = $model->getModelIri();
        $format = Erfurt_Syntax_RdfSerializer::normalizeFormat($mime);
        $serializer = Erfurt_Syntax_RdfSerializer::rdfSerializerWithFormat($format);
        $rdfData = $serializer->serializeResourceToString($objectUri, $modelUri, false, true, array());
        header('Content-type: ' . $mime);

        $template->disableLayout();
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

        $template->disableLayout();
        $template->setRawContent('');

        $mimeType = $properties[0]['mime'];

        $mediaController->getImage($objectId, $mimeType);
        //$template->addContent('templates/resourceshow.phtml');

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
}
