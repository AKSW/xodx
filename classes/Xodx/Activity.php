<?php
// TODO replace this by dssn-lib-php
/**
 * This class represents an aair:Activity
 */
class Xodx_Activity
{
    private $_uri;
    private $_actorUri;
    private $_verbUri;
    private $_objectUri;
    private $_contextUri;
    private $_date;

    public function __construct ($uri, $actorUri, $verbUri, $objectUri, $contextUri = null, $date = null)
    {
        if ($uri === null) {
            $this->_uri = 'http://localhost/~natanael/xodx/activity/' . md5(rand()) . '/';
        } else {
            $this->_uri = $uri;
        }

        $this->_actorUri = $actorUri;
        $this->_verbUri = $verbUri;
        $this->_objectUri = $objectUri;
        if ($date === null) {
            $this->_date = date('c');
        } else {
            $this->_date = $date;
        }
        if ($context !== null) {
            $this->_contextUri = $contextUri;
        } else {
            $this->_contextUri = '';
        }
    }

    public function getActor ()
    {
        return $this->_actorUri;
    }

    public function getVerb ()
    {
        return $this->_verbUri;
    }

    public function getObject ()
    {
        return $this->_objectUri;
    }

    public function getContext ()
    {
        return $this->_contextUri;
    }

    public function getDate ()
    {
        return $this->_date;
    }

    public function toGraphArray ()
    {
        $nsAair = 'http://ximlns.notu.be/aair#';
        $nsAtom = 'http://www.w3.org/2005/Atom/';
        $nsRdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $nsXsd = 'http://www.w3.org/2001/XMLSchema#';

        $return = array(
            $this->_uri => array(
                $nsRdf . 'type' => array(
                    array(
                        'type' => 'uri',
                        'value' => $nsAair . 'Activity'
                    )
                ),
                $nsAtom . 'published' => array(
                    array(
                        'type' => 'literal',
                        'value' => $this->_date,
                        'datatype' => $nsXsd . 'dateTime'
                    )
                ),
                $nsAair . 'activityActor' => array(
                    array(
                        'type' => 'uri',
                        'value' => $this->_actorUri
                    )
                ),
                $nsAair . 'activityVerb' => array(
                    array(
                        'type' => 'uri',
                        'value' => $this->_verbUri
                    )
                ),
                $nsAair . 'activityObject' => array(
                    array(
                        'type' => 'uri',
                        'value' => $this->_objectUri
                    )
                )
            )
        );
        if (!empty($this->_contextUri)) {
            $return[$this->_uri][$nsAair . 'activityContext'][0]['type'] = 'uri';
            $return[$this->_uri][$nsAair . 'activityContext'][0]['value'] = $this->_contextUri;
        }

        return $return;
    }
}
