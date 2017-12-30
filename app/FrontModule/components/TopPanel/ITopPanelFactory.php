<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\TopPanel;


interface ITopPanelFactory
{
    /**
     * @return TopPanel 
    */
    public function create();
    
}
