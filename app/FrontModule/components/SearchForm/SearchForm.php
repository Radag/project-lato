<?php

namespace App\FrontModule\Components;

use App\Model\Manager\SearchManager;

class SearchForm extends \App\Components\BaseComponent
{
    /** @var SearchManager */
    public $searchManager;
    
    public $searchResults = [];
    
    public function __construct(
        SearchManager $searchManager
    )
    {
        $this->searchManager = $searchManager;  
    }
    
    protected function createComponentForm()
    {
        $form = $this->getForm();
        $form->addText('text', 'Hledat')
             ->setAttribute('placeholder', 'Jméno osoby nebo název skupiny')
             ->setAttribute('autofocus')
             ->setRequired('Napište vyhledávací text');
        $form->addSubmit('send', 'Vyhledat');

        $form->onSuccess[] = [$this, 'processForm'];
        return $form;
    }
    
    public function render()
    {
        $this->template->results = $this->searchResults;
        parent::render();
    }
    
    public function processForm($form, $values) 
    {
        $this->searchResults = $this->searchManager->searchTerm($values->text);
        $this->presenter->payload->invalidForm = false;
        $this->redrawControl();
    }
}
