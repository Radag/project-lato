<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class TestSetup extends AbstractEntity {
    
    public $groupId = null;
    public $testId = null;
    public $timeLimit = null;
    public $questionsCount = null;
    public $numberOfRepetitions = null;
    public $sortRandom = false;
    
    protected $mapFields = [
        'group_id' => 'groupId',
        'test_id' => 'testId',
        'time_limit' => 'timeLimit',
        'questions_count' => 'questionsCount',        
        'number_of_repetitions' => 'numberOfRepetitions'
    ];
}
