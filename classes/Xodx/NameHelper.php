<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_NameHelper
{
    private $_app;
    private $_names;
    private $_languages;
    private $_properties;

    public function __construct ($app)
    {
        $this->_app = $app;
        $this->_languages = $this->_parseLanguageString($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->_languages[] = '';

        $nsFoaf = 'http://xmlns.com/foaf/0.1/';
        $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsRdfs = 'http://www.w3.org/2000/01/rdf-schema#';
        $nsDc = 'http://purl.org/dc/terms/';
        $this->_properties = array($nsFoaf . 'name', $nsFoaf . 'nick', $nsRdfs . 'label', $nsDc . 'title');
    }

    public function getName ($resourceUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $query = '' .
            'SELECT ?name ' .
            'WHERE { ';
        foreach ($this->_properties as $property) {
            $query .= '   { <' . $resourceUri . '> <' . $property . '> ?name . } ' .
                ' UNION ';
        }

        if (substr($query, -7, 7) == ' UNION ') {
            $query = substr($query, 0, strlen($query) - 7);
        }

        $query .= ' FILTER(';
        foreach ($this->_languages as $language) {
            $query .= ' lang(?name) = "' . $language . '" ||';
        }

        if (substr($query, -2, 2) == '||') {
            $query = substr($query, 0, strlen($query) - 2);
        }
        $query .= ') .';
        $query .= '} ';

        $names = $model->sparqlQuery($query);

        if (isset($names[0]['name'])) {
            return $names[0]['name'];
        } else {
            return false;
        }
    }

    private function _parseLanguageString ($langString)
    {
        $langcode = explode(",", $langString);
        $langPriority = array();

        foreach ($langcode as $lang) {
            $lang = explode(";", $lang);
            $langPriority[] = $lang[0];
        }
        return $langPriority;
    }

}
