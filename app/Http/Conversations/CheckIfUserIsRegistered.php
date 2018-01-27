<?php

namespace App\Http\Conversations;

use App\User;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

class CheckIfUserIsRegistered extends Conversation
{
    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        $this->askForRegistration();
    }

    /**
     * [askForRegistration description]
     * @return [type] [description]
     */
    public function askForRegistration()
    {
    	$question = Question::create("Have you already registered?")
            ->fallback('Unable to ask question')
            ->callbackId('ask_for_registration')
    		->addButtons([
                Button::create('Yes')->value('yes'),
                Button::create('No')->value('no'),
            ]);

    	return $this->ask($question, function (Answer $response) {
            $answer = $response->isInteractiveMessageReply() 
                            ? $response->getValue() 
                            : $response->getText();

       		if ($answer === 'no') {
       			$this->sayRegisterRoute();
       		} else if ($answer === 'yes') {
       			$this->askForMatchingAccounts();
       		} else {
                $this->bot->say("Sorry I can't understand what you are saying!");
            }
    	});
    }

    /**
     * [askForMatchingAccounts description]
     * @return [type] [description]
     */
    public function askForMatchingAccounts()
    {
        $botUser = $this->bot->getUser();

        $possibleAccounts = User::messenger($botUser)->get();

        if ($possibleAccounts->count() == 0) {
            $this->ask("I couldn't find any user matching your messenger's data. What is the username you registered with?", function (Answer $answer) {
                $user = User::whereUsername($answer->getValue())->first();

                if (! $user) {
                    $this->say("Sorry, still couldn't find any account.");
                    return $this->sayRegisterRoute();
                }

                $this->linkMessengerToUserAccount($user);             
            });
        }
    }

    /**
     * [sayRegisterRoute description]
     * @return [type] [description]
     */
    public function sayRegisterRoute($user)
    {
        $this->say('Please go to '.route('register').'!');
    }

    /**
     * [linkMessengerToUserAccount description]
     * @return [type] [description]
     */
    public function linkMessengerToUserAccount()
    {
        $question = Question::create("Ok I will link you with {$user->username}`s account.")
            ->callbackId('link_messenger_confirmation')
            ->addButtons([
                Button::create('Sure')->value('yes'),
                Button::create('Nope')->value('no'),
            ]);

        $this->ask($question, function (Answer $answer) {
            $answer = $response->isInteractiveMessageReply() 
                            ? $response->getValue() 
                            : $response->getText();

            if ($answer === 'yes') {
                $user->fill(['messenger_id' => $botUser->getId()])->save();

                $this->say('Ok - you got linked!');
            } else {
                $this->say('Ok!');
            }
        });
    }
}