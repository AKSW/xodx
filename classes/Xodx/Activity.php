<?php

/**
 * This class represents an aair:Activity
 */
class Activity
{
    private $_uri;
    private $_actor;
    private $_verb;
    private $_object;
    private $_date;

    public function __construct ($uri, $actor, $verb, $object, $date = null)
    {
        if ($uri === null) {
            $this->_uri = 'http://localhost/~natanael/xodx/activity/' . md5(rand()) . '/';
        } else {
            $this->_uri = $uri;
        }

        $this->_actor = $actor;
        $this->_verb = $verb;
        $this->_object = $object;
        if ($date === null) {
            $this->_date = date('c');
        } else {
            $this->_date = $date;
        }
    }

    public function getActor ()
    {
        return $this->_actor;
    }

    public function getVerb ()
    {
        return $this->_verb;
    }

    public function getObject ()
    {
        return $this->_object;
    }

    public function getDate ()
    {
        retunr $this->_date;
    }

    public function __toArray ()
    {
        $nsAair = 'http://ximlns.notu.be/aair#';
        $nsAtom = 'http://www.w3.org/2005/Atom/';

        $return = new array(
            $this->_uri => array(
                $nsAair . 'activityActor' => $this->_actor,
                $nsAair . 'activityVerb' => $this->_verb,
                $nsAair . 'activityObject' => $this->_object,
                $nsAtom . 'published' => $this->_date,
            )
        );
    }
}
