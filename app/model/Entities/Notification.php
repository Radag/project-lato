<?php

namespace App\Model\Entities;

class Notification extends AbstractEntity 
{
    
    public $id = null;
    public $title = null;
    public $text = null;
    public $icon = null;
    public $idUser = null;
    public $type = null;
    public $data = null;
    public $isRead = null;
    public $created = null;
    public $triggerUser = null;
    
    protected $mapFields = [
        'id' => 'id',
        'title' => 'title',
        'text' => 'text',
        'created_when' => 'created',
        'is_read' => 'isRead'
    ];
    
}
