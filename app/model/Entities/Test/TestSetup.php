<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class TestSetup extends AbstractEntity {
    
    public $id = null;
    public $testId = null;
    public $groupId = null;
    public $timeLimit = null;
    public $questionsCount = null;
    public $numberOfRepetitions = null;
    public $sortRandom = false;
    
    /** @var \Datetime **/
    public $publicationTime = false;
    
    protected $mapFields = [
        'id' => 'id',
        'group_id' => 'groupId',
        'test_id' => 'testId',
        'time_limit' => 'timeLimit',
        'questions_count' => 'questionsCount',        
        'number_of_repetitions' => 'numberOfRepetitions',
        'publication_time' => 'publicationTime'
    ];
}
