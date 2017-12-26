<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Stream\MessageForm\HomeworkForm;


/**
 *
 * @author Radaq
 */
interface IHomeworkFormFactory
{
    /**
     * @return HomeworkForm
     */
    public function create();
    
}
