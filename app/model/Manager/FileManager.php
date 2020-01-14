<?php
namespace App\Model\Manager;

use App\Model\Entities\File;
use App\Service\FileService;

class FileManager extends BaseManager
{
    
    public function isFileOwner($idFile, $idUser) : bool
    {
        $file = $this->db->fetchSingle("SELECT id FROM file_list WHERE created_by=? AND id=?", $idUser, $idFile);
        return !empty($file);
    }
    
    public function isStorageOfLimit($idUser) : bool
    {
        $sum = $this->db->fetchSingle("SELECT SUM(size) FROM file_list WHERE created_by=?", $idUser);
        return ($sum > FileService::STORAGE_LIMIT);
    }

    public function saveNewFile(File $file) : int
    {
        $this->db->query("INSERT INTO file_list", [
            'type' => $file->type,
            'extension' => $file->extension,
            'path' => $file->path,
            'mime' => $file->mime,
            'size' => $file->size,
            'filename' => $file->fileName,
            'full_path' => $file->fullPath,  
            'purpose' => $file->purpose,
            'name' => $file->name,
            'created_by' => $this->settings->getUser()->id
        ]);
        $fileId = $this->db->getInsertId();
        if($file->preview) {
            $this->db->query("INSERT INTO file_list_preview", [
                'file_id' => $fileId,
                'preview_name' => $file->preview->name,
                'preview_full_path' => $file->preview->fullPath
            ]);
        }
        return $fileId;
    }
    
    public function deleteFile(File $file) : void
    {
        $this->db->query('DELETE FROM file_list WHERE id=?', $file->id);
    }
    
    public function getUserFiles($userId)
    {
        $totalSize = 0;
        $files = $this->db->fetchAll("SELECT * FROM file_list WHERE created_by=?", $userId);
        foreach($files as $file) {
            $file->format_size = FileService::formatBytes($file->size);
            $totalSize += $file->size;
        }
        $total = (object)[
            'current' => FileService::formatBytes($totalSize),
            'limit' => FileService::formatBytes(FilesService::STORAGE_LIMIT),
            'percent' => round(100/FilesService::STORAGE_LIMIT * $totalSize) 
        ];
        
        return (object)['files' => $files, 'total' => $total];
    }
    
    public function getFile($id) : ?File
    {
        $file = $this->db->fetch("SELECT * FROM file_list WHERE id=?", $id);
        if(!$file) {
            return null;
        }
        return new File($file);    
    }
    
    public function getFileByName($name) : ?File
    {
        $file = $this->db->fetch("SELECT * FROM file_list WHERE filename=?", $name);
        if(!$file) {
            return null;
        }
        return new File($file);
    }
    
    public function getUnusedUserFiles(string $purpose)
    {
        $files = [];
        if($purpose === FileService::FILE_PURPOSE_STREAM) {
            $sql = "SELECT T1.* FROM file_list T1
                   LEFT JOIN message_attachment T2 ON T1.id=T2.file_id
                   WHERE T1.purpose='stream' AND T2.id IS NULL AND T1.created_by=?";
            $filesData = $this->db->fetchAll($sql, $this->settings->getUser()->id);
            foreach($filesData as $file) {
                $files[] = new File($file);
            }
        }
        return $files;
    }
}

