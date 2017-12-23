<?php

namespace App\Model\Manager;

use Nette;


/**
 * Users management.
 */
class FileManager extends BaseManager
{
    
    const FILE_TYPE_IMAGE = [
        'code' => 'image',
        'types' => [
            'image/svg+xml',
            'image/png',
            'image/jpeg',
            'image/bmp',
            'image/x-windows-bmp',
            'image/gif',
            'image/jpeg',
            'image/pjpeg',
            'image/jpeg',
            'image/pjpeg',
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/tiff',
            'image/x-tiff',
            'image/tiff',
            'image/x-tiff',
            'image/gdraw'
        ]
    ];
    const FILE_TYPE_SPREADSHEET = [
        'code' => 'spreadsheet',
        'types' => [
            'application/vnd.ms-excel',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]
    ];
    const FILE_TYPE_PRESENTASION = [
        'code' => 'presentation',
        'types' => [
            'application/vnd.ms-powerpoint',
            'application/vnd.oasis.opendocument.presentation',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.google-apps.presentation',
            'application/mspowerpoint',
            'application/mspowerpoint',
            'application/powerpoint',
            'application/x-mspowerpoint'
        ]
    ];
    const FILE_TYPE_DOCUMENT = [
        'code' => 'document',
        'types' => [
            'application/msword',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/pdf',
            'application/rtf',
            'application/x-rtf',
            'text/richtext',
            'application/x-tex',
            'text/plain',
            'application/wks', 
            'application/x-wks',
            'application/wordperfect',
            'application/x-wpwin',
            'application/vnd.google-apps.document'
        ]
    ];
    const FILE_TYPE_OTHER = 'other';
    
    const USER_DIRECTORY = '/var/www/cdn/users/';

    
    public function removeFile($idFile)
    {
        $this->database->beginTransaction();
        $file = $this->database->query('SELECT PATH, FILENAME FROM file_list WHERE ID_FILE=?', $idFile)->fetch();
        if($file) {
            $connId = $this->getFtpConnection();
            if(ftp_size($connId , '/var/www/cdn/' . $file['PATH'] . '/' . $file['FILENAME']) !== -1) {
                ftp_delete($connId, '/var/www/cdn/' . $file['PATH'] . '/' . $file['FILENAME']);
            }
            $this->deleteFile($idFile);
        }
        $this->database->commit();
        return true;
    }

    public function saveFile($file, $path) 
    {
        $connId = $this->getFtpConnection();
        $createdDirecories = ftp_nlist($connId , self::USER_DIRECTORY . $this->user->getIdentity()->data['URL_ID']);
        if(empty($createdDirecories)) {
            $this->createUserDirectories($connId);
        }
        $date = new \DateTime();
        $timestamp = $date->getTimestamp();
        if (ftp_put($connId, '/var/www/cdn/' . $path . '/' . $timestamp . '_' . $file->getSanitizedName(), $file->getTemporaryFile(), FTP_BINARY)) {
            $return['fileName'] = $file->getName();
            $return['type'] = $file->getContentType();
            $return['fullPath'] = 'https://cdn.lato.cz/' . $path . '/' .  $timestamp . '_' . $file->getSanitizedName();
            return $return; 
        } else {
            return false;
        }
    }
    
    public function uploadFile(Nette\Http\FileUpload $file, $path)
    {
        $connId = $this->getFtpConnection();

        
        $createdDirecories = ftp_nlist($connId , self::USER_DIRECTORY . $this->user->getIdentity()->data['URL_ID']);
        if(empty($createdDirecories)) {
            $this->createUserDirectories($connId);
        }
        
        $date = new \DateTime();
        $timestamp = $date->getTimestamp();

        if (ftp_put($connId, '/var/www/cdn/' . $path . '/' . $timestamp . '_' . $file->getSanitizedName(), $file->getTemporaryFile(), FTP_BINARY)) {
            if(in_array($file->getContentType(), self::FILE_TYPE_DOCUMENT['types'])) {
                $newFile['TYPE'] = self::FILE_TYPE_DOCUMENT['code'];
            } elseif (in_array($file->getContentType(), self::FILE_TYPE_SPREADSHEET['types'])) {
                $newFile['TYPE'] = self::FILE_TYPE_SPREADSHEET['code'];
            } elseif (in_array($file->getContentType(), self::FILE_TYPE_PRESENTASION['types'])) {
                $newFile['TYPE'] = self::FILE_TYPE_PRESENTASION['code'];
            } elseif (in_array($file->getContentType(), self::FILE_TYPE_IMAGE['types'])) {
                $newFile['TYPE'] = self::FILE_TYPE_IMAGE['code'];
            } else {
                $newFile['TYPE'] = self::FILE_TYPE_OTHER;
            }
            $newFile['PATH'] = $path;
            $newFile['MIME'] = $file->getContentType();
            $newFile['FILENAME'] = $timestamp . '_' . $file->getSanitizedName();
            $newFile['EXTENSION'] = pathinfo($file->getSanitizedName(), PATHINFO_EXTENSION);
            $return['idFile'] = $this->saveNewFile($newFile);
            $return['type'] = $newFile['TYPE'];
            $return['fileName'] = $file->getName();
            $return['type'] = $file->getContentType();
            $return['fullPath'] = 'https://cdn.lato.cz/' . $path . '/' . $newFile['FILENAME'];
            return $return; 
        } else {
            return false;
        }
    }

    protected function getFtpConnection()
    {
        $conn_id = ftp_connect('89.221.211.158') or die("Couldn't connect to '185.8.238.199'");
        $login_result = ftp_login($conn_id, 'cdn', 'N0yeA4e');
        return $conn_id;
    }

    protected function createUserDirectories($connId)
    {
        ftp_mkdir($connId, self::USER_DIRECTORY . $this->user->getIdentity()->data['URL_ID']);
        ftp_mkdir($connId, self::USER_DIRECTORY . $this->user->getIdentity()->data['URL_ID'] . '/profile');
        ftp_mkdir($connId, self::USER_DIRECTORY . $this->user->getIdentity()->data['URL_ID'] . '/files');
    }

    protected function saveNewFile($file)
    {
        $this->database->beginTransaction();
        $this->database->table('file_list')->insert(array(
            'TYPE' => $file['TYPE'],
            'EXTENSION' => $file['EXTENSION'],
            'PATH' => $file['PATH'],
            'MIME' => $file['MIME'],
            'FILENAME' => $file['FILENAME']
        ));
        $idFile = $this->database->query('SELECT MAX(ID_FILE) FROM file_list')->fetchField();
        $this->database->commit();
        return $idFile;
    }
    
    protected function deleteFile($idFile)
    {
        $this->database->query('DELETE FROM file_list WHERE ID_FILE=?', $idFile);
    }
}

