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
    /**
     * This shows the editor an processes any edited data.
     */
    public function editAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $configHelper = new Xodx_ConfigHelper($this->_app);
        $rightsHelper = new Xodx_RightsHelper($this->_app);
        $request = $bootstrap->getResource('request');
        $classId = $request->getValue('class', 'get');
        $typeUri = $configHelper->getEditorClass($classId);
        $applicationController = $this->_app->getController('Xodx_ApplicationController');

        //Needed switch to get personUri without passing it via $_GET
        if (strcmp($classId, "person") == 0) {
            $objectUri = urldecode($request->getValue('id', 'get'));
            //Use current UserId if no personUri was passed
            if (empty($objectUri)) {
                $userId = $applicationController->getUser();
                $userUri = $this->_app->getBaseUri() . '?c=person&id=' . $userId;
                $objectUri = $userUri;
            }
        } else {
            $objectUri = urldecode($request->getValue('id', 'get'));
        }

        //RightsManagement. Ask rightsHelper if action is allowed.
        $hasRights = $rightsHelper->HasRights('edit', $classId, $objectUri);
        if (!$hasRights) {
            echo ('You do not have the rights for this. Sorry.');
            return;
        }

        //Get Prefixes to be shown in the Editor
        $allowedSinglePrefixes = $configHelper->loadPropertiesSingle($classId);
        $allowedMultiplePrefixes = $configHelper->loadPropertiesMultiple($classId);

        $template->caption = $classId;
        $template->id = $objectUri;

        //Switch if this was called from a Form.
        if (count ($_POST) == 0) {
            //Get Values from Database
            $query = "SELECT ?p ?o WHERE { <"
                   . $objectUri
                   . "> a <"
                   . $typeUri
                   . "> . <"
                   . $objectUri
                   . "> ?p ?o }";

            $profiles = $model->sparqlQuery($query);

            //Add Values to $template
            $template->allowedSinglePrefixes = $allowedSinglePrefixes;
            $template->allowedMultiplePrefixes = $allowedMultiplePrefixes;
            $template->profile = $profiles;
            $template->addContent('templates/edit.phtml');

            return $template;
        } else {
            //Process POSTed values and show ProfileEditor with
            //  a) Data from POST if it needs to be corrected
            //  b) Data from Database if everything was fine so the new data in the DB can be viewed.

            $applicationController = $this->_app->getController('Xodx_ApplicationController');
            $userId = $applicationController->getUser();
            $userUri = $this->_app->getBaseUri() . '?c=person&id=' . $userId;
            $stringArray = explode("id=", $userUri);
            $name = $stringArray[1];

            $prefixesSinglePrepare = array();
            $valuesSinglePrepare = array();
            $prefixesMultiplePrepare = array();
            $valuesMultiplePrepare = array();
            $valuesSingleNew = array();
            $valuesMultipleNew = array();
            $newKey;
            $newValue;
            $oldValue;
            $changedAdd = array();
            $changedDelete = array();
            $wrong = array();

            $query = 'SELECT ?p ?o WHERE { <'
                   . $objectUri
                   . '> a <'
                   . $typeUri
                   . '> . <'
                   . $objectUri
                   . '> ?p ?o }';
            $databaseValues = $model->sparqlQuery($query);
            $notFoundMultipleKeys = $databaseValues;

            //prepare $_POST into prefix --> value
            foreach ($_POST as $key => $value) {
                $keyArray = explode('_', $key);
                $number = (int)$keyArray[0];

                //single Properties
                if ($keyArray[1] == 'value') {
                    $valuesSinglePrepare[$number] = $value;
                }

                if ($keyArray[1] == 'prefix') {
                    $prefixesSinglePrepare[$number] = $value;
                }

                //multiple Properties
                //$numberInKey is only needed if Property is multiple,
                //so it is put inside the if statements.
                if ($keyArray[1] == 'Mvalue') {
                    $numberInKey = (int)$keyArray[count($keyArray)-1];
                    $valuesMultiplePrepare[$number][$numberInKey] = $value;
                }

                if ($keyArray[1] == 'Mprefix') {
                    $numberInKey = (int)$keyArray[count($keyArray)-1];
                    $prefixesMultiplePrepare[$number] = $value;
                }
            }

            foreach ($prefixesSinglePrepare as $key => $value) {
                $valuesSingleNew[$value] = $valuesSinglePrepare[(int)$key];
            }

            //Single Properties
            foreach ($valuesSingleNew as $key => $value) {
                //Reset old values
                $oldValue = "";
                $newKey = $key;

                //find corresponding value in query
                //Searches for equivalent of $newKey
                foreach ($databaseValues as $dbkey => $element) {
                    $p = $element['p'];
                    $o = $element['o'];
                    if (strcmp ($element['p'],  $newKey) == 0) {
                        $oldValue = $element['o'];
                        unset($notFoundMultipleKeys[$dbkey]);
                    }
                }

                if ($value != $oldValue) {
                    $rString = $allowedSinglePrefixes[$key]['regex'];
                    //check Regex
                    if (preg_match($rString, $value)) {
                        //If Value matches, add it to the Values that are written and deleted from the DB.
                        $temp = array();
                        $temp['p'] = $newKey;
                        $temp['o'] = $value;
                        $changedAdd[] = $temp;
                        $temp = array();
                        $temp['p'] = $newKey;
                        $temp['o'] = $oldValue;
                        $changedDelete[] = $temp;
                    } else {
                        //If the Value is empty, it might not pass the Regex, but shall not be shown as wrong.
                        if (!empty ($value)) {
                            //Add Value to array which will later be shown as wrong values.
                            $wrong[$key] = $value;
                        }
                    }
                }
            }

            //Multiple Properties
            foreach ($prefixesMultiplePrepare as $prefixKey => $prefix) {
                $values = $valuesMultiplePrepare[$prefixKey];
                foreach ($values as $valueKey => $value) {
                    // 1. Forall key->value in newValues
                    // 1.1 Find corresponding value.
                    if ($value == '') {
                        break;
                    }
                    $found = false;

                    foreach ($databaseValues as $key => $element) {
                        //only for needed MultipleStatements
                        $p = $element['p'];
                        $o = $element['o'];
                        if (in_array($p, array_keys($allowedMultiplePrefixes))) {
                            if (strcmp ($p,  $prefix) == 0) {
                                if (strcmp ($o,  $value) == 0) {
                                    //1.2 Delete this pair from $oldValues
                                    //These Values are deleted from an extra Array
                                    //At the End, all Values from this Array are deleted.
                                    $found = true;
                                    unset($notFoundMultipleKeys[$key]);
                                }
                            }
                        }
                    }
                    if (!$found) {
                        $rString = $allowedMultiplePrefixes[$prefix]['regex'];
                        //check Regex
                        if (preg_match($rString, $value)) {
                            //If Value matches, add it to the Values that are written to the DB.
                            $temp = array();
                            $temp['p'] = $prefix;
                            $temp['o'] = $value;
                            $changedAdd[] = $temp;
                        } else {
                            //If the Value is empty, it might not pass the Regex, but shall not be shown as wrong.
                            if (!empty ($value)) {
                                //Add Value to array which will later be shown as wrong values.
                                $temp = array();
                                $temp['p'] = $prefix;
                                $temp['o'] = $value;
                                $wrong[] = $temp;
                            }
                        }
                    }
                }
            }

            //Check if there are any wrong Properties.
            if (count($wrong) > 0 && !is_null($wrong)) {
                //Allow wrong Properties to be corrected
                //Therefore, change all the wrong values in the Values gotten from the database.
                foreach ($wrong as $key => $value) {
                    $databaseValues[] = $value;
                }
                //Add Values to $template
                $template->allowedSinglePrefixes = $allowedSinglePrefixes;
                $template->allowedMultiplePrefixes = $allowedMultiplePrefixes;
                $template->profile = $databaseValues;
                $template->config = $config;
                $template->wrong = $wrong;
                $template->addContent('templates/edit.phtml');
                return $template;
            } else {
                //Prepare multiple Keys (deleted)
                foreach ($notFoundMultipleKeys as $key => $element) {
                    $p = $element['p'];
                    $o = $element['o'];

                    if (in_array($p, array_keys($allowedMultiplePrefixes))) {
                        $temp = array();
                        $temp['p'] = $p;
                        $temp['o'] = $o;
                        $changedDelete[] = $temp;
                    }
                }
                //Write Properties to Database
                foreach ($changedDelete as $key => $value) {
                    $valueArray = array(
                        'type'  => 'literal',
                        'value' => $value['o']);
                    $keyToDelete = $value['p'];
                    $valueToDelete = $value['o'];
                    $model->deleteStatement($objectUri, $keyToDelete, $valueArray);
                }
                foreach ($changedAdd as $key => $value) {
                    $valueArray = array(
                        'type'  => 'literal',
                        'value' => $value['o']);
                    $keyToWrite = $value['p'];
                    $valueToWrite = $value['o'];
                    $model->addStatement($objectUri, $keyToWrite, $valueArray);
                }

                //Show Editor with Values from Database.
                $_POST = null;
                $template = $this->editAction($template);
                return $template;
            }
        }
    }

    //The following functions are only for debug purposes,
    //but left here, in case anybody might need them.
    public function loadPropertiesAction()
    {
        $configHelper = new Xodx_ConfigHelper($this->_app);
        return $configHelper->loadProperties();
    }

    public function loadPropertiesSingleAction()
    {
        $configHelper = new Xodx_ConfigHelper($this->_app);
        var_dump($configHelper->loadPropertiesSingle('conference'));
    }

    public function loadPropertiesMultipleAction()
    {
        $configHelper = new Xodx_ConfigHelper($this->_app);
        var_dump($configHelper->loadPropertiesMultiple('person'));
    }
}
