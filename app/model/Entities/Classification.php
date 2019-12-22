<?php

namespace App\Model\Entities;

class Classification extends AbstractEntity 
{
    public $id = null;
    public $idClassificationGroup = null;
    public $idUser = null;
    
    public $user = null;
    public $group = null;
    public $name = null;
    public $grade = null;
    public $lastChange = null;
    public $notice = null;
    public $classificationDate = null;
    public $idPeriod = null;
    public $date = null;
}
