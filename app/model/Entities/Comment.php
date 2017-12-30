<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Model\Entities;
/**
 * Description of Message
 *
 * @author Radaq
 */
class Comment extends AbstractEntity {
    
    public $id = null;
    public $idMessage = null;
    
    public $text = null;
    public $user = null;
    public $created = null;
    public $sinceStart = null;
    
}
