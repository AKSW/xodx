<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * This class represents notifications which are stored for a user to let him know, that some
 * action happend (e.g. new message, new friend request).
 */
class Xodx_Notification
{
    /**
     * The uri of the notification represented by this object
     */
    private $_uri;

    /**
     * The uri of the user to whome this message may concern
     */
    private $_userUri;

    /**
     * A resource related to this notification, e.g. an Activity
     */
    private $_seeAlsoResource;

    /**
     * setter method for this objects uri
     */
    public function setUri ($notificationUri)
    {
        $this->_uri = $notificationUri;
    }

    /**
     * setter method for this objects uri
     */
    public function setUserUri ($userUri)
    {
        $this->_userUri = $userUri;
    }

    /**
     * setter method for this objects uri
     */
    public function setSeeAlsoUri ($seeAlsoResource)
    {
        $this->_seeAlsoResource = $seeAlsoResource;
    }
}
