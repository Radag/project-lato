<?php

namespace App\PublicModule\Presenters;

use App\PublicModule\Components\IContactForm;

class HomepagePresenter extends BasePresenter
{
    /** @var IContactForm @inject */
    public $contactForm; 
    
    public function actionTerms()
    {
        $this->template->showMainScreen = false;
    }
    
    public function actionGdpr()
    {
        $this->template->showMainScreen = false;
    }
    
    public function createComponentContactForm()
    {
        return $this->contactForm->create();
    }
    
    
}