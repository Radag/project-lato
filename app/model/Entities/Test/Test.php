<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class Test extends AbstractEntity {
    public $id = null;
    public $name = null;
    public $questions = [];
    public $questionsCount = null;
    public $created = null;
    
    protected $mapFields = [
        'id' => 'id',
        'name' => 'name',
        'questions_count' => 'questionsCount',
        'created_at' => 'created'
    ];
}
