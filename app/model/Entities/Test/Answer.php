<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class Answer extends AbstractEntity {
    
    public $id = null;
    public $fillingId = null;
    public $questionId = null;
    public $isCorrect = null;
    public $created = null;    
    public $answer = null;  
    public $question = null;
    
    protected $mapFields = [
        'id' => 'id',
        'test_filling_id' => 'fillingId',
        'question_id' => 'questionId',
        'answer' => 'answer',
        'is_correct' => 'isCorrect',
        'created_at' => 'created'
    ];
}
