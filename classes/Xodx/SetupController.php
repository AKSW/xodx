<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * This controller provides actions for different things which have to be done to install, upgrade
 * or reset an xodx installation
 */
class Xodx_SetupController extends Saft_Controller
{
    /**
     * If this action is called the complete database is cleared as if it would be new.
     * Use with caution!
     */
    function clearDatabaseAction ($template) {
        try {
            $this->clearDatabase();
        } catch (Erfurt_Store_Exception $e) {
            $template->addContent('templates/error.phtml');
            $template->exception = $e;
        }
        return $template;
    }

    /**
     * If this method is called the complete database is cleared as if it would be new
     * Use with caution!
     *
     * @throws Exception throws an Erfurt_Exception if it couldn't delete the model
     */
    function clearDatabase () {
        $bootstrap = $this->_app->getBootstrap();
        $store = $bootstrap->getResource('store');
        $model = $bootstrap->getResource('model');

        // TODO: catch Exception and wrapt it in some Saft Exception
        $modelIri = $model->getBaseIri();
        $store->deleteModel($modelIri);
    }
}
