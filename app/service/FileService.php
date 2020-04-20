<?php

namespace App\Service;

use Nette;
use App\Model\Manager\FileManager;
use Nette\Http\FileUpload;
use App\Model\Entities\File;
use App\Model\Entities\ImagePreview;
use App\Model\LatoSettings;
use Lato\FileUploadException;

class FileService 
{
    /** @var User **/
    private $activeUser;
    
    /** @var FileManager **/
    private $fileManager;
        
    public function __construct(
        FileManager $fileManager,
        LatoSettings $latoSettings
    )
    {
        $this->fileManager = $fileManager;
        $this->activeUser = $latoSettings->getUser();
    }
    
        
    const FILE_TYPE_IMAGE = [
        'code' => 'image',
        'types' => [
            'image/svg+xml',
            'image/png',
            'image/jpeg',
            'image/bmp',
            'image/x-windows-bmp',
            'image/gif',
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
    
    const FILE_PURPOSE_STREAM = 'stream';
    const FILE_PURPOSE_COMMIT = 'commit';
    const FILE_PURPOSE_AVATAR = 'avatar';
    
    const USER_DIRECTORY = 'users/';
    const FILES_DIRECTORY = '/files/cdn-lato/';
    
    const STORAGE_LIMIT = 524288000;
    const FILE_LIMIT = 52428800;
     
    const FILE_ADDRESS = 'https://cdn.lato.cz/';    
    
    public function uploadFile(FileUpload $file, $purpose, $restrictions = [], $settings = []) : File
    {
        $this->checkUploadedFile($file, $restrictions);
        $this->createUserDirectories();
        
        if($purpose === self::FILE_PURPOSE_AVATAR) {
            $path = 'users/' . $this->activeUser->slug . '/profile';
        } else {
            $path = 'users/' . $this->activeUser->slug . '/files';
        }
        
        $uploadedFile = new File();
        $uploadedFile->name = $file->getName();
        $uploadedFile->type = $this->getFileType($file->getContentType());
        $uploadedFile->path = $path;
        $uploadedFile->mime = $file->getContentType();
        $uploadedFile->extension = pathinfo($file->getSanitizedName(), PATHINFO_EXTENSION);
        $uploadedFile->fileName = $this->getFileName($file->getSanitizedName());
        $uploadedFile->size = $file->getSize();
        $uploadedFile->fullPath = self::FILE_ADDRESS . $uploadedFile->path . '/' . $uploadedFile->fileName;
        $uploadedFile->purpose = $purpose;
        
		$file->move(self::FILES_DIRECTORY . $path . '/' . $uploadedFile->fileName);
		if($file->isImage()) {
			$this->createImagePreview($file->toImage(), $uploadedFile, $settings);
		} 
        
        $uploadedFile->id = $this->fileManager->saveNewFile($uploadedFile);
        return $uploadedFile;
    }
    
    public function removeFile($idFile)
    {
        $file = $this->fileManager->getFile($idFile);
        if($file) {
            $path = self::FILES_DIRECTORY . $file->path . '/' . $file->fileName;
            if(file_exists($path)) {
                unlink($path);
            }
            $this->fileManager->deleteFile($file);
        }
        return true;
    }    
    
    public static function formatBytes($size) : float
    { 
        $base = log($size) / log(1024);
        $suffix = ["", "KB", "MB", "GB", "TB"];
        $f_base = floor($base);
        return round(pow(1024, $base - floor($base)), 1) . ' ' . $suffix[$f_base];
    }
    
    private function getFileType($contentType) : string
    {
        if(in_array($contentType, self::FILE_TYPE_DOCUMENT['types'])) {
            return self::FILE_TYPE_DOCUMENT['code'];
        } elseif (in_array($contentType, self::FILE_TYPE_SPREADSHEET['types'])) {
            return self::FILE_TYPE_SPREADSHEET['code'];
        } elseif (in_array($contentType, self::FILE_TYPE_PRESENTASION['types'])) {
            return self::FILE_TYPE_PRESENTASION['code'];
        } elseif (in_array($contentType, self::FILE_TYPE_IMAGE['types'])) {
            return self::FILE_TYPE_IMAGE['code'];
        } else {
            return self::FILE_TYPE_OTHER;
        }
    }
    
    private function createImagePreview(Nette\Utils\Image $image, File $file, $settings) : void
    {
        $width = 500;
        $height = 500;
        $method = Nette\Utils\Image::SHRINK_ONLY;
        if($settings) {
            if(isset($settings['preview-width'])) {
                $width = $settings['preview-width'];
            }
            if(isset($settings['preview-height'])) {
                $height = $settings['preview-height'];
            }
            $method = Nette\Utils\Image::EXACT;
        }        
        $image->resize($width, $height, $method);
        $file->preview = new ImagePreview();
        $file->preview->name = 'p_' . $file->fileName;
        $file->preview->fullPath = self::FILE_ADDRESS . $file->path . '/' . $file->preview->name;        
        $image->save(self::FILES_DIRECTORY . $file->path . '/' . $file->preview->name, 80);
    } 
        
    private function checkUploadedFile(FileUpload $file, $restrictions) : void
    {
        if($file->getSize() > self::FILE_LIMIT) {
            throw new FileUploadException('Soubor nesmí být větší než ' . $this->formatBytes(self::FILE_LIMIT));
        } elseif (!$file->getSize() || !$file->isOk()) {
            throw new FileUploadException('Nastala chyba při uploadu souboru: ' . $file->getError());
        }
        if(isset($restrictions['image']) && $restrictions['image'] && !$file->isImage()) {
            throw new FileUploadException('Soubor není podporovaný obrázek');
        } elseif(isset($restrictions['image']) && $restrictions['image']) {
            $image = Nette\Utils\Image::fromFile($file);
            if(isset($restrictions['min-width']) && isset($restrictions['min-height'])
               && ($image->width < $restrictions['min-width'] ||  $image->height < $restrictions['min-height'])
              ) {
                throw new FileUploadException('Velikost obrázku musí být alespoň ' . $restrictions['min-width'] . ' × ' . $restrictions['min-height'] . ' pixelů');
            } 
        }
        if(isset($restrictions['mime']) && is_array($restrictions['mime']) && !in_array($file->contentType, $restrictions['mime'])) {
            throw new FileUploadException('Soubor musí být typu: ' . implode(', ', $restrictions['mime']));
        }        
        if($this->fileManager->isStorageOfLimit($this->activeUser->id)) {
            throw new FileUploadException('Již jste překročili limit úložiště.');
        }        
    }
        
    private function getFileName($name) : string
    {
        $continue = true;
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        while($continue) {
            $filename = Nette\Utils\Random::generate(12) . '.' . $ext;
            $exist = $this->fileManager->getFileByName($name);
            if(!$exist) {
                $continue = false;
            }
        }
        return $filename;
    }
    
    private function createUserDirectories() : void
    {
        $createdDirecories = file_exists(self::FILES_DIRECTORY . self::USER_DIRECTORY . $this->activeUser->slug);
        if(!$createdDirecories) {
            mkdir(self::FILES_DIRECTORY . self::USER_DIRECTORY . $this->activeUser->slug);
            mkdir(self::FILES_DIRECTORY . self::USER_DIRECTORY . $this->activeUser->slug . '/profile');
            mkdir(self::FILES_DIRECTORY . self::USER_DIRECTORY . $this->activeUser->slug . '/files');
        }
    }    
}




/*
class FtpFilesService 
{
    
    private function getFtpConnection()
    {
        return $this->ftpSender->getConnection();
    }
    
    private function createUserDirectories()
    {
        $connId = $this->getFtpConnection();
        $createdDirecories = ftp_nlist($connId , self::FILES_DIRECTORY . self::USER_DIRECTORY . $this->user->getIdentity()->data['slug']);
        if(empty($createdDirecories)) {
            ftp_mkdir($connId, self::USER_DIRECTORY . $this->user->getIdentity()->data['slug']);
            ftp_mkdir($connId, self::USER_DIRECTORY . $this->user->getIdentity()->data['slug'] . '/profile');
            ftp_mkdir($connId, self::USER_DIRECTORY . $this->user->getIdentity()->data['slug'] . '/files');
        }   
    }
    
    public function uploadFile(FileUpload $file, $path, $restrictions, $settings) : File
    {
        ftp_put($connId, self::FILES_DIRECTORY . $path . '/' . $filename, $file->getTemporaryFile(), FTP_BINARY);
    }
    
    public function removeFile($file)
    {
        $connId = $this->getFtpConnection();
        if(ftp_size($connId , self::FILES_DIRECTORY . $file['path'] . '/' . $file['filename']) !== -1) {
            ftp_delete($connId, self::FILES_DIRECTORY . $file['path'] . '/' . $file['filename']);
        } 
    }    
    
}
 *
 */