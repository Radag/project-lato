<?php

namespace App\Model\Entities\Test;

use App\Model\Entities\AbstractEntity;

class Test extends AbstractEntity {
    public $id = null;
    public $name = null;
    
    /** @var \App\Model\Entities\User */
    public $author = null;
    public $questions = [];
    public $questionsCount = null;
    public $setupId = null;
    public $timeLimit = null;
    
    public $summary = null;
    
    /** @var TestSetup **/
    public $setup = null;
	
	/** @var \App\Model\Entities\Message **/
    public $message = null;
	
    public $created = null;
    
    protected $mapFields = [
        'id' => 'id',
        'name' => 'name',
        'questions_count' => 'questionsCount',
        'created_at' => 'created',
        'time_limit' => 'timeLimit',   
        'setup_id' => 'setupId'
    ];
}
