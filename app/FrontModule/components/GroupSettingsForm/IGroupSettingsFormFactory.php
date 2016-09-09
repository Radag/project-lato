<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\GroupSettingsForm;


/**
 * Description of SignInForm
 *
 * @author Radaq
 */
interface IGroupSettingsFormFactory
{
    /**
    * @return GroupSettingsForm 
    */
    public function create();
}
