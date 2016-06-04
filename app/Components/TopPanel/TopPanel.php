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
    
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }
    
    protected function create()
    {
        
    }
    
    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/TopPanel.latte');
        $template->render();
    }
    
}
