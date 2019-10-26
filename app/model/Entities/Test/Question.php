<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class Question extends AbstractEntity {
    
    public $id = null;
    public $testId = null;
    public $question = null;
    public $number = null;
    public $type = null;
    public $options = [];
    
     protected $mapFields = [
        'id' => 'id',
        'number' => 'number',
        'question' => 'question',
        'type' => 'type'
    ];
}
