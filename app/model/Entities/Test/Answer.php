<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class Answer extends AbstractEntity {
    
    public $id = null;
    public $fillingId = null;
    public $questionId = null;
    public $optionId = null;
    public $answerBinary = null;
    public $isCorrect = null;
    public $created = null;
    
    protected $mapFields = [
        'id' => 'id',
        'test_filling_id' => 'fillingId',
        'question_id' => 'questionId',
        'option_id' => 'optionId',
        'answer_binary' => 'answerBinary',
        'is_correct' => 'isCorrect',
        'created_at' => 'created'
    ];
}
