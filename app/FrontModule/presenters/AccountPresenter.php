<?php

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Account\IAccountSettings;

class AccountPresenter extends BasePresenter
{
    
    /** @var IAccountSettings @inject */
    public $accountSettings;

    public function actionDefault()
    {
        $this['topPanel']->setTitle('Nastavení');
        $this->setView('default');
    }

    public function createComponentAccountSettings()
    {
        return $this->accountSettings->create();
    } 
    
}
