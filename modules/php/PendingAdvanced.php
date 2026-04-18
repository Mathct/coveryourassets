<?php

namespace Bga\Games\coveryourassets;   // ATTENTION NOM DU JEU

trait PendingAdvancedTrait  // ATTENTION
{
    /*
     _______               
    |__   __|              
        | |_   _ _ __ _ __  
        | | | | | '__| '_ \ 
        | | |_| | |  | | | |
        |_|\__,_|_|  |_| |_|

    */


    function argPlayerTurn2($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must choose an action');
        $ret['titleyou'] = clienttranslate('${you} must choose an action');

        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
        $count_hand_cards = count($handCards);
        
        if($count_hand_cards >= 1)
        {
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
                
                $sets = game::$instance->getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' AND position >= 2 ORDER BY position DESC, card_type ASC");
                
                foreach ($sets as $card) {
                    // On garde la première carte rencontrée à la position la plus haute à partir de la position 2 (la position 1 est intouchable)
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

            if($this->player_turn == 2)
            {
               $ret['buttons'][] = 'pass_btn'; 
            }
        }
         

        return $ret;
    }



    function PlayerTurn2($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;
        $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

               
        if($varg1 == null)
        {
            $g->setGameStateValue("player_turn", 1);

            if($count_deck >= 1)
            {
                $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
                $count_hand_cards = count($handCards);

                $need_cards = 6 - $count_hand_cards;

                if($count_deck >= $need_cards)
                {
                    $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $this->player_id);
                    $g->deck->inc(-$need_cards);
                    $g->hand->inc($this->player_id, $need_cards);

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
                if (($count_deck < $need_cards)&&($count_deck >= 1))
                {
                    $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $this->player_id);
                    $g->deck->set(0);
                    $g->hand->inc($this->player_id, $count_deck);

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

                $g->addPendingFirst($this->player_id, "PlayerTurn2");

            }

            else
            {
                $count_all_cards = count($g->getObjectListFromDB( "SELECT card_id FROM cards WHERE card_location = 'hand'", true ));
                if($count_all_cards >= 1)
                {
                    $g->addPendingFirst($this->player_id, "PlayerTurn2");
                }
                else
                {
                    $g->addPending($this->player_id, "EndOfRound2");
                }
            }

        }

        if($varg1 == 'pass_btn')
        {
            $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
            $count_hand_cards = count($handCards);

            $need_cards = 6 - $count_hand_cards;

            if($count_deck >= $need_cards)
            {
                $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $this->player_id);
                $g->deck->inc(-$need_cards);
                $g->hand->inc($this->player_id, $need_cards);

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
            if (($count_deck < $need_cards)&&($count_deck >= 1))
            {
                $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $this->player_id);
                $g->deck->set(0);
                $g->hand->inc($this->player_id, $count_deck);

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
            
            $g->setGameStateValue("player_turn", 1);
            $g->addPendingFirst($this->player_id, "PlayerTurn2");

        }

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

            $card_for_set = $g->getObjectListFromDB(
                "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM cards WHERE card_location ='set' AND card_location_arg = '{$this->player_id}' AND position = '{$newSetPosition}' ORDER BY `card_type` ASC"
            )[0];
            
            $txt = clienttranslate('${player_name} creates a set: ${log}');
            $g->notify->all(
                "cardsMovedToTable",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'cards' => $cards,
                    'card_for_set' => $card_for_set,
                    'log' => $this->getSetLog($cards[0]['type'],$cards[1]['type']),
                ]
            );

            $this->majSetCounters($this->player_id);
            $g->set->inc($this->player_id, 1);
            $this->Lock();
            $g->hand->inc($this->player_id, -2);

            if($this->player_turn == 2)
            {
                $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
                $count_hand_cards = count($handCards);

                $need_cards = 6 - $count_hand_cards;

                if($count_deck >= $need_cards)
                {
                    $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $this->player_id);
                    $g->deck->inc(-$need_cards);
                    $g->hand->inc($this->player_id, $need_cards);

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
                if (($count_deck < $need_cards)&&($count_deck >= 1))
                {
                    $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $this->player_id);
                    $g->deck->set(0);
                    $g->hand->inc($this->player_id, $count_deck);

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
            }


            $g->DbQuery(
                "UPDATE player SET `first_set` = 1 WHERE player_id = '{$this->player_id}'"
            );

            if($this->player_turn == 1)
            {
                $g->setGameStateValue("player_turn", 2);
                $g->addPending($this->player_id, "PlayerTurn2");
            }
          
            if($this->player_turn == 2)
            {
                $g->setGameStateValue("player_turn", 1);
                $g->addPendingFirst($this->player_id, "PlayerTurn2");
            }

            
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

            $txt = clienttranslate('${player_name} discards: ${log}');
            $g->notify->all(
                "cardsMovedToDiscard",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'card' => $card,
                    'log' => $this->getCardLog($card['type']),
                ]
            );

            if($this->player_turn == 1)
            {

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

                    $g->deck->inc(-1);
                }

                else {
                    $g->hand->inc($this->player_id, -1);
                }

            }

            if($this->player_turn == 2)
            {
                $g->hand->inc($this->player_id, -1);

                $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
                $count_hand_cards = count($handCards);

                $need_cards = 6 - $count_hand_cards;

                if($count_deck >= $need_cards)
                {
                    $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $this->player_id);
                    $g->deck->inc(-$need_cards);
                    $g->hand->inc($this->player_id, $need_cards);

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
                if (($count_deck < $need_cards)&&($count_deck >= 1))
                {
                    $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $this->player_id);
                    $g->deck->set(0);
                    $g->hand->inc($this->player_id, $count_deck);

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
            }

            if($this->player_turn == 1)
            {
                $g->setGameStateValue("player_turn", 2);
                $g->addPending($this->player_id, "PlayerTurn2");
            }
          
            if($this->player_turn == 2)
            {
                $g->setGameStateValue("player_turn", 1);
                $g->addPendingFirst($this->player_id, "PlayerTurn2");
            }
        }

        if($varg1 == 'challenge_btn') {

            $g->addPending($this->player_id, "Challenge2Step1");
        }

        
    }


    /* choisir l'adversaire */
    function argChallenge2Step1($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must choose an action');
        $ret['titleyou'] = clienttranslate('Challenge: ${you} must choose an opponent');

        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
        
        $last_set = [];
        $players = game::$instance->getObjectListFromDB( "SELECT `player_id` FROM `player`", true );

        foreach ($players as $player)
        {
            
            $sets = game::$instance->getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' AND position >= 2 ORDER BY position DESC, card_type ASC");
            
            foreach ($sets as $card) {
                // On garde la première carte rencontrée à la position la plus haute à partir de la position 2 (la position 1 est intouchable)
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


    function Challenge2Step1($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;

        if($varg1 == 'cancel_btn')
        {
            $g->addPending($this->player_id, "PlayerTurn2");
        }

        else {
            $g->addPending($this->player_id, "Challenge2Step2", $varg1);
        }

        
        
        
    }

    /* choisir la premere card du défi pour l'attaquant */
    function argChallenge2Step2($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must choose an action');
        $ret['titleyou'] = clienttranslate('Challenge: ${you} must choose a card');

        $ret["selected"][] = $parg1;

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



    function Challenge2Step2($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;

        if($varg1 == 'cancel_btn')
        {
            $g->addPending($this->player_id, "PlayerTurn2");
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

            $g->notify->all(
                "challengeShow",
                '',
                [
                    'attaquant' => $this->player_id,
                    'defenseur' => $opponent,
                ]
            );


            $opponent_name = $g->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id='{$opponent}'");
            $opponent_color = $g->getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id='{$opponent}'");

            $txt = clienttranslate('${player_name} challenges ${opponent} with: ${log}');
            $g->notify->all(
                "cardMoveChallengeAttack",
                $txt,
                [
                    'opponent' =>    [
                        'log' => '<b style="color: #${color};">${opponent_name}</b>',
                        'args' => ['opponent_name' => $opponent_name, 'color' => $opponent_color]
                    ],
                    'player_id' => $this->player_id,
                    'card' => $card,
                    'log' => $this->getCardLog($card['type']),
                ]
            );

            $g->hand->inc($this->player_id, -1);
            $g->addPending($opponent, "Challenge2Step3");
        }

        
        
        
    }

    /* DEFI tour par tour jusqu'a abandon*/
    function argChallenge2Step3($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must respond to the Challenge');
        

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

        if(count($ret["selectable"]) != null)
        {
            $ret['titleyou'] = clienttranslate('Challenge: ${you} must choose a card OR');
        }

        else {
            $ret['titleyou'] = clienttranslate('Challenge: ${you} must');
        }
     
        $ret['buttons'][] = 'abandon_btn';
    
        return $ret;
    }



    function Challenge2Step3($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;

        if($varg1 == 'abandon_btn')
        {
            $g->addPending($this->player_id, "Challenge2Step4");
        }

        else {

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
            $txt = clienttranslate('${player_name} responds to the challenge with: ${log}');
            $g->notify->all(
                "cardMoveChallengeAttack",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'card' => $card,
                    'log' => $this->getCardLog($card['type']),
                ]
            );
            $g->hand->inc($this->player_id, -1);
            $g->addPending($defenseur, "Challenge2Step3");
        }

        if($defenseur == $this->player_id)
        {
            $position = count($g->getObjectListFromDB( "SELECT card_id FROM cards WHERE card_location = 'challenge_defense'", true ));
            $new_position = $position + 1;
            $g->cards_DB->moveCard($card_id, 'challenge_defense', $this->player_id);
            $g->DbQuery("UPDATE cards SET position = {$new_position} WHERE card_id = {$card_id}");
            $txt = clienttranslate('${player_name} responds to the challenge with: ${log}');
            $g->notify->all(
                "cardMoveChallengeDefense",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'card' => $card,
                    'log' => $this->getCardLog($card['type']),
                ]
            );
            $g->hand->inc($this->player_id, -1);
            $g->addPending($attaquant, "Challenge2Step3");
        }

        }

        
        
    }

    /* Abandon*/
    function argChallenge2Step4($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('');
        $ret['titleyou'] = clienttranslate('');

    
    
        return $ret;
    }



    function Challenge2Step4($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;

        $attaquant = game::$instance->getGameStateValue("attaquant");
        $defenseur = game::$instance->getGameStateValue("defenseur");
        $max_position_set_attaquant = $g->getUniqueValueFromDB("SELECT MAX(position) AS valeur_max FROM cards WHERE card_location = 'set' AND card_location_arg = '{$attaquant}'");
        $max_position_set_defenseur = $g->getUniqueValueFromDB("SELECT MAX(position) AS valeur_max FROM cards WHERE card_location = 'set' AND card_location_arg = '{$defenseur}'");

        $cards_defi = $g->getObjectListFromDB("SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='challenge_attack' OR `card_location` ='challenge_defense'");

        /* c'est l'attaquant qui abandonne*/
        if($attaquant == $this->player_id)
        {

            $txt = clienttranslate('${player_name} abandons the challenge');
            $g->notify->all(
                "challengeWinByDefense",
                $txt,
                [
                    'player_id' => $attaquant,
                    'winner' => $defenseur,
                    'cards' => $cards_defi,
                ]
            );

            $txt = clienttranslate('${player_name} wins the challenge');
            $g->notify->all(
                "message",
                $txt,
                [
                    'player_id' => $defenseur,
                    
                ]
            );

            foreach ($cards_defi as $card_defi)
            {
                $g->cards_DB->moveCard($card_defi['id'], 'set', $defenseur);
                $g->DbQuery("UPDATE cards SET position = {$max_position_set_defenseur} WHERE card_id = {$card_defi['id']}");
            }
            
        }

        /* c'est le defenseur qui abandonne*/
        if($defenseur == $this->player_id)
        {

            $new_position = $max_position_set_attaquant + 1;
            
            $g->DbQuery("UPDATE cards SET card_location_arg = {$attaquant}, position = {$new_position} WHERE position = '{$max_position_set_defenseur}' AND card_location_arg = '{$defenseur}'");

            foreach ($cards_defi as $card_defi)
            {
                $g->cards_DB->moveCard($card_defi['id'], 'set', $attaquant);
                $g->DbQuery("UPDATE cards SET position = {$new_position} WHERE card_id = {$card_defi['id']}");
            }

            $card_for_set = $g->getObjectListFromDB(
                "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM cards WHERE card_location ='set' AND card_location_arg = '{$attaquant}' AND position = '{$new_position}' ORDER BY `card_type` ASC"
            )[0];

            $txt = clienttranslate('${player_name} abandons the challenge');
            $g->notify->all(
                "challengeWinByAttack",
                $txt,
                [
                    'player_id' => $defenseur,
                    'position_def' => $max_position_set_defenseur,
                    'winner' => $attaquant,
                    'cards' => $cards_defi,
                    'card_for_set' => $card_for_set,
                ]
            );

            $txt = clienttranslate('${player_name} wins the challenge');
            $g->notify->all(
                "message",
                $txt,
                [
                    'player_id' => $attaquant,
                    
                ]
            );

            $g->set->inc($attaquant, 1);
            $g->set->inc($defenseur, -1);

        }

        $this->majSetCounters($attaquant);
        $this->majSetCounters($defenseur);
        $this->Lock();

        $count_hand_attaquant = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'hand' AND card_location_arg = '{$attaquant}'", true ));
        $count_hand_defenseur = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'hand' AND card_location_arg = '{$defenseur}'", true ));
        $draw_attaquant = 6 - $count_hand_attaquant;
        $draw_defenseur = 6 - $count_hand_defenseur;

        $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));
        $draw = 0;

        if(($draw_attaquant < 6)&&($count_deck >= 1))
        {
            if($count_deck >= $draw_attaquant)
            {
                $draw = $draw_attaquant;
            }
            else
            {
                $draw = $count_deck;
            }

            $newcards = $g->cards_DB->pickCards($draw,'deck', $attaquant);
            $g->notify->player(
                $attaquant,
                "drawCards",
                '',
                [
                    'player_id' => $attaquant,
                    'cards' => $newcards,
                ]
            );

            $g->hand->inc($attaquant, $draw);
            $g->deck->inc(-$draw);
        }

        $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));
        $draw = 0;

        if(($draw_defenseur < 6)&&($count_deck >= 1))
        {
            if($count_deck >= $draw_defenseur)
            {
                $draw = $draw_defenseur;
            }
            else
            {
                $draw = $count_deck;
            }

            $newcards = $g->cards_DB->pickCards($draw,'deck', $defenseur);
            $g->notify->player(
                $defenseur,
                "drawCards",
                '',
                [
                    'player_id' => $defenseur,
                    'cards' => $newcards,
                ]
            );

            $g->hand->inc($defenseur, $draw);
            $g->deck->inc(-$draw);
        }

        
        $g->setGameStateValue("attaquant", 0);
        $g->setGameStateValue("defenseur", 0);
        $g->setGameStateValue("challenge", 0);

        $g->addPendingFirst($attaquant, "PlayerTurn2");
        
    }

    /* END OF ROUND*/
    function argEndOfRound2($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('');
        $ret['titleyou'] = clienttranslate('');

    
        //$ret['buttons'][] = 'yes_btn';
    
        return $ret;
    }



    function EndOfRound2($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;
        $mode = $this->winning_condition;

        $g->notify->all('message', clienttranslate('${message}'), [
                'message' => [
                    'log' => '<div class="log_endofRound">${round} ${nb}</div>',
                    'args' => [
                        'round' => clienttranslate('End of Round'),
                        'nb' => $this->round_nb,
                        'i18n' => ['round']
                    ],
                ]
            ]);


 
    // addition du cumul des values en BDD
        $players = $g->getObjectListFromDB( "SELECT player_id FROM player", true );

        foreach ($players as $player)
        {
            $somme_value = $g->getUniqueValueFromDB("SELECT SUM(value) AS total FROM cards WHERE card_location = 'set' AND card_location_arg = '{$player}'");
            if($somme_value != null)
            {
                if($mode != 4)
                {
                    $g->DbQuery("UPDATE player SET cumul_value = cumul_value + {$somme_value} WHERE player_id = '{$player}'");
                    $cumul = $g->getUniqueValueFromDB("SELECT cumul_value FROM player WHERE player_id={$player}");
                    $g->cumul_score->set($player, $cumul);
                }

                else
                {
                    $g->DbQuery("UPDATE player SET cumul_value = {$somme_value} WHERE player_id = '{$player}'");
                }

                               
                $txt = clienttranslate('${player_name} gains $${log}');
                $g->notify->all(
                    "message",
                    $txt,
                    [                       
                        'player_id' => $player,
                        'log' => $somme_value,
                        
                    ]
                );
            }
            
            else{

                $txt = clienttranslate('${player_name} gains $0');
                $g->notify->all(
                    "message",
                    $txt,
                    [                       
                        'player_id' => $player,
                        
                        
                    ]
                );
            }


           
        }
     
    //////////////////////
    // Suite en fontion du mode de jeu
    //////////////////////

    // Mode 1 : Jouez jusqu’à ce qu’un joueur atteigne un total de 1 000 000 $
    if($mode == 1)
    {
        $wins = $g->getObjectListFromDB( "SELECT player_id FROM player WHERE cumul_value = (SELECT MAX(cumul_value) FROM player) AND cumul_value >= 1000000", true );
        if($wins != null)
        {
            foreach ($wins as $win)
            {
                game::$instance->bga->playerScore->set($win, 1);
            }

        // on vide la table pending pour mettre fin à la partie
        game::$instance->DbQuery("DELETE FROM `pending`;");

        }

        else{

            $this->newRound();
            $g->addPendingFirst($this->player_id, "PlayerTurn2");

        }
        
    }

    // Mode 2 : Le joueur ayant le score le plus élevé à la fin de la manche gagne
    if($mode == 2)
    {
        $wins = $g->getObjectListFromDB( "SELECT player_id FROM player WHERE cumul_value = (SELECT MAX(cumul_value) FROM player)", true );
        foreach ($wins as $win)
        {
            game::$instance->bga->playerScore->set($win, 1);
        }

        // on vide la table pending pour mettre fin à la partie
        game::$instance->DbQuery("DELETE FROM `pending`;");
    }

    // Mode 3 : Après 3 manches, le plus haut score cumulé l’emporte
    if($mode == 3)
    {
        $round = $g->getGameStateValue("round");

        if($round == 3)
        {
            $wins = $g->getObjectListFromDB( "SELECT player_id FROM player WHERE cumul_value = (SELECT MAX(cumul_value) FROM player)", true );
            foreach ($wins as $win)
            {
                game::$instance->bga->playerScore->set($win, 1);
            }

            // on vide la table pending pour mettre fin à la partie
            game::$instance->DbQuery("DELETE FROM `pending`;");
        }

        else{

            $this->newRound();
            $g->addPendingFirst($this->player_id, "PlayerTurn2");

        }

    }

    // Mode 4 : Le premier joueur à remporter 2 manches gagne la partie
    if($mode == 4)
    {

        $wins = $g->getObjectListFromDB( "SELECT player_id FROM player WHERE cumul_value = (SELECT MAX(cumul_value) FROM player)", true );
        foreach ($wins as $win)
        {
            game::$instance->bga->playerScore->inc($win, 1);

            $txt = clienttranslate('${player_name} wins the round');
                $g->notify->all(
                    "message",
                    $txt,
                    [                       
                        'player_id' => $win,
                                               
                    ]
            );
        }

        $end = $g->getObjectListFromDB( "SELECT player_id FROM player WHERE player_score = (SELECT MAX(player_score) FROM player) AND player_score >= 2", true );
        if($end != null)
        {
            // on vide la table pending pour mettre fin à la partie
            game::$instance->DbQuery("DELETE FROM `pending`;");
        }

        else {
            $this->newRound();
            $g->addPendingFirst($this->player_id, "PlayerTurn2");
        }


    }

    
        
}

        

    
}