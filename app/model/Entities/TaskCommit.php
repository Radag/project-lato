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
class TaskCommit {
    public $idTask = null;
    public $idCommit = null;
    public $comment = null;
    public $user = null;
    public $files = array();
}
