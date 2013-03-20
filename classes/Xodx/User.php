<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * This class represents a sioc:UserAccount or any other foaf:OnlineAccount
 * sioc: http://rdfs.org/sioc/ns#
 * foaf: http://xmlns.com/foaf/spec/
 */
class Xodx_User
{
    private $_uri;
    private $_name;
    private $_personUri = null;

    public function __construct ($uri)
    {
        $this->_uri = $uri;
    }

    public function getUri ()
    {
        return $this->_uri;
    }

    public function getName ()
    {
        return $this->_name;
    }

    public function setName ($name)
    {
        $this->_name = $name;
    }

    public function getPerson ()
    {
        return $this->_personUri;
    }

    public function setPerson ($personUri)
    {
        $this->_personUri = $personUri;
    }
}
