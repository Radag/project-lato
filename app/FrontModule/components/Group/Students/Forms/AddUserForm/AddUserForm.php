<?php
namespace App\FrontModule\Components\Group;

use App\FrontModule\Components\Group\AddUserForm\InviteFormFactory;
use App\FrontModule\Components\Group\AddUserForm\ImportFormFactory;
use App\FrontModule\Components\Group\AddUserForm\ICreateFormFactory;

class AddUserForm extends \App\Components\BaseComponent
{
    /** @var InviteFormFactory */
    protected $inviteForm;
    
    /** @var ImportFormFactory */
    protected $importForm;
    
    /** @var ICreateFormFactory */
    protected $createForm;

    public function __construct(
        InviteFormFactory $inviteForm,
        ImportFormFactory $importForm,
        ICreateFormFactory $createForm
    )
    {
        $this->inviteForm = $inviteForm;
        $this->importForm = $importForm;
        $this->createForm = $createForm;
    }
    
    public function render() {
        $this->template->activeGroup = $this->presenter->activeGroup;
        parent::render();
    }
    
    public function createComponentInviteUserForm()
    {
        return $this->inviteForm->create();
    }
    
    public function createComponentImportUserForm()
    {
        return $this->importForm->create();
    }
    
    public function createComponentCreateUserForm()
    {
        return $this->createForm->create();
    }
}
