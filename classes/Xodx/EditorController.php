<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The EditorController is responsible for providing an Editor for configurable classes.
 */
class Xodx_EditorController extends Xodx_ResourceController
{
    public function editAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $configHelper = new Xodx_ConfigHelper($this->_app);
        $request = $bootstrap->getResource('request');
        $classId = $request->getValue('class', 'get');
        $typeUri = $configHelper -> getEditorClass($classId);
        $applicationController = $this->_app->getController('Xodx_ApplicationController');

        if (strcmp($classId,"person") == 0)
        {
            $userId = $applicationController->getUser();
            $userUri = $this->_app->getBaseUri() . '?c=person&id=' . $userId;
            $objectUri = $userUri;
        }
        else
        {
            $objectUri = $request->getValue('id', 'get');
        }

        $allowedSinglePrefixes = $configHelper->loadPropertiesSingle($classId);
        $allowedMultiplePrefixes = $configHelper->loadPropertiesMultiple($classId);

        $template -> caption = $classId;

        //echo ("<br>$objectUri");
        //echo ("<br>$typeUri");

        if (count ($_POST) == 0)
        {
            //$userId = $applicationController->getUser();

            $query = "SELECT ?p ?o WHERE { <" . $objectUri . "> a <" . $typeUri . "> . <" . $objectUri . "> ?p ?o }";
            //$query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> SELECT ?p ?o WHERE { ?person a foaf:Person. ?person foaf:person '$userUri'. ?person ?p ?o }";

            //echo ("$query");
            //echo ("<br>$objectUri");
            //echo ("<br>$typeUri");

            $profiles = $model->sparqlQuery( $query);
            $template->allowedSinglePrefixes = $allowedSinglePrefixes;
            $template->allowedMultiplePrefixes = $allowedMultiplePrefixes;
            $template->profile = $profiles;

            //echo ("Foo");

            $template->addContent('templates/edit.phtml');

            return $template;
        }
        else
        {
            //Process POSTed values an show ProfileEditor with
            //  a) Data from POST if it needs to be corrected
            //     TODO: Indicated that data...
            //  b) Data from Database if everything was fine so the new data in the DB can be viewed.

            //This is real sourcecode!


            $applicationController = $this->_app->getController('Xodx_ApplicationController');
            $userId = $applicationController->getUser();
            $userUri = $this->_app->getBaseUri() . '?c=person&id=' . $userId;
            $stringArray = explode("id=", $userUri);
            $name = $stringArray[1];
            //$propertyRegex = $this -> loadPropertyRegex();

            $prefixesSinglePrepare = array();
            $valuesSinglePrepare = array();
            $prefixesMultiplePrepare = array();
            $valuesMultiplePrepare = array();
            $valuesSingleNew = array();
            $valuesMultipleNew = array();
            $newKey;
            $newValue;
            $oldValue;
            $changedADD = array();
            $changedDELETE = array();
            $wrong = array();

            $query = "SELECT ?p ?o WHERE { <" . $userUri . "> a <" . $typeUri . "> . <" . $userUri . "> ?p ?o }";
            $databaseValues = $model->sparqlQuery($query);
            $notFoundMultipleKeys = $databaseValues;

            //echo ("Foo");

            //var_dump($notFoundMultipleKeys);
            //echo ("<hr>");

            //echo ("Database Values:<br>");
            //var_dump($databaseValues);
            //echo ("<hr>");

            //prepare $_POST into prefix --> value
            foreach ($_POST as $key => $value)
            {
                //echo ("$key - $value <br>");
                $keyArray = explode("_", $key);
                $number = (int)$keyArray[0];

                //single
                if ($keyArray[1] == "value")
                {
                    $valuesSinglePrepare[$number] = $value;
                }

                if ($keyArray[1] == "prefix")
                {
                    $prefixesSinglePrepare[$number] = $value;
                }

                //multiple
                //$numberInKey is only needed if Property is multiple, so it is put inside the if statements.
                if ($keyArray[1] == "Mvalue")
                {
                    $numberInKey = (int)$keyArray[count($keyArray)-1];
                    $valuesMultiplePrepare[$number][$numberInKey] = $value;
                }

                if ($keyArray[1] == "Mprefix")
                {
                    $numberInKey = (int)$keyArray[count($keyArray)-1];
                    $prefixesMultiplePrepare[$number] = $value;
                }
            }

            foreach ($prefixesSinglePrepare as $key => $value)
            {
                $valuesSingleNew[$value] = $valuesSinglePrepare[(int)$key];
            }

            //Single
            foreach ($valuesSingleNew as $key => $value)
            {
                //Reset old values
                $oldValue = "";
                $newKey = $key;

                //find corresponding value in query
                //Searches for equivalent of $newKey
                foreach ($databaseValues as $dbkey => $element)
                {
                    $p = $element["p"];
                    $o = $element["o"];
                    //echo "<br>$p -- $o";
                    if (strcmp ($element["p"],  $newKey) == 0)
                    {
                        $oldValue = $element["o"];
                        unset($notFoundMultipleKeys[$dbkey]);
                    }
                }

                if ($value != $oldValue )
                {
                    $rString = $allowedSinglePrefixes[$key]["regex"];
                    if (ereg($rString, $value) == true)
                    {
                        //echo ("Match: $value for $newKey with $rString");
                        $temp = array();
                        $temp['p'] = $newKey;
                        $temp['o'] = $value;
                        $changedADD[] = $temp;
                        $temp = array();
                        $temp['p'] = $newKey;
                        $temp['o'] = $oldValue;
                        $changedDELETE[] = $temp;
                    }
                    else
                    {
                        //echo ("Wrong Format: $value for $newKey with $rString<br>");
                        if (!empty ($value))
                        {
                            $wrong[$newKey] = $value;
                        }
                    }
                }
            }

            //Multiple
            foreach ($prefixesMultiplePrepare as $prefixKey => $prefix)
            {
                $values = $valuesMultiplePrepare[$prefixKey];
                foreach ($values as $valueKey => $value)
                {
                    // 1. Forall key->value in newValues
                    // 1.1 Find corresponding value.
                    if ($value == "")
                    {
                        break;
                    }
                    $found = false;
                    //echo ("Looking for $prefix -> $value<br>");

                    //echo ("<hr>");
                    foreach ($databaseValues as $key => $element)
                    {
                        //only for MultipleStatements
                        $p = $element["p"];
                        $o = $element["o"];
                        if (in_array($p, array_keys($allowedMultiplePrefixes)))
                        {
                            if (strcmp ($p,  $prefix) == 0)
                            {
                                if (strcmp ($o,  $value) == 0)
                                {
                                    //1.2 Delete this pair from $oldValues
                                    //TODO: Implement this.
                                    $found = true;
                                    //echo ("$key <br>");
                                    //echo ("Found: $prefix -> $value<br>");
                                    unset($notFoundMultipleKeys[$key]);
                                    //var_dump($notFoundMultipleKeys);
                                }
                            }
                        }
                    }
                    if (!$found)
                    {
                        //echo ("Not found: $prefix -> $value<br>");
                        $rString = $allowedMultiplePrefixes[$prefix]["regex"];
                        //echo ("$rString");
                        //var_dump($value);
                        if (preg_match($rString, $value) == true)
                        {
                            //echo ("Match: $value for $newKey");
                            $temp = array();
                            $temp['p'] = $prefix;
                            $temp['o'] = $value;
                            $changedADD[] = $temp;
                        }
                        else
                        {
                            //echo ("Wrong Format: $value for $prefix with $rString <br>");
                            if (!empty ($value))
                            {
                                $temp = array();
                                $temp['p'] = $prefix;
                                $temp['o'] = $value;
                                $wrong[] = $temp;
                            }
                        }
                    }
                }
            }

            if (count($wrong) > 0 && !is_null($wrong))
            {
                //Allow wrong Properties to be corrected
                foreach ($wrong as $key => $value)
                {
                    $databaseValues[] = $value;
                }

                $template->allowedSinglePrefixes = $allowedSinglePrefixes;
                $template->allowedMultiplePrefixes = $allowedMultiplePrefixes;
                $template->profile = $databaseValues;
                $template->config = $config;
                $template->wrong = $wrong;
                $template->addContent('templates/edit.phtml');
                return $template;
            }
            else
            {
                //prepare $notFoundMultipleKeys
                foreach ($notFoundMultipleKeys as $key => $element)
                {
                    $p = $element["p"];
                    $o = $element["o"];

                    if (in_array($p, array_keys($allowedMultiplePrefixes)))
                    {
                        $temp = array();
                        $temp['p'] = $p;
                        $temp['o'] = $o;
                        $changedDELETE[] = $temp;
                    }
                }
                //Write Properties to Database
                foreach ($changedDELETE as $key => $value)
                {
                    //$keyArray = array('value' => );
                    $valueArray = array('type' => 'literal', 'value' => $value['o']);
                    $keyToDelete = $value['p'];
                    $valueToDelete = $value['o'];
                    //echo ("<br>Delete: $userUri, $keyToDelete, $valueToDelete");
                    $model->deleteStatement($userUri, $keyToDelete, $valueArray);
                }
                foreach ($changedADD as $key => $value)
                {
                    //$keyArray = array('value' => );
                    //array('type' => 'uri', 'value' => $newPersonUri)
                    $valueArray = array('type' => 'literal', 'value' => $value['o']);
                    $keyToWrite = $value['p'];
                    $valueToWrite = $value['o'];
                    //echo ("<br>Writing: $userUri, $keyToWrite, $valueToWrite");
                    $model->addStatement($userUri, $keyToWrite, $valueArray);
                }

                //Show Profileeditor with Values from Database.
                $_POST = NULL;
                $template = $this -> editAction($template);
                return $template;
            }
        }
    }

public function loadPropertiesAction()
    {
        $configHelper = new Xodx_ConfigHelper($this->_app);
        return $configHelper -> loadProperties();
    }

    public function loadPropertiesSingleAction()
    {
        $configHelper = new Xodx_ConfigHelper($this->_app);
        var_dump($configHelper -> loadPropertiesSingle("conference"));
    }

    public function loadPropertiesMultipleAction()
    {
        $configHelper = new Xodx_ConfigHelper($this->_app);
        var_dump($configHelper -> loadPropertiesMultiple("person"));
    }
}
