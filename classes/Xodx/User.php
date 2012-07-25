<?php

/**
 * This class represents a sioc:UserAccount or any other foaf:OnlineAccount
 * sioc: http://rdfs.org/sioc/ns#
 * foaf: http://xmlns.com/foaf/spec/
 */
class Xodx_User
{
    private $_uri;

    public function __construct ($uri)
    {
        $this->_uri = $uri;
    }

    public function getUri ()
    {
        return $this->_uri;
    }
}
