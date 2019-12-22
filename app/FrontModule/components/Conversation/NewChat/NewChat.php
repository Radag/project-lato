<?php
namespace App\FrontModule\Components\Conversation;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;
use App\Service\ConversationService;

class NewChat extends \App\Components\BaseComponent
{
    
    /** @var UserManager */
    public $userManager;

    /** @var ConversationService */
    public $conversationService;
    
    public function __construct(
        ConversationService $conversationService,
        UserManager $userManager)
    {
        $this->conversationService = $conversationService;
        $this->userManager = $userManager;
    }

     
    protected function createComponentUsersForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->addText('user', 'Jméno nebo e-mail uživatele');
        $form->addHidden('users');
        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = function(Form $form, $values) {
            $attenders = $this->userManager->getMultiple(explode(',', $values->users), false);
            $params = $this->conversationService->getConversationParams($attenders);
            $this->presenter->redirect(':Front:Conversation:default', $params);            
        };
        return $form;
    }
        
    public function handleSearchUsers()
    {
        $term = $this->presenter->getParameter('term');
        $userList = $this->userManager->searchGroupUser($term, [$this->presenter->activeUser->id]);
        if(empty($userList)) {
            $userList = [];
        }
        $this->template->userList = $userList;
        $this->redrawControl('users-list');
    }
}