<?php

namespace Bga\Games\coveryourassets;   // ATTENTION NOM DU JEU

//require_once 'PendingConfirm.php'; // ATTENTION

use Bga\GameFramework\UserException;
use Bga\GameFramework\NotificationMessage;

class Pending extends Game
{
    //use PendingConfirmTrait; // ATTENTION TRAIT

    public $player_id;
    public $player_no;
    public $player_name;
    public $player_score;
    public $player_color;
    public $player_pref_confirm;

    public function __construct($player_id)
    {
        $this->player_id = $player_id;
        $p = game::$instance->getObjectFromDB("SELECT * FROM player WHERE player_id = {$player_id}");
        $this->player_no = $p['player_no'];
        $this->player_id = $p['player_id'];
        $this->player_name = $p['player_name'];
        $this->player_score = $p['player_score'];
        $this->player_color = $p['player_color'];

        /// PREFERENCE DE CONFIRMATION
        //$this->player_pref_confirm = game::$instance->userPreferences->get($this->player_id, 100);
    }

    /*
     _______               
    |__   __|              
        | |_   _ _ __ _ __  
        | | | | | '__| '_ \ 
        | | |_| | |  | | | |
        |_|\__,_|_|  |_| |_|

    */


    function argPlayerTurn($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must ...');
        $ret['titleyou'] = clienttranslate('${you} must ...');

        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
        $assetCounts = [];
        $jokerCount = 0;
        foreach ($handCards as $card) {
            $ret["selectablemulti"][] = 'my_cards_item_' . $card['id'];
            $type = (int) $card['type'];
            if ($type >= 1 && $type <= 10) {
                if (!isset($assetCounts[$type])) {
                    $assetCounts[$type] = 0;
                }
                $assetCounts[$type]++;
            } else if ($type === 11 || $type === 12) {
                $jokerCount++;
            }
        }

        $canCreateSet = false;
        foreach ($assetCounts as $count) {
            if ($count >= 2) {
                $canCreateSet = true;
                break;
            }
        }
        if (!$canCreateSet && $jokerCount > 0 && count($assetCounts) > 0) {
            $canCreateSet = true;
        }

        if ($canCreateSet) {
            $ret['buttons'][] = 'create_set_btn';
        }

        $ret['buttons'][] = 'discard_btn';


        $last_set = [];
        $players = game::$instance->getObjectListFromDB( "SELECT `player_id` FROM `player`", true );

        foreach ($players as $player)
        {
            
            $sets = game::$instance->getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' AND position >= 1 ORDER BY position DESC, card_type ASC");
            
            foreach ($sets as $card) {
                // On garde la première carte rencontrée par position
                if (!isset($last_set[$player])) {
                    $last_set[$player] = $card;
                }
            }
    
        }

       

        $possible_challenge = 0;
        $first_set = game::$instance->getUniqueValueFromDB("SELECT first_set FROM player WHERE player_id = '{$this->player_id}'");
        if($first_set >= 1)
        {

            foreach ($players as $player)
            {
                if($player != $this->player_id)
                {
                    if (isset($last_set[$player])) {
                        $type_last_set = $last_set[$player]['type'];

                        foreach ($handCards as $card) {
                            $type = (int) $card['type'];
                            if ($type == $type_last_set || $type === 11 || $type === 12) {
                                $possible_challenge = 1;
                            } 
                        }


                    }
                }
            
            }
            
            if($possible_challenge == 1)
            {
                $ret['buttons'][] = 'challenge_btn';
            }

           
        }

        

        


        return $ret;
    }



    function PlayerTurn($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;
        $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

        if($varg1 == 'create_set_btn') {

            $ids = explode('_', $varg2);
            $id1 = (int) $ids[0];
            $id2 = (int) $ids[1];

            $cards = $g->getObjectListFromDB(
                "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM cards WHERE card_id IN ({$id1}, {$id2})"
            );

            $maxSetPosition = (int) $g->getUniqueValueFromDB(
                "SELECT COALESCE(MAX(`position`), 0) FROM cards WHERE card_location = 'set' AND card_location_arg = {$this->player_id}"
            );
            $newSetPosition = $maxSetPosition + 1;

            $g->cards_DB->moveCard($id1, 'set', $this->player_id);
            $g->cards_DB->moveCard($id2, 'set', $this->player_id);

            $g->DbQuery(
                "UPDATE cards SET `position` = {$newSetPosition} WHERE card_id IN ({$id1}, {$id2})"
            );

            
            $txt = clienttranslate('${player_name} set ....');
            $g->notify->all(
                "cardsMovedToTable",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'set_position' => $newSetPosition,
                    'cards' => $cards,
                ]
            );

            if($count_deck > 0) {
                if($count_deck >= 2) {
                    $newcards = $g->cards_DB->pickCards(2, 'deck', $this->player_id);
                }
                if($count_deck == 1) {
                    $newcards = $g->cards_DB->pickCard('deck', $this->player_id);
                }

                $g->notify->player(
                    $this->player_id,
                    "drawCards",
                    '',
                    [
                        'player_id' => $this->player_id,
                        'cards' => $newcards,
                    ]
                );
            }


            $g->DbQuery(
                "UPDATE player SET `first_set` = 1 WHERE player_id = '{$this->player_id}'"
            );

            $g->addPendingFirst($this->player_id, "PlayerTurn");
        }

        if($varg1 == 'discard_btn') {
            $ids = explode('_', $varg2);
            $id1 = (int) $ids[0];

            $card = $g->cards_DB->getCard($id1);

            $maxDiscardPosition = (int) $g->getUniqueValueFromDB(
                "SELECT COALESCE(MAX(`position`), 0) FROM cards WHERE card_location = 'discard' AND card_location_arg = {$this->player_id}"
            );
            $newDiscardPosition = $maxDiscardPosition + 1;

            $g->DbQuery(
                "UPDATE cards SET `position` = {$newDiscardPosition} WHERE card_id = {$id1}"
            );

            $g->cards_DB->moveCard($id1, 'discard', 0);

            $txt = clienttranslate('${player_name} discarded ....');
            $g->notify->all(
                "cardsMovedToDiscard",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'card' => $card,
                ]
            );

            if($count_deck >= 1) {
                $newcards = $g->cards_DB->pickCards(1,'deck', $this->player_id);
                $g->notify->player(
                    $this->player_id,
                    "drawCards",
                    '',
                    [
                        'player_id' => $this->player_id,
                        'cards' => $newcards,
                    ]
                );
            }

            $g->addPendingFirst($this->player_id, "PlayerTurn");
        }

        if($varg1 == 'challenge_btn') {

            $g->addPending($this->player_id, "ChallengeStep1");
        }

        
    }


    /* choisir l'adversaire */
    function argChallengeStep1($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must ...');
        $ret['titleyou'] = clienttranslate('${you} must ...');

        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
        
        $last_set = [];
        $players = game::$instance->getObjectListFromDB( "SELECT `player_id` FROM `player`", true );

        foreach ($players as $player)
        {
            
            $sets = game::$instance->getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' AND position >= 1 ORDER BY position DESC, card_type ASC");
            
            foreach ($sets as $card) {
                // On garde la première carte rencontrée par position
                if (!isset($last_set[$player])) {
                    $last_set[$player] = $card;
                }
            }
    
        }

        $players_challenge = [];
       
        foreach ($players as $player)
        {
            if($player != $this->player_id)
            {
                if (isset($last_set[$player])) {
                    $type_last_set = $last_set[$player]['type'];

                    foreach ($handCards as $card) {
                        $type = (int) $card['type'];
                        if ($type == $type_last_set || $type === 11 || $type === 12) {
                            if (!in_array($player, $players_challenge))
                            {
                                $players_challenge[] = $player;
                            }
                        } 
                    }


                }
            }
        
        }


        foreach ($players_challenge as $p)
        {

            $ret["selectable"][] = 'set_'.$p;

        }
        
        
        $ret['buttons'][] = 'cancel_btn';
        
        


        return $ret;
    }


    function ChallengeStep1($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;

        if($varg1 == 'cancel_btn')
        {
            $g->addPending($this->player_id, "PlayerTurn");
        }

        else {
            $g->addPending($this->player_id, "ChallengeStep2", $varg1);
        }

        
        
        
    }

    /* la premere card pour défier */
    function argChallengeStep2($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must ...');
        $ret['titleyou'] = clienttranslate('${you} must ...');

        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
        $opponent = explode('_', $parg1)[1];

                
        $last_set = [];
        $sets = game::$instance->getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$opponent}' AND position >= 1 ORDER BY position DESC, card_type ASC");
               
        foreach ($sets as $card) {
                       
            if ($last_set == null) {

               $last_set[] = $card;
            }
        }
        

        foreach ($handCards as $handCard)
        {
            if($handCard['type'] == $last_set[0]['type'])
            {
                $ret["selectable"][] = 'my_cards_item_'.$handCard['id'];
            }

            if($handCard['type'] == 11 || $handCard['type'] == 12)
            {
                $ret["selectable"][] = 'my_cards_item_'.$handCard['id'];
            }
        }
    
        
        
        $ret['buttons'][] = 'cancel_btn';
        
        


        return $ret;
    }



    function ChallengeStep2($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;

        if($varg1 == 'cancel_btn')
        {
            $g->addPending($this->player_id, "PlayerTurn");
        }

        else {
            $opponent = explode('_', $parg1)[1];
            $card_id = explode('_', $varg1)[3];
            $card = $g->cards_DB->getCard($card_id);
            $g->setGameStateValue("attaquant", intval($this->player_id));
            $g->setGameStateValue("defenseur", intval($opponent));
            $g->setGameStateValue("challenge", 1);

            $position = count($g->getObjectListFromDB( "SELECT card_id FROM cards WHERE card_location = 'challenge_attack'", true ));
            $new_position = $position + 1;
            
            $g->cards_DB->moveCard($card_id, 'challenge_attack', $this->player_id);
            $g->DbQuery("UPDATE cards SET position = {$new_position} WHERE card_id = {$card_id}");



            $txt = clienttranslate('${player_name} attaque with ....');
            $g->notify->all(
                "cardMoveChallengeAttack",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'card' => $card,
                ]
            );


            $g->addPending($opponent, "ChallengeStep3");
        }

        
        
        
    }

    /* DEFI */
    function argChallengeStep3($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must ...');
        $ret['titleyou'] = clienttranslate('${you} must ...');

        $attaquant = game::$instance->getGameStateValue("attaquant");
        $defenseur = game::$instance->getGameStateValue("defenseur");

    
        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);

        $last_set = [];
        $sets = game::$instance->getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$defenseur}' AND position >= 1 ORDER BY position DESC, card_type ASC");
            
        foreach ($sets as $card) {
                    
            if ($last_set == null) {

            $last_set[] = $card;
            }
        }

        foreach ($handCards as $handCard)
        {
            if($handCard['type'] == $last_set[0]['type'])
            {
                $ret["selectable"][] = 'my_cards_item_'.$handCard['id'];
            }

            if($handCard['type'] == 11 || $handCard['type'] == 12)
            {
                $ret["selectable"][] = 'my_cards_item_'.$handCard['id'];
            }
        }
     
        $ret['buttons'][] = 'abandon_btn';
    
        return $ret;
    }



    function ChallengeStep3($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;

        $attaquant = game::$instance->getGameStateValue("attaquant");
        $defenseur = game::$instance->getGameStateValue("defenseur");
        $card_id = explode('_', $varg1)[3];
        $card = $g->cards_DB->getCard($card_id);
        

        if($attaquant == $this->player_id)
        {
            $position = count($g->getObjectListFromDB( "SELECT card_id FROM cards WHERE card_location = 'challenge_attack'", true ));
            $new_position = $position + 1;
            $g->cards_DB->moveCard($card_id, 'challenge_attack', $this->player_id);
            $g->DbQuery("UPDATE cards SET position = {$new_position} WHERE card_id = {$card_id}");
            $txt = clienttranslate('${player_name} attaque with ....');
            $g->notify->all(
                "cardMoveChallengeAttack",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'card' => $card,
                ]
            );
            $g->addPending($defenseur, "ChallengeStep3");
        }

        if($defenseur == $this->player_id)
        {
            $position = count($g->getObjectListFromDB( "SELECT card_id FROM cards WHERE card_location = 'challenge_defense'", true ));
            $new_position = $position + 1;
            $g->cards_DB->moveCard($card_id, 'challenge_defense', $this->player_id);
            $g->DbQuery("UPDATE cards SET position = {$new_position} WHERE card_id = {$card_id}");
            $txt = clienttranslate('${player_name} defense with ....');
            $g->notify->all(
                "cardMoveChallengeDefense",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'card' => $card,
                ]
            );
            $g->addPending($attaquant, "ChallengeStep3");
        }

        
        
    }

   


    /*
     _                     
    | |                    
    | |     ___   __ _ ___ 
    | |    / _ \ / _` / __|
    | |___| (_) | (_| \__ \
    |______\___/ \__, |___/
                __/ |    
                |___/     

    */

    function getLogs($type)
    {
        
    }

    


}
