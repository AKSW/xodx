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
    public __construct ($app) {
        $this->_app = $app;
    }

    /**
     * Get a new Xodx_Notification instance from the model
     */
    public function fromModel ($notificationUri)
    {
        // TODO get notification from model

        $notification = new Xodx_Notification();
        $notification->setUri($notificationUri);

        // TODO set properties
    }
}
