<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Group\About;

use App\Model\Manager\GroupManager;
/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class AboutGroup extends \App\Components\BaseComponent
{
        
    protected $groupManager;
    
    public function __construct(
        GroupManager $groupManager
    )
    {
        $this->groupManager = $groupManager;
    }
    
    
    public function render() 
    {
        $this->template->periods = $this->groupManager->getGroupPeriods($this->presenter->activeGroup);
        $this->template->activeGroup = $this->presenter->activeGroup;
        $this->template->groupPermission = $this->presenter->groupPermission;
        parent::render();
    }
    
}
