<?php
namespace App\FrontModule\Components\PrivateMessageForm;

use \Nette\Application\UI\Form;
use App\Model\Manager\UserManager;
use App\Model\Manager\ConversationManager;


class PrivateMessageForm extends \App\Components\BaseComponent
{
    /** @var ConversationManager */
    public $conversationManager;
    
    /** @var UserManager */
    public $userManager;

    public function __construct(
        ConversationManager $conversationManager,
        UserManager $userManager)
    {
        $this->conversationManager = $conversationManager;
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
            $slugIds = $ids =[];
            foreach($attenders as $att) {
                $ids[] = $att->id;
                $slugIds[] = $att->slug;
            }
            $ids[] = $this->presenter->activeUser->id;
            sort($ids);
            $exist = $this->conversationManager->conversationExist($ids);
            if($exist) {
                $this->presenter->redirect(':Front:Conversation:default', ['id' => $exist->id]);
            } else {
                $this->presenter->redirect(':Front:Conversation:default', ['users' => implode(',', $slugIds)]);
            }
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