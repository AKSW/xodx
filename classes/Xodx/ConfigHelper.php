<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_ConfigHelper extends Saft_Helper
{
    public function loadPropertiesSingle($editorType)
    {
        $properties = $this -> loadProperties($editorType);
        $single = array();

        foreach($properties as $key => $element)
        {
            if ($element["cardinality"] == "single")
            {
                $single[$key] = $element;
            }
        }
        return $single;
    }

    public function loadPropertiesMultiple($editorType)
    {
        $properties = $this -> loadProperties($editorType);
        $multiple = array();

        foreach($properties as $key => $element)
        {
            if ($element["cardinality"] == "multiple")
            {
                $multiple[$key] = $element;
            }
        }
        return $multiple;
    }

    public function loadPropertyRegex()
    {
        $properties = array();
        $config = $this->_app->getBootstrap()->getResource('Config');
        $bothConfigs = explode(",",$config["editor.single"].",".$config["editor.multiple"]);
        $skip = false;

        foreach($bothConfigs as $key => $element)
        {
            if (!$skip)
            {
                $property = $element;
                $skip = true;
            }
            else
            {
                $skip = false;
                $properties[$property] = $this->propertyRegex($element);
            }
        }
        return $properties;
    }

    public function propertyRegex($regexName)
    {
        //echo "propertyRegex: $regexName <br>";
        $config = $this->_app->getBootstrap()->getResource('Config');
        $regexString = "regex.".$regexName;
        return $config[$regexString];
    }

    public function loadProperties($editorType)
    {
        $propertiesPrepared = array();
        $config = $this->_app->getBootstrap()->getResource('Config');
        foreach ($config as $key => $value)
        {
            $keySplit = explode(".",$key);
            if ($keySplit[0] == "editor")
            {
                if ($keySplit[1] == $editorType)
                {
                    if ($keySplit[2] == "property")
                    {
                        $propertiesPrepared[$keySplit[3]][$keySplit[4]] = $value;
                    }
                }
            }
        }
        $properties = array();
        foreach ($propertiesPrepared as $key => $value)
        {
            $properties[$value["uri"]]["type"] = $value["type"];
            $properties[$value["uri"]]["cardinality"] = $value["cardinality"];
            $properties[$value["uri"]]["regex"] = $this -> propertyRegex($value["type"]);
        }
        return $properties;
    }

    public function getEditorClass($editorType)
    {
        $config = $this->_app->getBootstrap()->getResource('Config');
        foreach ($config as $key => $value)
        {
            $keySplit = explode(".",$key);
            if ($keySplit[0] == "editor")
            {
                if ($keySplit[1] == $editorType)
                {
                    if ($keySplit[2] == "class")
                    {
                            return $value;
                    }
                }
            }
        }

    }
}


