<?php

namespace App\Model\Entities;

class PrivateMessage extends AbstractEntity 
{
    public $id;
    public $text;
    public $created;
    public $fromMe;
    public $read;
    public $user;
    
    protected $mapFields = [
        'id' => 'id',
        'message' => 'text',
        'created_when' => 'created'
    ];
}
