<?php

namespace App\Model\Entities;

class File extends AbstractEntity 
{
    public $id = null;
    public $type = null;
    public $name = null;
    public $path = null;
    public $mime = null;
    public $size = null;
    public $extension = null;
    public $fileName = null;
    public $fullPath = null;
    public $purpose = null;
    
    /** @var \Datetime **/
    public $created = null;
    
    /** @var ImagePreview **/
    public $preview;
    
    protected $mapFields = [
        'id' => 'id',
        'name' => 'name',
        'type' => 'type',
        'path' => 'path',
        'mime' => 'mime',
        'size' => 'size',
        'full_path' => 'fullPath',
        'filename' => 'fileName',
        'created_when' => 'created',
        'purpose' => 'purpose'
    ];    
}

class ImagePreview extends AbstractEntity 
{
    public $name = null;
    public $fullPath = null;
}
