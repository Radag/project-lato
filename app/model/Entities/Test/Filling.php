<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class Filling extends AbstractEntity {
    public $id = null;
    public $userId = null;
    public $testId = null;
    public $groupId = null;
    public $correctCount = null;
    public $questionsCount = null;
    public $percent = null;
    public $isFinished = null;
    public $created = null;
    public $answers = [];
    
    protected $mapFields = [
        'id' => 'id',
        'user_id' => 'userId',
        'test_id' => 'testId',
        'groupId' => 'groupId',
        'correct_count' => 'correctCount',
        'questions_count' => 'questionsCount',
        'percent' => 'percent',
        'is_finished' => 'isFinished',
        'created_at' => 'created'
    ];
}
