<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_MediaController extends Saft_Controller
{
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
