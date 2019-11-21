<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class Filling extends AbstractEntity {
    public $id = null;
    public $setupId = null;
    public $userId = null;
    public $correctCount = null;
    public $questionsCount = null;
    public $percent = null;
    public $isFinished = null;
    
    /** @var TestSetup **/
    public $setup = null;
    
    /** @var \Datetime **/
    public $created = null;
    public $questions = [];
    public $answers = [];
    
    protected $mapFields = [
        'id' => 'id',
        'setup_id' => 'setupId',
        'group_id' => 'groupId',
        'correct_count' => 'correctCount',
        'questions_count' => 'questionsCount',
        'percent' => 'percent',
        'is_finished' => 'isFinished',
        'created_at' => 'created'
    ];
}
