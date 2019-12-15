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
    public $isCreator = false;
    public $timeLeft = null;
    public $classificationGroupId = null;
    public $randomSort = null;
    public $canLookAtResults = null;
    
    /** @var \App\Model\Entities\Group **/
    public $group = null;
    
    /** @var \Datetime **/
    public $publicationTime = null;
    
    /** @var \Datetime **/
    public $deadline = null;
    
    protected $mapFields = [
        'id' => 'id',
        'group_id' => 'groupId',
        'test_id' => 'testId',
        'time_limit' => 'timeLimit',
        'questions_count' => 'questionsCount',        
        'number_of_repetitions' => 'numberOfRepetitions',
        'deadline' => 'deadline',
        'publication_time' => 'publicationTime',        
        'random_sort' => 'randomSort',
        'classification_group_id' => 'classificationGroupId',
        'can_look_at_results' => 'canLookAtResults',
    ];
}
