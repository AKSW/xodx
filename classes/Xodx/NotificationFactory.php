<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * This factory creates new notification objects
 */
class Xodx_NotificationFactory
{
    /**
     * The reference to the currently valid application instace
     */
    private $_app;

    /**
     * The construct method for this factory
     * @param $app the currently valid application instance
     */
    public function __construct ($app) {
        $this->_app = $app;
    }

    /**
     * Get a new Xodx_Notification instance from the model
     */
    public function fromModel ($notificationUri)
    {
        // TODO get notification from model
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $nsDssn = 'http://purl.org/net/dssn/';
        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsDct  = 'http://purl.org/dc/terms/';

        $query = 'PREFIX dssn: <' . $nsDssn . '>' . PHP_EOL;
        $query.= 'PREFIX sioc: <' . $nsSioc . '>' . PHP_EOL;
        $query.= 'PREFIX dct: <' . $nsDct . '>' . PHP_EOL;
        $query.= 'SELECT ?user ?content ?attachment' . PHP_EOL;
        $query.= 'WHERE {' . PHP_EOL;
        $query.= '  <' . $notificationUri . '> dssn:notify ?user;' . PHP_EOL;
        $query.= '      sioc:content ?content;' . PHP_EOL;
        $query.= '      dct:references ?attachment.' . PHP_EOL;
        $query.= '}' . PHP_EOL;

        $result = $model->sparqlQuery($query);

        if (count($result) > 0) {
            $notification = new Xodx_Notification();
            $notification->setUri($notificationUri);
            $notification->setUserUri($result[0]['user']);
            $notification->setContent($result[0]['content']);
            $notification->setAttachment($result[0]['attachment']);

            return $notification;
        } else {
            return null;
        }

        // TODO set properties
    }

    /**
     * Create a new Xodx_Notification instance for a User
     */
    public function forUser ($userUri, $text, $attachmentUri = null)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');

        $notificationUri = $model->createResourceUri('Notification');
        $notification = new Xodx_Notification($notificationUri);

        $nsDssn = 'http://purl.org/net/dssn/';
        $nsSioc = 'http://rdfs.org/sioc/ns#';
        $nsDct  = 'http://purl.org/dc/terms/';

        $statements = array(
            $notificationUri => array(
                EF_RDF_NS . 'type' => array(
                    array('type' => 'uri', 'value' => $nsDssn . 'Notification')
                ),
                $nsDssn . 'notify' => array(
                    array('type' => 'uri', 'value' => $userUri)
                ),
                $nsSioc . 'content' => array(
                    array('type' => 'literal', 'value' => $text)
                )
            )
        );

        if ($attachmentUri !== null) {
            $statements[$notificationUri][$nsDct . 'references'][] = array(
                'type' => 'uri', 'value' => $attachmentUri
            );
        }

        $model->addMultipleStatements($statements);
    }
}
