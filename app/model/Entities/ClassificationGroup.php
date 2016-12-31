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
class ClassificationGroup {
    public $idClassificationGroup = null;
    public $group = null;
    public $name = null;
    public $task = null;
    public $classifications = array();
    public $classificationDate = null;
}
