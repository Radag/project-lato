<?php
namespace App\PublicModule\Presenters;

use Nette;

class BasePresenter extends Nette\Application\UI\Presenter
{

    public function flashMessage($message, $type = 'info') {
        parent::flashMessage($message, $type);
        $this->redrawControl('flashMessages');
    }
}
