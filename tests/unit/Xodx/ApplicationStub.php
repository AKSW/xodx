<?php

class Xodx_ApplicationStub
{
    private $_bootstrap;

    public function getBootstrap()
    {
        if (!isset($this->_bootstrap)) {
            $this->_bootstrap = new Xodx_BootstrapStub();
        }

        return $this->_bootstrap;
    }
}
