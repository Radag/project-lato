<?php
namespace App\FrontModule\Components\Group;

use App\FrontModule\Components\Group\AddUserForm\IInviteForm;
use App\FrontModule\Components\Group\AddUserForm\IImportForm;
use App\FrontModule\Components\Group\AddUserForm\ICreateForm;

class AddUserForm extends \App\Components\BaseComponent
{
    /** @var IInviteForm */
    protected $inviteForm;
    
    /** @var IImportForm */
    protected $importForm;
    
    /** @var ICreateForm */
    protected $createForm;

    public function __construct(
        IInviteForm $inviteForm,
        IImportForm $importForm,
        ICreateForm $createForm
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
