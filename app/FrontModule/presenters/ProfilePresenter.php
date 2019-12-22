<?php

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Account\IProfile;

class ProfilePresenter extends BasePresenter
{
    /** @var IProfile @inject */
    public $profile;
    
    public function actionDefault($id)
    {
        $this->presenter['topPanel']->setTitle("Můj profil");
        if($id) {
            $this['profile']->setUser($id);
        }
    }
    
    protected function createComponentProfile()
    {
        return $this->profile->create();
    }
}
