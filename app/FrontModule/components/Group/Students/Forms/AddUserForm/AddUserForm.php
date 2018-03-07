<?php
namespace App\FrontModule\Components\Group;

use App\FrontModule\Components\Group\AddUserForm\InviteFormFactory;
use App\FrontModule\Components\Group\AddUserForm\ImportFormFactory;

class AddUserForm extends \App\Components\BaseComponent
{
    /** @var InviteFormFactory */
    protected $inviteForm;
    
    /** @var ImportFormFactory */
    protected $importForm;

    public function __construct(
        InviteFormFactory $inviteForm,
        ImportFormFactory $importForm
    )
    {
        $this->inviteForm = $inviteForm;
        $this->importForm= $importForm;
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
        return new AddUserForm\CreateForm();
    }
}
