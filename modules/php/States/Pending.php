<?php

declare(strict_types=1);

namespace Bga\Games\coveryourassets\States;

use Bga\GameFramework\StateType;
use Bga\Games\coveryourassets\Game;

//const ST_END_GAME = 99;

class Pending extends \Bga\GameFramework\States\GameState
{

    function __construct(
        protected Game $game,

    ) {
        parent::__construct(
            $game,
            id: 2,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    /**
     * Game state action, example content.
     *
     * The onEnteringState method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
     */
    public function onEnteringState(int $activePlayerId)
    {


        $pending = $this->game->getObjectFromDB("SELECT * FROM pending ORDER BY id DESC LIMIT 1");

        game::$instance->dump('state Pending', $pending);

        if ($pending == null) {
           
            return 99;
           
        } else {
            // pending is not empty
            $args = $this->game->callPending($pending, false);

            ////////////// attention changement car si on donne la main a un autre joueur sans arg l'id de l active player ne change pas 
            if ($pending['player_id'] != $this->game->getActivePlayerId()) {
                //change active player      
                $this->gamestate->changeActivePlayer($pending['player_id']);
                return Pending::class;
            } else if ($args == null || (count($args['selectable']) == 0 && count($args['buttons']) == 0)) {

                //no args required, execute
                $this->game->callPending($pending, true);
                $this->game->DbQuery("DELETE FROM pending WHERE id = " . $pending['id']);
                return Pending::class;
            } else {
                return NormalTurn::class;
            }
        }
    }
}
