<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * This class extends the Erfurt_Ping to overwrite the _checkTargetExists method
 */
class Xodx_Ping extends Erfurt_Ping
{
    private $_app;

    /**
     * The constructor, setting $_app and calling the parent
     *
     * @param $app the currently valide Saft_Application
     */
    public function __construct ($app) {
        parent::__construct();;
        $this->_app = $app;
    }

    /**
     * Overwrites the Erfurt_Ping _checkTargetExists method to search only in Xodx model for the
     * ping target resource.
     *
     * @param $targetUri the uri of the target which is pinged
     * @return boolean whether the target resource exists or not
     */
    protected function _checkTargetExists ($targetUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $query = 'SELECT ?p ?o' . PHP_EOL;
        $query.= 'WHERE {' . PHP_EOL;
        $query.= '    <' . $targetUri . '> ?p ?o.' . PHP_EOL;
        $query.= '}' . PHP_EOL;

        $result = $model->sparqlQuery($query);

        if (count($result) > 0) {
            $graphUri = $model->getModelIri();
            $this->_targetGraph = $graphUri;
            return true;
        } else {
            return false;
        }
    }
}
