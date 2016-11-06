<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\FrontModule\Components\Account\NotificationSettings;

use \Nette\Application\UI\Form;
use \Nette\Application\UI\Control;
use App\Model\Manager\NotificationManager;
use App\Model\Entities\User;

/**
 * Description of SignInForm
 *
 * @author Radaq
 */
class NotificationSettings extends Control
{
    /**
     * @var User $activeUser
     */
    protected $activeUser;
    
    /**
     * @var NotificationManager $notificationManager
     */
    protected $notificationManager;
    
    protected $notificationTypes = array();
    protected $notificationSettings = array();
    
    public function __construct(User $activeUser,NotificationManager $notificationManager)
    {
        $this->activeUser = $activeUser;  
        $this->notificationManager = $notificationManager;
        $this->notificationTypes = $this->notificationManager->getNotificationTypes();
        $this->notificationSettings = $this->notificationManager->getNotificationSettings($this->activeUser);
    }
    
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
        
        foreach($this->notificationTypes as  $type) {
            $form->addCheckbox('notification_' . $type->ID_TYPE, '');
            $form->addCheckbox('mail_' . $type->ID_TYPE, '');
        }
        
        foreach($this->notificationSettings as  $setting) {
            $form['notification_' . $setting->ID_TYPE]->setValue($setting->SHOW_NOTIFICATION);
            $form['mail_' . $setting->ID_TYPE]->setValue($setting->SEND_BY_EMAIL);
        }
        
        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = function($form, $values) {
            $valArry = array();
            foreach($values as $key=>$val) {
                $a = explode('_', $key);
                $valArry[$a[1]][$a[0]] = $val ? 1 : 0;  
            }
            foreach($valArry as $id=>$k) {
                $this->notificationManager->setSettings($this->activeUser, $id, $k['mail'], $k['notification']);
            }
            
            $this->flashMessage('Nastavení notifikací uloženo', 'success');
            $this->redirect('this');
        };
        return $form;        
    }
    
    public function render()
    {
        $template = $this->template;
        $template->types = $this->notificationTypes;
        $template->setFile(__DIR__ . '/NotificationSettings.latte');
        $template->render();
    }
    
  
}
