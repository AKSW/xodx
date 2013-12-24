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
        $userController = $this->_app->getController('Xodx_UserController');
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');
        $config = $bootstrap->getResource('config');

        $configHelper = $this->_app->getHelper('Xodx_ConfigHelper');
        $rightsHelper = $this->_app->getHelper('Xodx_RightsHelper');

        $classId = $request->getValue('class', 'get');
        $resourceUri = $request->getValue('id', 'get');

        if ($classId == 'person' && empty($resourceUri)) {
            // Use current users URI if no resourceUri was passed
            $resourceUri = $userController->getUser()->getPerson();
        } else if (empty($resourceUri)) {
            throw new Exception('Resource to edit is unknownm, no resource URI given. Pleas specify parameter "id".');
        }

        // AC. Check if action is allowed.
        if (!$rightsHelper->isAllowed('edit', $resourceUri)) {
            throw new Exception('You don\'t have the right to edit "' . $resourceUri . '"');
        }

        $typeQuery = 'SELECT ?type' . PHP_EOL;
        $typeQuery.= 'WHERE {' . PHP_EOL;
        $typeQuery.= '  <' . $resourceUri . '> a ?type ' . PHP_EOL;
        $typeQuery.= '}';

        $resourceTypeResult = $model->sparqlQuery($typeQuery);

        $types = array();
        foreach ($resourceTypeResult as $typeRow) {
            $types[] = $typeRow['type'];
        }

        // Get configured profile to determine predicates to be shown in editor
        $profiles = $configHelper->getProfilesForTypes($types);

        $numProfiles = count($profiles);
        if ($numProfiles != 1) {
            throw new Exception('Can\'t determine which application profile to use. Following profiles are available: ' . var_export($profiles, true));
        }
        $profile = $configHelper->loadProperties($profiles[0]);
        $resourceDescription = $this->_getResourceDescription($resourceUri, $model, $profile);

        $newValues = $request->getValue('newValues', 'post');
        $originalValues = $request->getValue('originalValues', 'post');

        $template->id = $resourceUri;
        if ($newValues === null) {
            //Add values to template
            $template->profile = $resourceDescription;

            return $template;
        }

        // Check if the posted values are based on the current data or if the resource was changed
        // in between.
        if (!$this->_equal($originalValues, $resourceDescription)) {
            // TODO show the changes to the user to let him merge his edit
            throw new Exception('The resoure was changed in between');
        }

        $diff = $this->_diff($newValues, $resourceDescription);

        // Check validaty of added values
        $wrong = array();
        foreach ($diff['add'] as $resource => $properties) {
            foreach ($properties as $predicate => $objects) {
                if (!isset($profile[$predicate])) {
                    continue;
                }
                foreach ($objects as $object) {
                    $regexString = $profile[$predicate]['regex'];
                    if (!empty($object['value']) && !preg_match($regexString, $object['value'])) {
                        if (!isset($wrong[$predicate])) {
                            $wrong[$predicate] = array();
                        }
                        $wrong[$predicate][] = $object['value'];
                    }
                }
            }
        }

        // Check if there are any wrong properties
        if (count($wrong) > 0) {
            //Add Values to $template
            $template->wrong = $wrong;
            $template->profile = $newValues;
            $template->diff = $diff;
            $template->error = 'Please correct the red Properties!';

            return $template;
        } else {
            // Write changes
            // TODO start transaction
            $model->deleteMultipleStatements($diff['delete']);
            $model->addMultipleStatements($diff['add']);
            // TODO end transaction

            // Show editor with values from database
            $template->profile = $this->_getResourceDescription($resourceUri, $model, $profile);
            $template->info = 'Wrote changes to database.';
            return $template;
        }
    }

    /**
     * This method checks if a posted array is equal to the given result set.
     *
     * @param $postValues contains an array in array('predicate' => array('value1', 'value2', …)) format
     * @param $resultSet contains an array in sparql result-set format with ?p ?o
     * @return wether the properties are equal or not
     */
    private function _equal ($postValues, $resultSet)
    {
        $diff = $this->_diff($postValues, $resultSet);

        if (count($diff['add']) == 0 && count($diff['delete']) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * This method compares a posted array to the given result set.
     *
     * @param $newValues contains an array in array('predicate' => array('value1', 'value2', …)) format
     * @param $originalData contains an array in sparql result-set format with ?p ?o
     * @return array changeset with arra('add' => …, 'delete' => …)
     */
    private function _diff ($newValues, $originalData)
    {
        $add    = $this->_minus($newValues, $originalData);
        $delete = $this->_minus($originalData, $newValues);

        return array('add' => $add, 'delete' => $delete);
    }

    private function _minus ($aValues, $bValues)
    {
        $cValues = array();
        foreach ($aValues as $resource => $properties) {
            foreach ($properties as $predicate => $objects) {
                foreach ($objects as $object) {
                    $found = false;
                    if ($object['value'] === null) {
                        continue;
                    }
                    if (isset($bValues[$resource][$predicate])) {
                        foreach ($bValues[$resource][$predicate] as $foundObject) {
                            if (
                                $foundObject['value'] == $object['value'] &&
                                $foundObject['type'] == $object['type']
                            ) {
                                $found = true;
                                break;
                            }
                        }
                    }
                    if (!$found) {
                        if (!isset($cValues[$resource])) {
                            $cValues[$resource] = array();
                        }
                        if (!isset($cValues[$resource][$predicate])) {
                            $cValues[$resource][$predicate] = array();
                        }
                        $cValues[$resource][$predicate][] = $object;
                    }
                }
            }
        }
        return $cValues;
    }

    /**
     * This method gets a description array for a given resource.
     *
     * @param $resourceUri the URI of the resource to describe
     * @param $model the model from where to get the description
     * @return an array of the format
     * array('resource' => array('property' => array('type' => …, 'value' => …, …)))
     */
    private function _getResourceDescription ($resourceUri, $model, $profile)
    {
        $description = $model->getResource($resourceUri)->getDescription(1);
        foreach ($profile as $predicate => $restrictions) {
            if (!isset($description[$resourceUri][$predicate])) {
                $description[$resourceUri][$predicate] = array(
                    array (
                        'type' => strtolower($restrictions['type']),
                        'value' => null
                    )
                );
            }
        }
        return $description;
    }
}
