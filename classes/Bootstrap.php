<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * This Class is responsible to initialize resources and returning them on request.
 * It keeps a registry of the already initialized resources to not have to the work twice.
 */
class Bootstrap extends Saft_Bootstrap
{

    /**
     * Initializes the configuration by reading the config file
     */
    protected function initConfig ()
    {
        $configPath = $this->_app->getBaseDir() . 'config.ini';

        $configArray = parse_ini_file($configPath, true);

        // TODO merge with some default settings
        // TODO move most settings into the model

        return $configArray['xodx'];
    }

    /**
     * Initializes all namespace prefixes
     */
    protected function initNamespacesConfig ()
    {
        $configPath = $this->_app->getBaseDir() . 'config.ini';

        $configArray = parse_ini_file($configPath, true);

        // TODO merge with some default settings
        // TODO move most settings into the model

        return $configArray['namespaces'];
    }

    /**
     * Initializes the Erfurt Store
     */
    protected function initStore ()
    {
        $configPath = $this->_app->getBaseDir() . 'config.ini';
        $erfurtConfig = null;

        if (is_readable($configPath)) {
            try {
                $erfurtConfig = new Zend_Config_Ini($configPath, 'erfurt');
            } catch (Exception $e) {
            }
        }

        // Creating an instance of Erfurt API
        // don't autostart it to set config
        $erfurt = Erfurt_App::getInstance(false);
        $erfurt->start($erfurtConfig);

        // TODO: add the stuff from
        // https://github.com/AKSW/Erfurt/wiki/Internals

        // Authentification on Erfurt (needed for model access)
        $dbUser = $erfurt->getStore()->getDbUser();
        $dbPass = $erfurt->getStore()->getDbPassword();
        $erfurt->authenticate($dbUser, $dbPass);

        if (!$erfurt->getStore()->isModelAvailable('http://ns.ontowiki.net/SysOnt/', true)) {
            // It seams the store is new, so we should run some setup stuff

            // Create SysOnt
            $t = time ();
            $erfurt->getStore()->getNewModel ( 'http://ns.ontowiki.net/SysOnt/' );
            $erfurt->getStore()->addStatement (
                'http://ns.ontowiki.net/SysOnt/', $t, $t,
                array('value' => $t, 'type' => 'literal')
            );
            $erfurt->getStore()->deleteMatchingStatements ( 
                'http://ns.ontowiki.net/SysOnt/', $t, $t,
                array('value' => $t, 'type' => 'literal')
            );

            // Creates cache tables
            $c = new Erfurt_Cache_Backend_QueryCache_Database ();
            $c->createCacheStructure ();

            // Creates versioning tables
            $v = new Erfurt_Versioning ();
            $v->isVersioningEnabled ();
        }

        return $erfurt->getStore();
    }

    /**
     * Initializes the default Erfurt Model as specified in the config
     */
    protected function initModel ()
    {
        $model = null;
        $store = $this->getResource('store');
        $config = $this->getResource('config');

        $modelUri = $config['xodx.model'];

        if (empty($modelUri)) {
            throw new Exception('No xodx model configured. Please add "xodx.model" entry to "config.ini".');
        }

        // Get the model or create it if it doesn't exist
        $model = $store->getModelOrCreate($modelUri);

        return $model;
    }

    /**
     * Initializes the session
     * Note: currently this is done by initializing Erfurt
     */
    protected function initSession ()
    {
        // Session is started by Zend in Erfurt
        // session_start();

        $store = $this->getResource('store');
        $model = $this->getResource('model');

        return null;
    }

}
