<?php

namespace App\PublicModule\Presenters;

use App\PublicModule\Components\IContactForm;

class HomepagePresenter extends BasePresenter
{
    /** @var IContactForm @inject */
    public $contactForm; 
    
    public function actionTerms()
    {
        $this->template->menuItems = [
            '#menu1' => 'Definice',
            '#menu2' => 'Obecná ustanovení',
            '#menu3' => 'Užívání Aplikace',
            '#menu4' => 'Odpovědnost za škodu',
            '#menu5' => 'Ochrana osobních údajů',
            '#menu6' => 'Závěrečná ustanovení'
        ];
        $this->template->showMainScreen = false;
    }
    
    public function actionGdpr()
    {
        $this->template->menuItems = [
            '#menu1' => 'Správce osobních údajů',
            '#menu2' => 'Zpracovávané údaje',
            '#menu3' => 'Cookies',
            '#menu4' => 'Informace a otázky'
        ];
        $this->template->showMainScreen = false;
    }
    
    public function createComponentContactForm()
    {
        return $this->contactForm->create();
    }
    
    
}