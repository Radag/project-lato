<?php
namespace App\Model\Manager;

use Nette;


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
    
    const STORAGE_LIMIT = 524288000;
    const FILE_LIMIT = 52428800;
     
    
    public function removeFile($idFile)
    {
        $this->db->begin();
        $file = $this->db->fetch('SELECT * FROM file_list WHERE id=?', $idFile);
        if($file) {
            $connId = $this->getFtpConnection();
            if(ftp_size($connId , '/var/www/cdn/' . $file['path'] . '/' . $file['filename']) !== -1) {
                ftp_delete($connId, '/var/www/cdn/' . $file['path'] . '/' . $file['filename']);
            }
            $this->deleteFile($idFile);
        }
        $this->db->commit();
        return true;
    }
    
    public function isFileOwner($idFile, $idUser)
    {
        $file = $this->db->fetch("SELECT id FROM file_list WHERE created_by=? AND id=?", $idUser, $idFile);
        return !empty($file);
    }

    public function isStorageOfLimit($idUser)
    {
        $sum = $this->db->fetchSingle("SELECT SUM(size) FROM file_list WHERE created_by=?", $idUser);
        return ($sum > self::STORAGE_LIMIT);
    }
    
    public function saveFile($file, $path) 
    {
        $connId = $this->getFtpConnection();
        $createdDirecories = ftp_nlist($connId , self::USER_DIRECTORY . $this->user->getIdentity()->data['slug']);
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
        $createdDirecories = ftp_nlist($connId , self::USER_DIRECTORY . $this->user->getIdentity()->data['slug']);
        if(empty($createdDirecories)) {
            $this->createUserDirectories($connId);
        }
        
        $date = new \DateTime();
        $timestamp = $date->getTimestamp();
        $return = [
            'success' => false
        ];
        if($file->getSize() > self::FILE_LIMIT) {
            $return['message'] = 'Soubor nesmí být větší než 50Mb.';
            return $return;
        } elseif (!$file->getSize()) {
            $return['message'] = 'Soubor nesmí být větší než 50Mb.';
            return $return;
        } elseif ($file->getSize()) {
            $filename = $this->getFileName($file);
            if(!\Tracy\Debugger::isEnabled()) {
                $file->move('/var/www/cdn/' . $path . '/' . $filename);
            } else {
                ftp_put($connId, '/var/www/cdn/' . $path . '/' . $filename, $file->getTemporaryFile(), FTP_BINARY);
            }
            
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
            $newFile['SIZE'] = $file->getSize();
            $newFile['FILENAME'] = $filename;
            $newFile['NAME'] = $file->getName();
            $newFile['EXTENSION'] = pathinfo($file->getSanitizedName(), PATHINFO_EXTENSION);
            $newFile['FULLPATH'] = 'https://cdn.lato.cz/' . $path . '/' . $newFile['FILENAME'];
            $return['idFile'] = $this->saveNewFile($newFile);
            $return['type'] = $newFile['TYPE'];
            $return['fileName'] = $file->getName();
            $return['type'] = $file->getContentType();
            $return['fullPath'] = $newFile['FULLPATH'];
            $return['success'] = true;
            return $return; 
        }
    }
    
    public function getFileName(Nette\Http\FileUpload $file)
    {
        $continue = true;
        while($continue) {
            $filename = Nette\Utils\Random::generate(12) . '.' . pathinfo($file->getSanitizedName(), PATHINFO_EXTENSION);
            $exist = $this->db->fetchSingle("SELECT id FROM `file_list` WHERE filename=?", $filename);
            if(!$exist) {
                $continue = false;
            }
        }
        return $filename;
    }

    protected function getFtpConnection()
    {
        return $this->ftpSender->getConnection();
    }

    protected function createUserDirectories($connId)
    {
        ftp_mkdir($connId, self::USER_DIRECTORY . $this->user->getIdentity()->data['slug']);
        ftp_mkdir($connId, self::USER_DIRECTORY . $this->user->getIdentity()->data['slug'] . '/profile');
        ftp_mkdir($connId, self::USER_DIRECTORY . $this->user->getIdentity()->data['slug'] . '/files');
    }

    protected function saveNewFile($file)
    {
        $this->db->query("INSERT INTO file_list", [
            'type' => $file['TYPE'],
            'extension' => $file['EXTENSION'],
            'path' => $file['PATH'],
            'mime' => $file['MIME'],
            'size' => $file['SIZE'],
            'created_by' => $this->user->id,
            'filename' => $file['FILENAME'],
            'full_path' => $file['FULLPATH'],            
            'name' => $file['NAME']
        ]);
        return $this->db->getInsertId();
    }
    
    protected function deleteFile($idFile)
    {
        $this->db->query('DELETE FROM file_list WHERE id=?', $idFile);
    }
    
    public function getUserFiles($userId)
    {
        $totalSize = 0;
        $files = $this->db->fetchAll("SELECT * FROM file_list WHERE created_by=?", $userId);
        foreach($files as $file) {
            $file->format_size = $this->formatBytes($file->size);
            $totalSize += $file->size;
        }
        $total = (object)[
            'current' => $this->formatBytes($totalSize),
            'limit' => $this->formatBytes(self::STORAGE_LIMIT),
            'percent' => round(100/self::STORAGE_LIMIT * $totalSize) 
        ];
        
        return (object)['files' => $files, 'total' => $total];
    }
    
    function formatBytes($size) 
    { 
        $base = log($size) / log(1024);
        $suffix = array("", "KB", "MB", "GB", "TB");
        $f_base = floor($base);
        return round(pow(1024, $base - floor($base)), 1) . ' ' . $suffix[$f_base];
    }
}

