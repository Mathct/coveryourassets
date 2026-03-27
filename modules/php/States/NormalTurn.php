<?php

declare(strict_types=1);

namespace Bga\Games\coveryourassets\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\UserException;
use Bga\Games\coveryourassets\Game;

class NormalTurn extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct(
            $game,
            id: 3,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must take an action'),
            descriptionMyTurn: clienttranslate('${you} must take an action'),
            transitions: ['end' => 99],
        );
    }

    /**
     * Game state arguments.
     *
     * This method returns some additional information that is very specific to the `PlayerTurn` game state.
     */
    public function getArgs(): array
    {
        // Get some values from the current game situation from the database.
        // équivalent au argNormalTurn

        $pending =  $this->game->getObjectFromDB("SELECT * FROM pending ORDER BY id DESC LIMIT 1");
        $arg = $this->game->callPending($pending, false);
        return $arg;
    }




    /**
     * Player actions.
     *
     * This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     */


    // #[PossibleAction]
    // public function actPlaceTruck(string $arg1, string $arg2)
    // {
    //     $pending =  $this->game->getObjectFromDB("SELECT * FROM pending ORDER BY id DESC LIMIT 1");
    //     $this->game->callPending($pending, true, $arg1, $arg2);
    //     $this->game->DbQuery("DELETE FROM pending WHERE id = " . $pending['id']);
    //     $this->game->giveExtraTime((int) $this->game->getActivePlayerId());
    //     return Pending::class;
    // }

    #[PossibleAction]
    public function actSelect(string $arg1)
    {
        $pending =  $this->game->getObjectFromDB("SELECT * FROM pending ORDER BY id DESC LIMIT 1");
        $this->game->callPending($pending, true, $arg1);
        $this->game->DbQuery("DELETE FROM pending WHERE id = " . $pending['id']);
        $this->game->giveExtraTime((int) $this->game->getActivePlayerId());
        return Pending::class;
    }

    

    #[PossibleAction]
    public function actButton(string $arg1, string $arg2 = NULL)
    {
        $pending =  $this->game->getObjectFromDB("SELECT * FROM pending ORDER BY id DESC LIMIT 1");
        $this->game->callPending($pending, true, $arg1, $arg2);
        $this->game->DbQuery("DELETE FROM pending WHERE id = " . $pending['id']);
        $this->game->giveExtraTime((int) $this->game->getActivePlayerId());
        return Pending::class;
    }

    

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: play a random card).
     * 
     * See more about Zombie Mode: https://en.doc.boardgamearena.com/Zombie_Mode
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, 
     * but use the $playerId passed in parameter and $this->game->getPlayerNameById($playerId) instead.
     */
    function zombie(int $playerId)
    {
        // Example of zombie level 0: return NextPlayer::class; or $this->actPass($playerId);
        return Pending::class;
        // Example of zombie level 1:
        //$args = $this->getArgs();
        //$zombieChoice = $this->getRandomZombieChoice($args['playableCardsIds']); // random choice over possible moves
        //return $this->actPlayCard($zombieChoice, $playerId, $args); // this function will return the transition to the next state
    }
}
