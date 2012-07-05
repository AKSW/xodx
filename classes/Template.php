<?php
class Template {
    private static $_instance = null;

    private $_layoutEnabled = true;

    private $_contentFiles = null;
    private $_menuFiles = null;
    private $_layout = null;
    private $_rawContent = null;
    private $_debugLog = '';

    public static function getInstance ()
    {
        if (self::$_instance == null) {
            self::$_instance = new Template();
        }
        return self::$_instance;
    }

    public function __construct () {
        if ($this->_menuFiles === null) {
            $this->_menuFiles = array();
        }
        if ($this->_contentFiles === null) {
            $this->_contentFiles = array();
        }
    }

    public function addMenu ($menuFile) {
        $this->_menuFiles[] = $menuFile;
    }

    public function addContent ($contentFile) {
        $this->_contentFiles[] = $contentFile;
    }

    public function setLayout ($layout) {
        $this->_layout = $layout;
    }

    public function addDebug ($debugString) {
        $this->_debugLog .= $debugString . "\n";
    }

    public function disableLayout () {
        $this->_layoutEnabled = false;
    }

    public function setRawContent ($rawContent) {
        $this->_rawContent = $rawContent;
    }

    public function render () {
        if ($this->_layoutEnabled) {
            include $this->_layout;
        } else {
            echo $this->_rawContent;
        }
    }

}
