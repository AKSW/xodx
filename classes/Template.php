<?php
class Template {
    private static $_instance = null;
    private $_contentFiles = null;
    private $_menuFiles = null;
    private $_layout = null;

    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new Template();
        }
        return self::$_instance;
    }

    public function __construct() {
        if ($this->_menuFiles === null) {
            $this->_menuFiles = array();
        }
        if ($this->_contentFiles === null) {
            $this->_contentFiles = array();
        }
    }

    public function addMenu($menuFile) {
        $this->_menuFiles[] = $menuFile;
    }

    public function addContent($contentFile) {
        $this->_contentFiles[] = $contentFile;
    }

    public function setLayout($layout) {
        $this->_layout = $layout;
    }

    public function render() {
        include $this->_layout;
    }

}
?>
