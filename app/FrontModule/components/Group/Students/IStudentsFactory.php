<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Group;

use App\FrontModule\Components\Group\Students;

/**
 *
 * @author Radaq
 */
interface IStudentsFactory
{
    /**
     * @return Students 
    */
    public function create();
    
}
