<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Group;

use \Nette\Application\UI\Form;
use App\Model\Manager\GroupManager;
use App\FrontModule\Components\NewClassificationForm\NewClassificationForm;
use App\FrontModule\Components\NewClassificationForm\UserClassificationForm;
use App\Model\Manager\ClassificationManager;
use App\Model\Manager\TaskManager;
use App\Model\Manager\UserManager;
use App\Model\Manager\NotificationManager;
use App\Model\Manager\PrivateMessageManager;


/**
 * Description of JoinGroupForm
 *
 * @author Radaq
 */
class Students extends \App\Components\BaseComponent
{
       

    
    public function __construct(
            )
    {
    }


    
    public function handleAddToGroup()
    {
        $this->redrawControl('test');
    }
}
