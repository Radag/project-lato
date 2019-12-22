<?php
namespace App\FrontModule\Components\Archive;

use App\Model\Manager\GroupManager;

class Groups extends \App\Components\BaseComponent
{
    /** @var GroupManager @inject */
    public $groupManager;
    
    public function __construct(
        GroupManager $groupManager
    )
    {
        $this->groupManager = $groupManager;
    }
    
    public function render()
    {
        $this->template->groups = $this->groupManager->getUserGroups($this->presenter->activeUser, (object)['only_archived' => true]);
        parent::render();
    }
    
    
}
