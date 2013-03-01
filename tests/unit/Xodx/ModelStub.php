<?php

class Xodx_ModelStub
{
    private $_statements = array();

    public function addMultipleStatements ($statements)
    {
        $this->_statements = array_merge($this->_statements, $statements);
    }

    public function getModelIri ()
    {
        return 'http://example.com/';
    }
}
