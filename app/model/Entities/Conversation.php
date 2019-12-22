<?php

namespace App\Model\Entities;

class Conversation extends AbstractEntity 
{
    
    public $id;
    public $created;
    public $diffDays;
    public $read = false;
    public $user;
    public $text = '';
    public $lastIsMme = null;
    
    protected $mapFields = [
        'conv_id' => 'id',
        'conv_created_when' => 'created',
        'message' => 'text',
        'read' => 'read',
        'last_is_me' => 'lastIsMme'
    ];
}
