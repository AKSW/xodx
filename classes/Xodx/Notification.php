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
    private $_attachment;

    private $_content;

    private $_read;

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
    public function setContent ($content)
    {
        $this->_content = $content;
    }

    /**
     * setter method for this objects uri
     */
    public function setAttachment ($attachment)
    {
        $this->_attachment = $attachment;
    }

    /**
     * setter method for this read status
     */
    public function setRead ($read)
    {
        if ($read) {
            $this->_read = true;
        } else {
            $this->_read = false;
        }
    }

    public function getUri ()
    {
        return $this->_uri;
    }

    public function getUserUri ()
    {
        return $this->_userUri;
    }

    public function getContent ()
    {
        return $this->_content;
    }

    public function getAttachment ()
    {
        return $this->_attachment;
    }

    public function isRead ()
    {
        return $this->_read;
    }

    public function toArray ()
    {
        $array = array(
            'uri' => $this->_uri,
            'userUri' => $this->_userUri,
            'attachment' => $this->_attachment,
            'content' => $this->_content,
            'read' => $this->_read
        );

        return $array;
    }
}
