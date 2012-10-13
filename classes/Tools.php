<?php

class Tools
{
    /**
     * @warning This function sends a web request and might take a long time
     * @hint You should run this function asynchrounusly or independent of your UI
     */
    public static function getLinkedDataResource($uri)
    {
        $app = Application::getInstance();
        $model = $app->getBootstrap()->getResource('Model');
        $modelUri = $model->getModelIri();

        $r = new Erfurt_Rdf_Resource($uri);

        // Try to instanciate the requested wrapper
        //$wrapper = new LinkeddataWrapper();
        $wrapperName = 'Linkeddata';
        $wrapper = Erfurt_Wrapper_Registry::getInstance()->getWrapperInstance($wrapperName);

        $wrapperResult = null;
        $wrapperResult = $wrapper->run($r, $modelUri, true);

        $newStatements = null;
        if ($wrapperResult === false) {
            // IMPORT_WRAPPER_NOT_AVAILABLE;
        } else if (is_array($wrapperResult)) {
            $newStatements = $wrapperResult['add'];
            // TODO make sure to only import the specified resource
            $newModel = new Erfurt_Rdf_MemoryModel($newStatements);
            $newStatements = array();
            $newStatements[$uri] = $newModel->getPO($uri);
        } else {
            // IMPORT_WRAPPER_ERR;
        }

        return $newStatements;
    }

    /**
     * Matches an array of mime types against the Accept header in a request.
     *
     * @param Xodx_Request $request the request
     * @param array $supportedMimetypes The mime types to match against
     * @return string
     */
    public static function matchMimetypeFromRequest (
        Xodx_Request $request,
        array $supportedMimetypes
    )
    {
        // get accept header
        $header = $request->getHeader();
        $acceptHeader = strtolower($header['Accept']);

        require_once 'Mimeparse.php';
        try {
            // suppress warnings because we will catch exceptions
            $match = @Mimeparse::best_match($supportedMimetypes, $acceptHeader);
        } catch (Exception $e) {
            $match = '';
        }

        return $match;
    }

    /**
     * Looks up if a natural word instead of $uri exists and returns this
     *
     * TODO: move this function to NameHelper and user predicate labels
     * @param unknown_type $uri a uri
     */
    public static function getSpokenWord($uri)
    {

        $nsXsd = 'http://www.w3.org/2001/XMLSchema#';
        $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsAtom = 'http://www.w3.org/2005/Atom/';
        $nsAair = 'http://xmlns.notu.be/aair#';
        $nsXodx = 'http://xodx.org/ns#';
        $nsFoaf = 'http://xmlns.com/foaf/0.1/';
        $nsOv = 'http://open.vocab.org/docs/';
        $nsPingback = 'http://purl.org/net/pingback/';
        $nsDssn = 'http://purl.org/net/dssn/';

        $words = array(
            $nsAair . 'makeFriend' => 'friended',
            $nsAair . 'Post' => 'posted',
            $nsAair . 'Share' => 'shared',
            $nsSioc . 'Comment' => 'comment',
            $nsFoaf . 'Image' => 'image'
        );

        if (isset($words[$uri])) {
            return $words[$uri];
        } else {
            return $uri;
        }
    }
}
