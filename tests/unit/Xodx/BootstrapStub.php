<?php

class Xodx_BootstrapStub
{
    private $_resources = array();

    public function getResource ($name)
    {
        if (!isset($this->_resources[$name])) {
            switch ($name) {
                case 'logger':
                    $this->_resources[$name] = new Xodx_LoggerStub();
                case 'model':
                    $this->_resources[$name] = new Xodx_ModelStub();
            }
        }

        return $this->_resources[$name];
    }
}
