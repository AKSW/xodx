<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_ConfigHelper extends Saft_Helper
{
    /**
     * Returns all Properties with the corresponding RegExes, ready for preg_match
     */
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

    /**
     * Returns the RegEx for a given $regexName
     */
    public function propertyRegex($regexName)
    {
        //echo "propertyRegex: $regexName <br>";
        $config = $this->_app->getBootstrap()->getResource('Config');
        $regexString = 'regex.'
                     . $regexName;
        return $config[$regexString];
    }

    /**
     * Returns all properties for a given $editorType
     */
    public function loadProperties($editorType)
    {
        $propertiesPrepared = array();
        $config = $this->_app->getBootstrap()->getResource('Config');
        foreach ($config as $key => $value)
        {
            $keySplit = explode('.',$key);
            if ($keySplit[0] == 'editor')
            {
                if ($keySplit[1] == $editorType)
                {
                    if ($keySplit[2] == 'property')
                    {
                        $propertiesPrepared[$keySplit[3]][$keySplit[4]] = $value;
                    }
                }
            }
        }
        $properties = array();
        foreach ($propertiesPrepared as $key => $value)
        {
            $properties[$value['uri']]['type'] = $value['type'];
            $properties[$value['uri']]['cardinality'] = $value['cardinality'];
            $properties[$value['uri']]['regex'] = $this->propertyRegex($value['type']);
        }
        return $properties;
    }

    /**
     * Returns the class (e.g foaf:person)of an Editor with the given class as it is in the config
     */
    public function getProfilesForTypes ($types)
    {
        $config = $this->_app->getBootstrap()->getResource('Config');

        $profiles = array();
        foreach ($config as $key => $value) {
            $keySplit = explode('.', $key);
            if (isset($keySplit[0]) && isset($keySplit[1]) && $keySplit[0] == 'editor') {
                $profileName = $keySplit[1];
                if (isset($keySplit[2]) && $keySplit[2] == 'class' && in_array($value, $types)) {
                    $profiles[] = $profileName;
                }
            }
        }

        return $profiles;
    }
}
