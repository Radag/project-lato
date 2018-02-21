<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\Entities;

/**
 * Description of PrivateMessage
 *
 * @author Radaq
 */
class Conversation extends AbstractEntity {
    
    public $id;
    public $created;
    public $diffDays;
    public $read = false;
    public $user;
    public $text = '';
    
    protected $mapFields = [
        'conv_id' => 'id',
        'conv_created_when' => 'created'
    ];
}
