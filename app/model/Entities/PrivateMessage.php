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
class PrivateMessage extends AbstractEntity {
    public $id;
    public $text;
    public $idUserFrom;
    public $idUserTo;
    public $created;
    public $fromMe;
    public $read;
}
