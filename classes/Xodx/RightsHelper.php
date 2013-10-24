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
    public function HasRights($action, $type, $id)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $applicationController = $this->_app->getController('Xodx_ApplicationController');

        //This obviously has to be expanded.
        if (strcmp($type,"person") == 0)
        {
            $userId = $applicationController->getUser();
            $userUri = $this->_app->getBaseUri() . '?c=person&id=' . $userId;
            if (strcmp($userUri, $id) == 0)
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        if (strcmp($type,"conference") == 0)
        {
            return true;
        }
    }
}

