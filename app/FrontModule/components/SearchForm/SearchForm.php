<?php

namespace App\FrontModule\Components;

use \Nette\Application\UI\Control;

use App\Model\Manager\SearchManager;

class SearchForm extends Control
{
    protected  $searchManager;
    public $searchResults = array();
    
    
    public function __construct(SearchManager $searchManager)
    {
        $this->searchManager = $searchManager;
        
    }
    
    protected function createComponentForm()
    {
        $form = new \Nette\Application\UI\Form;
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
        $template = $this->template;
        $template->results = $this->searchResults;
        $template->setFile(__DIR__ . '/SearchForm.latte');
        $template->render();
    }
    
    public function processForm($form, $values) 
    {
        $this->searchResults = $this->searchManager->searchTerm($values->text);
        $this->redrawControl(); 
    }
}
