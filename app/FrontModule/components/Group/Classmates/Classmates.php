<?php

namespace App\FrontModule\Components\Group;

use App\Model\Manager\GroupManager;

class Classmates extends \App\Components\BaseComponent
{
     /** @var GroupManager @inject */
    public $groupManager;
    
    public function __construct(
        GroupManager $groupManager
    )
    {
        $this->groupManager = $groupManager;
    }
    
    public function render() { 
        $members = $this->groupManager->getGroupUsers($this->presenter->activeGroup->id, GroupManager::RELATION_STUDENT, null, [$this->presenter->activeUser->id]);
        $this->template->groupMembers = $members;
        parent::render();
    }
 
}
