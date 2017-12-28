<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Group\About;


/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class AboutGroup extends \App\Components\BaseComponent
{
        
    public function render() 
    {
        $this->template->activeGroup = $this->presenter->activeGroup;
        $this->template->groupPermission = $this->presenter->groupPermission;
        parent::render();
    }
    
}
