<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Components\Authetication\TopPanel;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\UserManager;
use App\Model\GroupManager;


/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class TopPanel extends Control
{
    
    /**
     *
     * @var UserManager $userManager
     */
    private $userManager;
    
    
    private $groupManager;
    private $activeGroup;
    
    public function __construct(UserManager $userManager, GroupManager $groupManager, $activeGroup)
    {
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->activeGroup = $activeGroup;
    }
    
    protected function create()
    {
        
    }
    
    public function render()
    {
        $template = $this->template;
        $template->activeGroup = $this->activeGroup;
        $user = $this->getPresenter()->user;
        $template->activeUser = $user->getIdentity()->data;
        $groups = $this->groupManager->getUserGroups($user);
        $subject = array();
        $others = array();
        foreach($groups as $group) {
            if($group->groupType == 1) {
                $subject[] = $group;
            } else {
                $others[] = $group;
            }
        }
        $template->subjects = $subject;
        $template->groups = $others;
        $template->setFile(__DIR__ . '/TopPanel.latte');
        $template->render();
    }
    
}
