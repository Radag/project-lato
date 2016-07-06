<?php

namespace App\Model\Manager;

use Nette;


/**
 * Users management.
 */
class FileManager extends Nette\Object
{
    
    const FILE_TYPE_IMAGE = 1;
    const FILE_TYPE_OTHER = 2;
    
    const USER_DIRECTORY = '/cdn/users/';
    private $user;

    /** @var Nette\Database\Context */
    private $database;


    public function __construct(Nette\Database\Context $database,
                    Nette\Security\User $user
            )
    {
            $this->database = $database;
            $this->user = $user;
    }


    public function uploadFile(Nette\Http\FileUpload $file, $path)
    {
        $connId = $this->getFtpConnection();

        //$this->createUserDirectories($connId);

        


        $date = new \DateTime();
        $timestamp = $date->getTimestamp();

        if (ftp_put($connId, '/cdn/' . $path . '/' . $timestamp . '_' . $file->getSanitizedName(), $file->getTemporaryFile(), FTP_BINARY)) {
            if($file->isImage()) {
                $newFile['ID_FILE'] = self::FILE_TYPE_IMAGE;
            } else {
                $newFile['ID_FILE'] = self::FILE_TYPE_OTHER;
            }
            
            $newFile['PATH'] = $path;
            $newFile['FILENAME'] = $timestamp . '_' . $file->getSanitizedName();
            $return['idFile'] = $this->saveNewFile($newFile);
            $return['fileName'] = $file->getName();
            return $return; 
        } else {
            return false;
        }
    }

    protected function getFtpConnection()
    {
        $conn_id = ftp_connect('185.8.238.199') or die("Couldn't connect to '185.8.238.199'");
        $login_result = ftp_login($conn_id, 'petr', 'petricek3');
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
            'ID_TYPE' => $file['ID_FILE'],
            'PATH' => $file['PATH'],
            'FILENAME' => $file['FILENAME']
        ));
        $idFile = $this->database->query('SELECT MAX(ID_FILE) FROM file_list')->fetchField();
        $this->database->commit();
        return $idFile;
    }

}

