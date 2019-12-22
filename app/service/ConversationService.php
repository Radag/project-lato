<?php

namespace App\Service;

use App\Model\LatoSettings;
use App\Model\Manager\ConversationManager;

class ConversationService 
{
     /** @var LatoSettings **/
    private $settings;
    
    /** @var ConversationManager **/
    private $conversationManager;
        
    public function __construct(
        ConversationManager $conversationManager,
        LatoSettings $latoSettings
    )
    {
        $this->conversationManager = $conversationManager;
        $this->settings = $latoSettings;
    }
    
    public function getConversationParams($attenders) : array
    {
        $slugIds = $ids =[];
        foreach($attenders as $att) {
            $ids[] = $att->id;
            $slugIds[] = $att->slug;
        }
        $ids[] = $this->settings->getUser()->id;
        sort($ids);
        $exist = $this->conversationManager->conversationExist($ids);
        if($exist) {
            return ['id' => $exist->id];
        } else {
            return ['users' => implode(',', $slugIds)];
        }
    }
}
