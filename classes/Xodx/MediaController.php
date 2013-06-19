<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_MediaController extends Xodx_ResourceController
{
    public function showAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $request = $bootstrap->getResource('request');

        // Array of Accept Header values
        $imageTypes = array(
            '*/*' => 'show',
            'image/jpg' => 'imagejpg'
        );

        $mimetypeHelper = $this->_app->getHelper('Saft_Helper_MimetypeHelper');
        $match = $mimetypeHelper->matchFromRequest($request, array_keys($imageTypes));

        if ($imageTypes[$match] == 'imagejpg') {
            $template->disableLayout();
            $template->setRawContent('');
            $location = new Saft_Url($request);
            $location->setParameter('a', 'img');
            $template->redirect($location);
        } else {
            $model = $bootstrap->getResource('model');

            $nsAair = 'http://xmlns.notu.be/aair#';
            $nsSioc = 'http://rdfs.org/sioc/ns#';
            $nsFoaf = 'http://xmlns.com/foaf/0.1/';
            $nsDssn = 'http://purl.org/net/dssn/';

            $objectId = $request->getValue('id', 'get');
            $controller = $request->getValue('c', 'get');
            $objectUri = $this->_app->getBaseUri() . '?c=' . $controller . '&id=' . $objectId;

            $query = 'PREFIX aair: <' . $nsAair . '> ' . PHP_EOL;
            $query.= 'PREFIX sioc: <' . $nsSioc . '> ' . PHP_EOL;
            $query.= 'PREFIX dssn: <' . $nsDssn . '> ' . PHP_EOL;
            $query.= 'PREFIX foaf: <' . $nsFoaf . '> ' . PHP_EOL;
            $query.= 'SELECT ?image ?maker ?creation ?feed' . PHP_EOL;
            $query.= 'WHERE { ' . PHP_EOL;
            $query.= '   <' . $objectUri . '> aair:largerImage ?image ; ' . PHP_EOL;
            $query.= '      foaf:maker ?maker ; ' . PHP_EOL;
            $query.= '      sioc:created_at ?creation ; ' . PHP_EOL;
            $query.= '      dssn:activityFeed ?feed . ' . PHP_EOL;
            $query.= '} ' . PHP_EOL;

            $properties = $model->sparqlQuery($query);

            $activityController = $this->_app->getController('Xodx_ActivityController');
            $activities = $activityController->getActivities($objectUri);

            $template->activities = $activities;

            $template->image = $properties[0]['image'];
            $template->creation = $properties[0]['creation'];
            $template->feed = $properties[0]['feed'];
            $template->maker = $properties[0]['maker'];
            $template->resourceUri = $objectUri;
        }

        return $template;
    }

    public function tagAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();

        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');
        $imageUri = $request->getValue('image', 'post');
        $personUri = $request->getValue('person', 'post');

        $nsFoaf = 'http://xmlns.com/foaf/0.1/';
        $model->addStatement(
            $imageUri, $nsFoaf . 'depicts', array('type' => 'uri', 'value' => $personUri)
        );

        return $template;
    }

    /**
     * This method uploads an image file after using an upload form
     * @param $fileName the name.ext of the file posted
     * @return Array with 'fileId' and 'mimeType'
     */
    public function uploadImage($fieldName)
    {
        $bootstrap = $this->_app->getBootstrap();

        $request = $bootstrap->getResource('request');

        $uploadDir = $this->_app->getBaseDir() . 'raw/';
        $checkFile = basename($_FILES[$fieldName]['name']);
        $pathParts = pathinfo($checkFile);
        $tmpFile = $_FILES[$fieldName]['name'];

        // Check if file's MIME-Type is an image
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $checkType = finfo_file($finfo, $_FILES[$fieldName]['tmp_name']);
        finfo_close($finfo);
        $allowedTypes = array(
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/tiff',
            'image/x-ms-bmp',
            'image/x-bmp',
            'image/bmp'
        );

        if (!in_array($checkType, $allowedTypes)) {
            throw new Exception('Unsupported MIME-Type: ' . $checkType);
            return false;
        }

        $uploadFile = md5(rand());
        $uploadPath = $uploadDir . $uploadFile; // . $fileExt;

        // Upload File
        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $uploadPath)) {
            $return = array (
                'fileId' => $uploadFile,
                'mimeType' => $checkType
            );
            return $return;
        } else {
            throw new Exception('Could not move uploaded file to upload directory: ' . $uploadPath);
        }
    }

    public function getImage($objectId, $mimeType)
    {
        header('Content-Type: ' . $mimeType);
        $dir = $this->_app->getBaseDir() . 'raw/';
        return $dir . $objectId;
    }
}
