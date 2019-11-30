<?php
namespace App\Service;

use App\Model\Manager\TestManager;
use Nette\Application\UI\Form;
use App\Model\Entities\Test\Test;
use App\Model\Entities\Test\Filling;
use App\Model\Entities\Test\Answer;
use App\Model\Entities\User;
use App\Model\Entities\Test\TestSetup;
use App\Model\LatoSettings;

class TestService
{
    /** @var User **/
    private $activeUser;
    
    /** @var TestManager **/
    private $testManager;
        
    public function __construct(
        TestManager $testManager,
        LatoSettings $latoSettings
    )
    {
        $this->testManager = $testManager;
        $this->activeUser = $latoSettings->getUser();
    }

    public function getTestForm(Form $form, Test $test, Filling $filling)
    {
        foreach($test->questions as $question) {
            if(in_array($question->id, $filling->questions)) {
                foreach($question->options as $option) {
                    $form->addCheckbox('opt_' . $question->id . '_' . $option->id);
                }
            }
        }
    }
    
    public function saveTestForm($values, Filling $filling, Test $test, bool $finish)
    {
        $this->testManager->clearTestAnswers($filling->id);
        $questions = $this->testManager->getTestForUser($filling->setup->testId);
        
        $answers = [];
        foreach($values as $key => $val) {
            $name = explode('_', $key);
            if($name[0] === 'opt') {
                if(!isset($answers[$name[1]])) {
                    $answer = new Answer();
                    $answer->fillingId = $filling->id;
                    $answer->questionId = $name[1];
                    $answer->question = $questions->questions[$name[1]];
                    if($answer->question->type === 'options') {
                        $answer->answer = (object)['options' => []];
                    }
                    $answers[$name[1]] = $answer;
                }
                if($answers[$name[1]]->question->type === 'options' && $val) {
                    $answer->answer->options[] = (int)$name[2];
                }
            }
        }
        
        $correctCount = 0;
        foreach($answers as $answer) {
            $answer->isCorrect = $this->isCorrect($answer);
            if($answer->isCorrect) {
                $correctCount++;
            }
            $this->testManager->saveAnswer($answer);
        }
        
        if($finish) {
            $filling->isFinished = true;
            $filling->correctCount = $correctCount;
            $filling->percent = round(100/$test->questionsCount * $correctCount);
            $this->testManager->updateFilling($filling);
        }
    }
    
    private function isCorrect(Answer $answer)
    {
        if($answer->question->type === 'options') {
            $correct = [];
            foreach($answer->question->options as $option) {
                if($option->isCorrect) {
                    $correct[] = $option->id;
                }                
            }
            sort($correct);
            sort($answer->answer->options);
            if($correct == $answer->answer->options) {
                return true;
            }
        }
        return false;        
    }
}
