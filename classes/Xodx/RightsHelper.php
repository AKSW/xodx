<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The RightsHelper is responsible for providing Right Management.
 */
class Xodx_RightsHelper extends Saft_Helper
{
    /**
     * Returns true if the User has the right to do a specified action.
     */
    public function isAllowed($action, $resourceUri)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $applicationController = $this->_app->getController('Xodx_ApplicationController');

        // TODO

        return true;
    }
}

