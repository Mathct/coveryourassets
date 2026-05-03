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

        $g = game::$instance;

        $players = game::$instance->getObjectListFromDB( "SELECT `player_id` FROM `player`", true );

        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
        $count_hand_cards = count($handCards);

        $discard_card = $g->getObjectFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='discard' ORDER BY `position` DESC LIMIT 1" );

        $last_defenseur = $g->getGameStateValue("defenseur_first_turn");
        $last_win = $g->getGameStateValue("win_first_turn");

        $last_set_type = $g->getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$this->player_id}' ORDER BY `position` DESC, `card_type` ASC LIMIT 1" );

        $possible_swap = 0;
        $possible_move = 0;

        if($g->set->get($this->player_id) >= 1)
        {
            foreach($players as $player)
            {
                if(($player != $this->player_id) && ($g->set->get($player) >= 1))
                {
                    $possible_swap = 1;
                }
            }
        }
        
        foreach($players as $player)
        {
            if($g->set->get($player) >= 2)
            {
                $possible_move = 1;
            }
        }
    

                        
        if($count_hand_cards >= 1)
        {
            $assetCounts = [];
            $jokerCount = 0;
            $improve = 0;
            $swap = 0;
            $move = 0;

            foreach ($handCards as $card) {
                $ret["selectablemulti"][] = 'my_cards_item_' . $card['id'];
                $type = (int) $card['type'];
                if ($type >= 1 && $type <= 11) {
                    if (!isset($assetCounts[$type])) {
                        $assetCounts[$type] = 0;
                    }
                    $assetCounts[$type]++;
                } 

                if ($type == 12 || $type == 13) {
                    $jokerCount++;
                }

                if($type == $last_set_type)
                {
                    $improve = 1;
                }

                if($type == 14)
                {
                    $swap = 1;
                }

                if($type == 15)
                {
                    $move = 1;
                }
            }

            

            $canCreateSet = false;
            $canCreateSetwithDiscard = false;

            foreach ($assetCounts as $count) {
                if ($count >= 2) {
                    $canCreateSet = true;
                }
            }

            if ($jokerCount > 0 && count($assetCounts) > 0) {
                $canCreateSet = true;
            }

 
            if (!isset($discard_card['type'])) {
                // aucune carte dans la défausse → skip
            } 
            else 
            {
                $type = (int)$discard_card['type'];

                if (isset($assetCounts[$type]) || ($type == 12 && count($assetCounts) > 0) || ($type == 13 && count($assetCounts) > 0)) 
                {
                    $canCreateSet = true;
                    $canCreateSetwithDiscard = true;
                }

                if ($jokerCount > 0 && $type <= 11)
                {
                    $canCreateSet = true;
                    $canCreateSetwithDiscard = true;
                }
            }

            if ($canCreateSet) {
                $ret['buttons'][] = 'create_set_btn';
            }

            if ($canCreateSetwithDiscard)
            {
                $ret["selectablemulti"][] = 'discard_card_'.$discard_card['id'];
            }


            if($improve == 1)
            {
                $ret['buttons'][] = 'improve_btn';
            }

            if(($swap == 1)&&($possible_swap == 1))
            {
                $ret['buttons'][] = 'swap_btn';
            }

            if(($move == 1)&&($possible_move == 1))
            {
                $ret['buttons'][] = 'move_btn';
            }




            $ret['buttons'][] = 'discard_btn';




            $last_set = [];
            $second_last_set = [];
            
            // dernier set
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

            // avant dernier set non protégé
            foreach ($players as $player)
            {
                $nb_set = $g->set->get($player);
                if($nb_set >= 3)
                {
                    $position_second_set = $nb_set - 1;
                    $second_type = $g->getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' AND `position` = '{$position_second_set}' ORDER BY `card_type` ASC LIMIT 1" );
                    $second_last_set[$player] = $second_type;
                        
                }
                
            }


            $possible_challenge = 0;

            $first_set = game::$instance->getUniqueValueFromDB("SELECT first_set FROM player WHERE player_id = '{$this->player_id}'");
            if($first_set >= 1)
            {

                // test pour dernier set
                foreach ($players as $player)
                {
                    if($player != $this->player_id)
                    {
                        if(($player == $last_defenseur)&&($last_win == 1)||($player != $last_defenseur))
                        {
                            if (isset($last_set[$player])) {
                                $type_last_set = $last_set[$player]['type'];

                                foreach ($handCards as $card) {
                                    $type = (int) $card['type'];
                                    if ($type == $type_last_set || $type == 11 || $type == 12 || $type == 13) {
                                        $possible_challenge = 1;
                                    } 
                                }


                            }
                        }
                    }
                
                }

                // test pour avant dernier set
                foreach ($players as $player)
                {
                    if($player != $this->player_id)
                    {
                        if(($player == $last_defenseur)&&($last_win == 1)||($player != $last_defenseur))
                        {
                            if (isset($second_last_set[$player])) {

                                $type_second_last_set = $second_last_set[$player];

                                if(isset($assetCounts[$type_second_last_set]))
                                {
                                    if(($assetCounts[$type_second_last_set] >= 2) || ($assetCounts[$type_second_last_set] == 1 && $jokerCount >= 1))
                                    {
                                        $possible_challenge = 1;
                                    }  
                                }

                                if($jokerCount >= 2)
                                {
                                    $possible_challenge = 1;
                                }

                                if(isset($assetCounts[11]))
                                {
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


                $last_defenseur = $g->getGameStateValue("defenseur_first_turn");
                $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

                if(($last_defenseur != 0)&&($count_deck >= 1))
                {
                    $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $last_defenseur);
                    $count_hand_cards = count($handCards);

                    $need_cards = 6 - $count_hand_cards;

                    if($count_deck >= $need_cards)
                    {
                        $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $last_defenseur);
                        $g->deck->inc(-$need_cards);
                        $g->hand->inc($last_defenseur, $need_cards);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );
                    }
                    if (($count_deck < $need_cards)&&($count_deck >= 1))
                    {
                        $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $last_defenseur);
                        $g->deck->set(0);
                        $g->hand->inc($last_defenseur, $count_deck);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );

                    }

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

            $g->setGameStateValue("attaquant", 0);
            $g->setGameStateValue("defenseur", 0);
            $g->setGameStateValue("challenge", 0);
            $g->setGameStateValue("defenseur_first_turn", 0);
            $g->setGameStateValue("win_first_turn", 0);
            $g->setGameStateValue("player_turn", 1);

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

            $last_defenseur = $g->getGameStateValue("defenseur_first_turn");
            $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

            if(($last_defenseur != 0)&&($count_deck >= 1))
            {
                $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $last_defenseur);
                $count_hand_cards = count($handCards);

                $need_cards = 6 - $count_hand_cards;

                if($count_deck >= $need_cards)
                {
                    $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $last_defenseur);
                    $g->deck->inc(-$need_cards);
                    $g->hand->inc($last_defenseur, $need_cards);

                    $g->notify->player(
                            $last_defenseur,
                            "drawCards",
                            '',
                            [
                                'player_id' => $last_defenseur,
                                'cards' => $newcards,
                            ]
                        );
                }
                if (($count_deck < $need_cards)&&($count_deck >= 1))
                {
                    $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $last_defenseur);
                    $g->deck->set(0);
                    $g->hand->inc($last_defenseur, $count_deck);

                    $g->notify->player(
                            $last_defenseur,
                            "drawCards",
                            '',
                            [
                                'player_id' => $last_defenseur,
                                'cards' => $newcards,
                            ]
                        );

                }

            }
            
            $g->setGameStateValue("attaquant", 0);
            $g->setGameStateValue("defenseur", 0);
            $g->setGameStateValue("challenge", 0);
            $g->setGameStateValue("defenseur_first_turn", 0);
            $g->setGameStateValue("win_first_turn", 0);
            $g->setGameStateValue("player_turn", 1);

            $g->addPendingFirst($this->player_id, "PlayerTurn2");

        }

        if($varg1 == 'create_set_btn') {

            $ids = explode('_', $varg2);
            $id1 = (int) $ids[0];
            $id2 = (int) $ids[1];
            $discard = (int) $ids[2];

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
                    'discard_id' => $discard,
                    'log' => $this->getSetLog($cards[0]['type'],$cards[1]['type']),
                ]
            );

            $this->majSetCounters($this->player_id);
            $g->set->inc($this->player_id, 1);
            $this->Lock();
            if($discard == 0)
            {
                $g->hand->inc($this->player_id, -2);
            }
            else
            {
                $g->hand->inc($this->player_id, -1);
            }

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
                $last_defenseur = $g->getGameStateValue("defenseur_first_turn");
                $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

                if(($last_defenseur != 0)&&($count_deck >= 1))
                {
                    $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $last_defenseur);
                    $count_hand_cards = count($handCards);

                    $need_cards = 6 - $count_hand_cards;

                    if($count_deck >= $need_cards)
                    {
                        $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $last_defenseur);
                        $g->deck->inc(-$need_cards);
                        $g->hand->inc($last_defenseur, $need_cards);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );
                    }
                    if (($count_deck < $need_cards)&&($count_deck >= 1))
                    {
                        $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $last_defenseur);
                        $g->deck->set(0);
                        $g->hand->inc($last_defenseur, $count_deck);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );

                    }

                }

                $g->setGameStateValue("attaquant", 0);
                $g->setGameStateValue("defenseur", 0);
                $g->setGameStateValue("challenge", 0);
                $g->setGameStateValue("defenseur_first_turn", 0);
                $g->setGameStateValue("win_first_turn", 0);
                $g->setGameStateValue("player_turn", 1);
                $g->addPendingFirst($this->player_id, "PlayerTurn2");
            }

            
        }

        if($varg1 == 'discard_btn') {
            $ids = explode('_', $varg2);
            $id1 = (int) $ids[0];

            $newDiscardPosition = 0;

            $card = $g->cards_DB->getCard($id1);

            $type = $card['type'];

            if($type <= 13)
            {
                $maxDiscardPosition = (int) $g->getUniqueValueFromDB("SELECT `position` FROM cards WHERE `card_location` ='discard' ORDER BY `position` DESC LIMIT 1");
                $newDiscardPosition = $maxDiscardPosition + 1;

                $g->DbQuery(
                    "UPDATE cards SET `position` = {$newDiscardPosition} WHERE card_id = {$id1}"
                );

                $g->cards_DB->moveCard($id1, 'discard', 0);

            }

            else
            {
                $g->cards_DB->moveCard($id1, 'discardaction');
            }

            $txt = clienttranslate('${player_name} discards: ${log}');
            $g->notify->all(
                "cardsMovedToDiscard",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'card' => $card,
                    'position' => $newDiscardPosition,
                    'log' => $this->getCardLog($type),
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
                $last_defenseur = $g->getGameStateValue("defenseur_first_turn");
                $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

                if(($last_defenseur != 0)&&($count_deck >= 1))
                {
                    $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $last_defenseur);
                    $count_hand_cards = count($handCards);

                    $need_cards = 6 - $count_hand_cards;

                    if($count_deck >= $need_cards)
                    {
                        $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $last_defenseur);
                        $g->deck->inc(-$need_cards);
                        $g->hand->inc($last_defenseur, $need_cards);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );
                    }
                    if (($count_deck < $need_cards)&&($count_deck >= 1))
                    {
                        $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $last_defenseur);
                        $g->deck->set(0);
                        $g->hand->inc($last_defenseur, $count_deck);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );

                    }

                }

                $g->setGameStateValue("attaquant", 0);
                $g->setGameStateValue("defenseur", 0);
                $g->setGameStateValue("challenge", 0);
                $g->setGameStateValue("defenseur_first_turn", 0);
                $g->setGameStateValue("win_first_turn", 0);
                $g->setGameStateValue("player_turn", 1);
                $g->addPendingFirst($this->player_id, "PlayerTurn2");
            }
        }

        if($varg1 == 'improve_btn') 
        {
            $g->hand->inc($this->player_id, -1);
            $max_position = $g->getUniqueValueFromDB("SELECT MAX(position) AS valeur_max FROM cards WHERE card_location = 'set' AND card_location_arg = '{$this->player_id}'");
            $ids = explode('_', $varg2);
            $id1 = (int) $ids[0];

            $card = $g->cards_DB->getCard($id1);

            $g->DbQuery(
                "UPDATE cards SET `position` = {$max_position} WHERE card_id = {$id1}"
            );

            $g->cards_DB->moveCard($id1, 'set', $this->player_id);


            $txt = clienttranslate('${player_name} improve the last set with: ${log}');
            $g->notify->all(
                "cardImprove",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'card' => $card,
                    'log' => $this->getCardLog($card['type']),
                ]
            );


            $this->majSetCounters($this->player_id);
            $this->Lock();
            

            if($this->player_turn == 1)
            {
                $g->setGameStateValue("player_turn", 2);
                $g->addPending($this->player_id, "PlayerTurn2");
            }

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

                $last_defenseur = $g->getGameStateValue("defenseur_first_turn");
                $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

                if(($last_defenseur != 0)&&($count_deck >= 1))
                {
                    $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $last_defenseur);
                    $count_hand_cards = count($handCards);

                    $need_cards = 6 - $count_hand_cards;

                    if($count_deck >= $need_cards)
                    {
                        $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $last_defenseur);
                        $g->deck->inc(-$need_cards);
                        $g->hand->inc($last_defenseur, $need_cards);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );
                    }
                    if (($count_deck < $need_cards)&&($count_deck >= 1))
                    {
                        $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $last_defenseur);
                        $g->deck->set(0);
                        $g->hand->inc($last_defenseur, $count_deck);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );

                    }

                }

                $g->setGameStateValue("attaquant", 0);
                $g->setGameStateValue("defenseur", 0);
                $g->setGameStateValue("challenge", 0);
                $g->setGameStateValue("defenseur_first_turn", 0);
                $g->setGameStateValue("win_first_turn", 0);
                $g->setGameStateValue("player_turn", 1);
                $g->addPendingFirst($this->player_id, "PlayerTurn2");
            }

        }        

        if($varg1 == 'swap_btn')
        {
            $g->addPending($this->player_id, "Swap", $varg2);
        }

        if($varg1 == 'move_btn')
        {
            $g->addPending($this->player_id, "Move", $varg2);
        }

        if($varg1 == 'challenge_btn') {

            $g->addPending($this->player_id, "Challenge2Step1");
        }

        
    }


    function argSwap($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must choose an action');
        $ret['titleyou'] = clienttranslate('${you} must choose an other player for the swap action');

        $g = game::$instance;

        $players = game::$instance->getObjectListFromDB( "SELECT `player_id` FROM `player`", true );

        $ret["selected"][] = 'my_cards_item_'.$parg1;

        foreach($players as $player)
        {
            if(($player != $this->player_id) && ($g->set->get($player) >= 1))
            {
                $ret["selectable"][] = 'set_'.$player;
            }
        }

        $ret['buttons'][] = 'cancel_btn';

        return $ret;
    }

    function Swap($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;
        $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

        if($varg1 == 'cancel_btn')
        {
            $g->addPending($this->player_id, "PlayerTurn2");
        }

        else
        {
            $player = (int)explode('_', $varg1)[1];
            $max_position_set_my = $g->getUniqueValueFromDB("SELECT MAX(position) AS valeur_max FROM cards WHERE card_location = 'set' AND card_location_arg = '{$this->player_id}'");
            $my_position_inc = (int)$max_position_set_my + 1;
            $max_position_set_player = $g->getUniqueValueFromDB("SELECT MAX(position) AS valeur_max FROM cards WHERE card_location = 'set' AND card_location_arg = '{$player}'");

            $g->DbQuery("UPDATE cards SET position = {$my_position_inc}, card_location_arg = {$this->player_id} WHERE card_location ='set' AND card_location_arg = '{$player}' AND position = '{$max_position_set_player}'");
            $g->DbQuery("UPDATE cards SET position = {$max_position_set_player}, card_location_arg = {$player} WHERE card_location ='set' AND card_location_arg = '{$this->player_id}' AND position = '{$max_position_set_my}'");
            $g->DbQuery("UPDATE cards SET position = {$max_position_set_my} WHERE card_location ='set' AND card_location_arg = '{$this->player_id}' AND position = '{$my_position_inc}'");

            $g->cards_DB->moveCard($parg1, 'discardaction');
            

            $txt = clienttranslate('${player_name} uses: ${log}');
            $g->notify->all(
                "message",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'log' => $this->getCardLog(14),
                ]
            );

            $playercards = [];
            $sets = self::getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' AND position >= 1 ORDER BY position ASC, card_type ASC");
            foreach ($sets as $card) {
                // On garde la première carte rencontrée par position
                if (!isset($playercards[$card['position']])) {
                    $playercards[$card['position']] = $card;
                }
            }

            $mycards = [];
            $sets = self::getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$this->player_id}' AND position >= 1 ORDER BY position ASC, card_type ASC");
            foreach ($sets as $card) {
                // On garde la première carte rencontrée par position
                if (!isset($mycards[$card['position']])) {
                    $mycards[$card['position']] = $card;
                }
            }

            $my_last_set_type = self::getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$this->player_id}' ORDER BY `position` DESC, `card_type` ASC LIMIT 1" );
            $player_last_set_type = self::getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' ORDER BY `position` DESC, `card_type` ASC LIMIT 1" );

            $opponent_name = $g->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id='{$player}'");
            $opponent_color = $g->getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id='{$player}'");

            $txt = clienttranslate('${player_name} swaps with ${opponent}');
            $g->notify->all(
                "swapCard",
                $txt,
                [
                    'opponent' =>    [
                        'log' => '<b style="color: #${color};">${opponent_name}</b>',
                        'args' => ['opponent_name' => $opponent_name, 'color' => $opponent_color]
                    ],
                    'player_id' => $this->player_id,
                    'player' => $player,
                    'discardID' => $parg1,
                    'discard_player' => $this->player_id,
                    'mycards' => $mycards,
                    'playercards' => $playercards,
                    'my_position' => $max_position_set_my,
                    'player_position' => $max_position_set_player,
                    'my_last_set_type' => $my_last_set_type,
                    'player_last_set_type' => $player_last_set_type
                    
                ]
            );


            $g->hand->inc($this->player_id, -1);

            $this->majSetCounters($this->player_id);
            $this->majSetCounters($player);
            $this->Lock();

                       
            if($this->player_turn == 1)
            {
                $g->setGameStateValue("player_turn", 2);
                $g->addPending($this->player_id, "PlayerTurn2");
            }

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

                $last_defenseur = $g->getGameStateValue("defenseur_first_turn");
                $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

                if(($last_defenseur != 0)&&($count_deck >= 1))
                {
                    $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $last_defenseur);
                    $count_hand_cards = count($handCards);

                    $need_cards = 6 - $count_hand_cards;

                    if($count_deck >= $need_cards)
                    {
                        $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $last_defenseur);
                        $g->deck->inc(-$need_cards);
                        $g->hand->inc($last_defenseur, $need_cards);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );
                    }
                    if (($count_deck < $need_cards)&&($count_deck >= 1))
                    {
                        $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $last_defenseur);
                        $g->deck->set(0);
                        $g->hand->inc($last_defenseur, $count_deck);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );

                    }

                }

                $g->setGameStateValue("attaquant", 0);
                $g->setGameStateValue("defenseur", 0);
                $g->setGameStateValue("challenge", 0);
                $g->setGameStateValue("defenseur_first_turn", 0);
                $g->setGameStateValue("win_first_turn", 0);
                $g->setGameStateValue("player_turn", 1);
                $g->addPendingFirst($this->player_id, "PlayerTurn2");
            }
        
            
        }

    }





    function argMove($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must choose an action');
        $ret['titleyou'] = clienttranslate('${you} muste choose a player for the move action');

        $ret["selected"][] = 'my_cards_item_'.$parg1;

        $g = game::$instance;

        $players = game::$instance->getObjectListFromDB( "SELECT `player_id` FROM `player`", true );

        foreach($players as $player)
        {
            if($g->set->get($player) >= 2)
            {
                $ret["selectable"][] = 'set_'.$player;
            }
        }

        $ret['buttons'][] = 'cancel_btn';

        return $ret;
    }

    function Move($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;

        if($varg1 == 'cancel_btn')
        {
            $g->addPending($this->player_id, "PlayerTurn2");
        }

        else
        {
            $g->addPending($this->player_id, "MoveStep2", $parg1, $varg1);
        }
        
        

    }

    function argMoveStep2($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must choose an action');
        $ret['titleyou'] = clienttranslate('${you} must choose the direction of the move');

        $ret["selected"][] = 'my_cards_item_'.$parg1;
        $ret["selected"][] = $parg2;

        
        $ret['buttons'][] = 'movetoptobottom_btn';
        $ret['buttons'][] = 'movebottomtotop_btn';

        $ret['buttons'][] = 'cancel_btn';

        return $ret;
    }

    function MoveStep2($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;
        $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

        if($varg1 == 'cancel_btn')
        {
            $g->addPending($this->player_id, "PlayerTurn2");
        }

        if($varg1 == 'movetoptobottom_btn')
        {
            $player = (int)explode('_', $parg2)[1];
            $max_position_set = $g->getUniqueValueFromDB("SELECT MAX(position) AS valeur_max FROM cards WHERE card_location = 'set' AND card_location_arg = '{$player}'");

            $g->DbQuery("UPDATE cards SET position = 0 WHERE card_location ='set' AND card_location_arg = '{$player}' AND position = '{$max_position_set}'");
            $g->DbQuery("UPDATE cards SET position = position +1 WHERE card_location ='set' AND card_location_arg = '{$player}'");

            $g->cards_DB->moveCard($parg1, 'discardaction');
           


            $cards = [];
            $sets = self::getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' AND position >= 1 ORDER BY position ASC, card_type ASC");
            foreach ($sets as $card) {
                // On garde la première carte rencontrée par position
                if (!isset($cards[$card['position']])) {
                    $cards[$card['position']] = $card;
                }
            }

            $last_set_type = self::getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' ORDER BY `position` DESC, `card_type` ASC LIMIT 1" );


            $txt = clienttranslate('${player_name} uses: ${log}');
            $g->notify->all(
                "message",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'log' => $this->getCardLog(15),
                ]
            );

            $txt = clienttranslate('${player_name} moves the set From Top to Bottom');
            $g->notify->all(
                "moveCard",
                $txt,
                [
                    'player_id' => $player,
                    'player' => $player,
                    'cards' => $cards,
                    'discardID' => $parg1,
                    'discard_player' => $this->player_id,
                    'last_type' => $last_set_type

                ]
            );
            

            $this->majSetCounters($player);
            $this->Lock();

            $g->hand->inc($this->player_id, -1);
            
            if($this->player_turn == 1)
            {
                $g->setGameStateValue("player_turn", 2);
                $g->addPending($this->player_id, "PlayerTurn2");
            }

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

                $last_defenseur = $g->getGameStateValue("defenseur_first_turn");
                $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

                if(($last_defenseur != 0)&&($count_deck >= 1))
                {
                    $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $last_defenseur);
                    $count_hand_cards = count($handCards);

                    $need_cards = 6 - $count_hand_cards;

                    if($count_deck >= $need_cards)
                    {
                        $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $last_defenseur);
                        $g->deck->inc(-$need_cards);
                        $g->hand->inc($last_defenseur, $need_cards);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );
                    }
                    if (($count_deck < $need_cards)&&($count_deck >= 1))
                    {
                        $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $last_defenseur);
                        $g->deck->set(0);
                        $g->hand->inc($last_defenseur, $count_deck);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );

                    }

                }

                $g->setGameStateValue("attaquant", 0);
                $g->setGameStateValue("defenseur", 0);
                $g->setGameStateValue("challenge", 0);
                $g->setGameStateValue("defenseur_first_turn", 0);
                $g->setGameStateValue("win_first_turn", 0);
                $g->setGameStateValue("player_turn", 1);
                $g->addPendingFirst($this->player_id, "PlayerTurn2");
            }
        }

        if($varg1 == 'movebottomtotop_btn')
        {
            $player = (int)explode('_', $parg2)[1];
            $max_position_set = $g->getUniqueValueFromDB("SELECT MAX(position) AS valeur_max FROM cards WHERE card_location = 'set' AND card_location_arg = '{$player}'");

            $g->DbQuery("UPDATE cards SET position = {$max_position_set} + 1 WHERE card_location ='set' AND card_location_arg = '{$player}' AND position = 1");
            $g->DbQuery("UPDATE cards SET position = position -1 WHERE card_location ='set' AND card_location_arg = '{$player}'");

            $g->cards_DB->moveCard($parg1, 'discard', 0);
            $min_position_discard = $g->getUniqueValueFromDB("SELECT MIN(position) AS valeur_min FROM cards WHERE card_location = 'discard'");
            $g->DbQuery("UPDATE cards SET position = {$min_position_discard} -1 WHERE card_id = '{$parg1}'");

            $cards = [];
            $sets = self::getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' AND position >= 1 ORDER BY position ASC, card_type ASC");
            foreach ($sets as $card) {
                // On garde la première carte rencontrée par position
                if (!isset($cards[$card['position']])) {
                    $cards[$card['position']] = $card;
                }
            }

            $last_set_type = self::getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' ORDER BY `position` DESC, `card_type` ASC LIMIT 1" );

            $txt = clienttranslate('${player_name} uses: ${log}');
            $g->notify->all(
                "message",
                $txt,
                [
                    'player_id' => $this->player_id,
                    'log' => $this->getCardLog(15),
                ]
            );

            $txt = clienttranslate('${player_name} moves the set From Bottom to Top');
            $g->notify->all(
                "moveCard",
                $txt,
                [
                    'player_id' => $player,
                    'player' => $player,
                    'cards' => $cards,
                    'discardID' => $parg1,
                    'discard_player' => $this->player_id,
                    'last_type' => $last_set_type
                    
                ]
            );

            $this->majSetCounters($player);
            $this->Lock();

            $g->hand->inc($this->player_id, -1);
            
            if($this->player_turn == 1)
            {
                $g->setGameStateValue("player_turn", 2);
                $g->addPending($this->player_id, "PlayerTurn2");
            }

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

                $last_defenseur = $g->getGameStateValue("defenseur_first_turn");
                $count_deck = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

                if(($last_defenseur != 0)&&($count_deck >= 1))
                {
                    $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $last_defenseur);
                    $count_hand_cards = count($handCards);

                    $need_cards = 6 - $count_hand_cards;

                    if($count_deck >= $need_cards)
                    {
                        $newcards = $g->cards_DB->pickCards($need_cards, 'deck', $last_defenseur);
                        $g->deck->inc(-$need_cards);
                        $g->hand->inc($last_defenseur, $need_cards);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );
                    }
                    if (($count_deck < $need_cards)&&($count_deck >= 1))
                    {
                        $newcards = $g->cards_DB->pickCards($count_deck, 'deck', $last_defenseur);
                        $g->deck->set(0);
                        $g->hand->inc($last_defenseur, $count_deck);

                        $g->notify->player(
                                $last_defenseur,
                                "drawCards",
                                '',
                                [
                                    'player_id' => $last_defenseur,
                                    'cards' => $newcards,
                                ]
                            );

                    }

                }

                $g->setGameStateValue("attaquant", 0);
                $g->setGameStateValue("defenseur", 0);
                $g->setGameStateValue("challenge", 0);
                $g->setGameStateValue("defenseur_first_turn", 0);
                $g->setGameStateValue("win_first_turn", 0);
                $g->setGameStateValue("player_turn", 1);
                $g->addPendingFirst($this->player_id, "PlayerTurn2");
            }
        }
        
        

    }






    ////////////////////////////////////////////////////////////////////
    ////////             choisir l'adversaire                 //////////
    ////////////////////////////////////////////////////////////////////


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

        $g = game::$instance;

        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);

        $assetCounts = [];
        $jokerCount = 0;
        foreach ($handCards as $card) {
            $type = (int) $card['type'];
            if ($type >= 1 && $type <= 11) {
                if (!isset($assetCounts[$type])) {
                    $assetCounts[$type] = 0;
                }
                $assetCounts[$type]++;
            } 

            if ($type == 12 || $type == 13) {
                $jokerCount++;
            }
        }

        $last_defenseur = $g->getGameStateValue("defenseur_first_turn");
        $last_win = $g->getGameStateValue("win_first_turn");
        
        $last_set = [];
        $second_last_set = [];

        $players = game::$instance->getObjectListFromDB( "SELECT `player_id` FROM `player`", true );

        // dernier set
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

        // avant dernier set non protégé
        foreach ($players as $player)
        {
            $nb_set = $g->set->get($player);
            if($nb_set >= 3)
            {
                $position_second_set = $nb_set - 1;
                $second_type = $g->getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' AND `position` = '{$position_second_set}' ORDER BY `card_type` ASC LIMIT 1" );
                $second_last_set[$player] = $second_type;
                    
            }
            
        }

        $players_challenge = [];
       
        // test pour dernier set
        foreach ($players as $player)
        {
            if($player != $this->player_id)
            {
                if(($player == $last_defenseur)&&($last_win == 1)||($player != $last_defenseur))
                {
                    if (isset($last_set[$player])) {
                        $type_last_set = $last_set[$player]['type'];

                        foreach ($handCards as $card) {
                            $type = (int) $card['type'];
                            if ($type == $type_last_set || $type == 11 || $type == 12 || $type == 13) {
                                if (!in_array($player, $players_challenge))
                                {
                                    $players_challenge[] = $player;
                                }
                            } 
                        }


                    }
                }
            }
        
        }

        // test pour avant dernier set
        foreach ($players as $player)
        {
            if($player != $this->player_id)
            {
                if(($player == $last_defenseur)&&($last_win == 1)||($player != $last_defenseur))
                {
                    if (isset($second_last_set[$player])) {

                        $type_second_last_set = $second_last_set[$player];

                        if(isset($assetCounts[$type_second_last_set]))
                        {
                            if(($assetCounts[$type_second_last_set] >= 2) || ($assetCounts[$type_second_last_set] == 1 && $jokerCount >= 1))
                            {
                                if (!in_array($player, $players_challenge)) 
                                {
                                    $players_challenge[] = $player;
                                }
                            }  
                        }

                        if($jokerCount >= 2)
                        {
                            if (!in_array($player, $players_challenge)) 
                            {
                                $players_challenge[] = $player;
                            }
                        }

                        if(isset($assetCounts[11]))
                        {
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
            $g->addPending($this->player_id, "ChooseSet", $varg1);
        }
      
    }



    ////////////////////////////////////////////////////////////////////
    ////////             choisir le set attaqué                /////////
    ////////////////////////////////////////////////////////////////////

    function argChooseSet($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must choose an action');
        $ret['titleyou'] = clienttranslate('Challenge: ${you} must choose which set to attack');

        $g = game::$instance;
        $ret["selected"][] = $parg1;


        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
        $opponent = explode('_', $parg1)[1];

        $assetCounts = [];
        $jokerCount = 0;
        foreach ($handCards as $card) {
            $type = (int) $card['type'];
            if ($type >= 1 && $type <= 11) {
                if (!isset($assetCounts[$type])) {
                    $assetCounts[$type] = 0;
                }
                $assetCounts[$type]++;
            } 

            if ($type == 12 || $type == 13) {
                $jokerCount++;
            }
        }

        $nb_set = $g->set->get($opponent);
        $challenge_last_set = 0;
        $challenge_second_last_set = 0;
   
        $last_set = game::$instance->getObjectFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$opponent}' AND position = '{$nb_set}' ORDER BY card_type ASC LIMIT 1");
        
        if(isset($assetCounts[$last_set['type']]))
        {
            
            if(($assetCounts[$last_set['type']] >= 1))
            {
                $challenge_last_set = 1;
            }

        }

        if(isset($assetCounts[11]))
        {
            $challenge_last_set = 1;
        }

        if($jokerCount >= 1)
        {
            $challenge_last_set = 1;
        }

        if($challenge_last_set == 1)
        {
            if($nb_set % 2 == 0)
                {
                    $ret["selectable"][] = 'pile_set_paire_'.$opponent;
                }
                else
                {
                    $ret["selectable"][] = 'pile_set_impaire_'.$opponent;
                }
        }



        $second_last_set = [];

        if($nb_set >= 3)
        {
            $set = $nb_set - 1;
            $second_last_set = game::$instance->getObjectFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$opponent}' AND position = '{$set}' ORDER BY card_type ASC LIMIT 1");
            
            if(isset($assetCounts[$second_last_set['type']]))
            {
                if($assetCounts[$second_last_set['type']] >= 2)
                {
                    $challenge_second_last_set = 1;
                }

                if(($assetCounts[$second_last_set['type']] == 1) && $jokerCount == 1)
                {
                    $challenge_second_last_set = 1;
                }
            }

            if($jokerCount >= 2)
            {
                $challenge_second_last_set = 1;
            }

            if(isset($assetCounts[11]))
            {
                $challenge_second_last_set = 1;
            }


            if($challenge_second_last_set == 1)
            {
                if($set % 2 == 0)
                {
                    $ret["selectable"][] = 'pile_set_paire_'.$opponent;
                }
                else
                {
                    $ret["selectable"][] = 'pile_set_impaire_'.$opponent;
                }
            }
        }

     
        $ret['buttons'][] = 'cancel_btn';

        return $ret;
    }


    function ChooseSet($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;

        if($varg1 == 'cancel_btn')
        {
            $g->addPending($this->player_id, "PlayerTurn2");
        }

        else {

            $opponent = intval(explode('_', $varg1)[3]);
            $parite_set = explode('_', $varg1)[2];
            $nb_set = intval($g->set->get($opponent));

            if((($nb_set %2 == 0) && ($parite_set == 'paire')) || (($nb_set %2 != 0) && ($parite_set == 'impaire')))
            {
                // le joueur attaque le dernier set
                $g->addPending($this->player_id, "Challenge2Step2", $parg1, $varg1);
            }

            if((($nb_set %2 == 0) && ($parite_set == 'impaire')) || (($nb_set %2 != 0) && ($parite_set == 'paire')))
            {
                // le joueur attaque l avant dernier set
                $g->addPending($this->player_id, "Challenge2Step2v2", $parg1, $varg1);
            }

                   

            
        }
    }





    ////////////////////////////////////////////////////////////////////
    ////           l'attaquant attaque le dernier set            ///////
    ////////////////////////////////////////////////////////////////////

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
        $ret["selected"][] = $parg2;

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

            if($handCard['type'] == 11 || $handCard['type'] == 12 || $handCard['type'] == 13)
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
            $g->DbQuery("UPDATE `challenge` SET `value` = '{$parg2}' WHERE `name` = 'set'");

            if($this->player_turn == 1)
            {
                $g->setGameStateValue("defenseur_first_turn", intval($opponent));
            }

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
                    'set' => $parg2
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

            if($card['type'] != 11)
            {
                $g->addPending($opponent, "Challenge2Step3", 1);
            }
            else
            {
                $g->addPending($opponent, "Challenge2Step3v2", 1);
            }
            
        }

        
        
        
    }


    ////////////////////////////////////////////////////////////////////
    ////           l'attaquant attaque l avant dernier set       ///////
    ////////////////////////////////////////////////////////////////////

    function argChallenge2Step2v2($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selectablemulti2"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret["selectedmulti2"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must choose an action');
        $ret['titleyou'] = clienttranslate('Challenge: ${you} must choose 2 cards or 1 wild card');

        $g = game::$instance;

        $ret["selected"][] = $parg1;
        $ret["selected"][] = $parg2;

        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);
        $opponent = explode('_', $parg1)[1];

        $assetCounts = [];
        $jokerCount = 0;
        foreach ($handCards as $card) {
            $type = (int) $card['type'];
            if ($type >= 1 && $type <= 11) {
                if (!isset($assetCounts[$type])) {
                    $assetCounts[$type] = 0;
                }
                $assetCounts[$type]++;
            } 

            if ($type == 12 || $type == 13) {
                $jokerCount++;
            }
        }

        $position = intval($g->set->get($opponent)) - 1;
                
        $second_last_set = game::$instance->getObjectFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$opponent}' AND position = '{$position}' ORDER BY card_type ASC LIMIT 1");
               

        foreach ($handCards as $handCard)
        {

            if(($handCard['type'] == $second_last_set['type'])&&($handCard['type']<= 10))
            {
                if($assetCounts[$second_last_set['type']] >= 2)
                {
                    $ret["selectablemulti2"][] = 'my_cards_item_'.$handCard['id'];
                }

                elseif(($assetCounts[$second_last_set['type']] == 1)&&($jokerCount >= 1))
                {
                    $ret["selectablemulti2"][] = 'my_cards_item_'.$handCard['id'];
                }

            }

            if(($handCard['type'] == 12)||($handCard['type'] == 13))
            {
                if($jokerCount >= 2)
                {
                    $ret["selectablemulti2"][] = 'my_cards_item_'.$handCard['id'];
                }

                elseif(isset($assetCounts[$second_last_set['type']]))
                {

                    $ret["selectablemulti2"][] = 'my_cards_item_'.$handCard['id'];
                }

                
            }
            

            if($handCard['type'] == 11)
            {
                $ret["selectablemulti2"][] = 'my_cards_item_'.$handCard['id'];
            }
        }

    
        $ret['buttons'][] = 'validate_btn';          
        $ret['buttons'][] = 'cancel_btn';
        
        return $ret;
    }



    function Challenge2Step2v2($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;


        if($varg1 == 'cancel_btn')
        {
            $g->addPending($this->player_id, "PlayerTurn2");
        }

        else {

            $opponent = explode('_', $parg1)[1];
            $g->setGameStateValue("attaquant", intval($this->player_id));
            $g->setGameStateValue("defenseur", intval($opponent));
            $g->setGameStateValue("challenge", 1);
            $g->DbQuery("UPDATE `challenge` SET `value` = '{$parg2}' WHERE `name` = 'set'");

            if($this->player_turn == 1)
            {
                $g->setGameStateValue("defenseur_first_turn", intval($opponent));
            }

            $ids = explode('_', $varg2);
            $countIds = count($ids);
            $id1 = (int)$ids[0];
            $id2 = 0;

            if($countIds == 2)
            {
                $id2 = (int)$ids[1];
            }


            if($countIds == 1)
            {
                $card = $g->cards_DB->getCard($id1);

                $position = count($g->getObjectListFromDB( "SELECT card_id FROM cards WHERE card_location = 'challenge_attack'", true ));
                $new_position = $position + 1;
                
                $g->cards_DB->moveCard($id1, 'challenge_attack', $this->player_id);
                $g->DbQuery("UPDATE cards SET position = {$new_position} WHERE card_id = {$id1}");

                $g->notify->all(
                    "challengeShow",
                    '',
                    [
                        'attaquant' => $this->player_id,
                        'defenseur' => $opponent,
                        'set' => $parg2
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
                $g->addPending($opponent, "Challenge2Step3v2", 2);


            }

            else
            {
                $card1select = $g->cards_DB->getCard($id1);
                $card2select = $g->cards_DB->getCard($id2);
                $card1 = [];
                $card2 = [];

                if($card1select['type'] <= $card2select['type'])
                {
                    $card1 = $card1select;
                    $card2 = $card2select;
                }
                else
                {
                    $card1 = $card2select;
                    $card2 = $card1select;
                }

                $position = count($g->getObjectListFromDB( "SELECT card_id FROM cards WHERE card_location = 'challenge_attack'", true ));
                $new_position1 = $position + 1;
                $new_position2 = $position + 2;
                
                $g->cards_DB->moveCard($card1['id'], 'challenge_attack', $this->player_id);
                $g->DbQuery("UPDATE cards SET position = {$new_position1} WHERE card_id = {$card1['id']}");

                $g->cards_DB->moveCard($card2['id'], 'challenge_attack', $this->player_id);
                $g->DbQuery("UPDATE cards SET position = {$new_position2} WHERE card_id = {$card2['id']}");

                $g->notify->all(
                    "challengeShow",
                    '',
                    [
                        'attaquant' => $this->player_id,
                        'defenseur' => $opponent,
                        'set' => $parg2
                    ]
                );


                $opponent_name = $g->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id='{$opponent}'");
                $opponent_color = $g->getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id='{$opponent}'");

                $txt = clienttranslate('${player_name} challenges ${opponent} with: ${log}');
                $g->notify->all(
                    "cardMoveChallengeAttack2",
                    $txt,
                    [
                        'opponent' =>    [
                            'log' => '<b style="color: #${color};">${opponent_name}</b>',
                            'args' => ['opponent_name' => $opponent_name, 'color' => $opponent_color]
                        ],
                        'player_id' => $this->player_id,
                        'card1' => $card1,
                        'card2' => $card2,
                        'log' => $this->getSetLog($card1['type'], $card2['type']),
                        
                    ]
                );

                $g->hand->inc($this->player_id, -2);
                $g->addPending($opponent, "Challenge2Step3", 2);


            }
  
            
        }
  
        
    }


    ////////////////////////////////////////////////////////////////////
    ////           le defenseur se fait attaquer par un Wild      //////
    ////////////////////////////////////////////////////////////////////

    function argChallenge2Step3v2($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selectablemulti2"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret["selectedmulti2"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must choose an action');
        $ret['titleyou'] = clienttranslate('Challenge: ${you} must choose 2 cards or 1 wild card');

        $g = game::$instance;

        $set_attaque = (int)$parg1; // 1 (last) ou 2 (second last)   

        $attaquant = game::$instance->getGameStateValue("attaquant");
        $defenseur = game::$instance->getGameStateValue("defenseur");
          
        $nb_set = $g->set->get($defenseur);


        $set_card = [];

        if($set_attaque == 1)
        {
            $set = $nb_set;
            $set_card = game::$instance->getObjectFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$defenseur}' AND position = '{$set}' ORDER BY card_type ASC LIMIT 1");
        }

        if($set_attaque == 2)
        {
            $set = $nb_set - 1;
            $set_card = game::$instance->getObjectFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$defenseur}' AND position = '{$set}' ORDER BY card_type ASC LIMIT 1");
        }

        

        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $defenseur);

        $assetCounts = [];
        $jokerCount = 0;
        foreach ($handCards as $card) {
            $type = (int) $card['type'];
            if ($type >= 1 && $type <= 11) {
                if (!isset($assetCounts[$type])) {
                    $assetCounts[$type] = 0;
                }
                $assetCounts[$type]++;
            } 

            if ($type == 12 || $type == 13) {
                $jokerCount++;
            }
        }


        
        foreach ($handCards as $handCard)
        {
            if(($handCard['type'] == $set_card['type'])&&($handCard['type']<= 10))
            {
                if($assetCounts[$set_card['type']] >= 2)
                {
                    $ret["selectablemulti2"][] = 'my_cards_item_'.$handCard['id'];
                }

                elseif(($assetCounts[$set_card['type']] == 1)&&($jokerCount >= 1))
                {
                    $ret["selectablemulti2"][] = 'my_cards_item_'.$handCard['id'];
                }

            }

            if(($handCard['type'] == 12)||($handCard['type'] == 13))
            {
                if($jokerCount >= 2)
                {
                    $ret["selectablemulti2"][] = 'my_cards_item_'.$handCard['id'];
                }

                elseif(isset($assetCounts[$set_card['type']]))
                {

                    $ret["selectablemulti2"][] = 'my_cards_item_'.$handCard['id'];
                }

                
            }
            

            if($handCard['type'] == 11)
            {
                $ret["selectablemulti2"][] = 'my_cards_item_'.$handCard['id'];
            }
        }

        if(count($ret["selectablemulti2"]) >= 1)
        {
            $ret['buttons'][] = 'validate_btn'; 
        }
        

        $ret['buttons'][] = 'abandon_btn';
        
        return $ret;
    }



    function Challenge2Step3v2($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {
        $g = game::$instance;

        if($varg1 == 'abandon_btn')
        {
            $g->addPending($this->player_id, "Challenge2Step4", $parg1);
        }

        else
        {
            $ids = explode('_', $varg2);
            $countIds = count($ids);
            $id1 = (int)$ids[0];
            $id2 = 0;

            if($countIds == 2)
            {
                $id2 = (int)$ids[1];
            }

            if($countIds == 1)
            {
                $card = $g->cards_DB->getCard($id1);
                $position = count($g->getObjectListFromDB( "SELECT card_id FROM cards WHERE card_location = 'challenge_defense'", true ));
                $new_position = $position + 1;
                $g->cards_DB->moveCard($id1, 'challenge_defense', $this->player_id);
                $g->DbQuery("UPDATE cards SET position = {$new_position} WHERE card_id = {$id1}");
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
                $attaquant = game::$instance->getGameStateValue("attaquant");
                $g->addPending($attaquant, "Challenge2Step3", $parg1);
            }


            if($countIds == 2)
            {
                $card1select = $g->cards_DB->getCard($id1);
                $card2select = $g->cards_DB->getCard($id2);
                $card1 = [];
                $card2 = [];

                if($card1select['type'] <= $card2select['type'])
                {
                    $card1 = $card1select;
                    $card2 = $card2select;
                }
                else
                {
                    $card1 = $card2select;
                    $card2 = $card1select;
                }

                $position = count($g->getObjectListFromDB( "SELECT card_id FROM cards WHERE card_location = 'challenge_defense'", true ));
                $new_position1 = $position + 1;
                $new_position2 = $position + 2;
                
                $g->cards_DB->moveCard($card1['id'], 'challenge_defense', $this->player_id);
                $g->DbQuery("UPDATE cards SET position = {$new_position1} WHERE card_id = {$card1['id']}");

                $g->cards_DB->moveCard($card2['id'], 'challenge_defense', $this->player_id);
                $g->DbQuery("UPDATE cards SET position = {$new_position2} WHERE card_id = {$card2['id']}");

            

                $txt = clienttranslate('${player_name} responds to the challenge with: ${log}');
                $g->notify->all(
                    "cardMoveChallengeDefense2",
                    $txt,
                    [
                        'player_id' => $this->player_id,
                        'card1' => $card1,
                        'card2' => $card2,
                        'log' => $this->getSetLog($card1['type'], $card2['type']),
                        
                    ]
                );

                $g->hand->inc($this->player_id, -2);
                
                $attaquant = game::$instance->getGameStateValue("attaquant");
                $g->addPending($attaquant, "Challenge2Step3", $parg1);
            }
            
        }
        
  
        
    }



    ////////////////////////////////////////////////////////////////////
    ///////         DEFI tour par tour jusqu'a abandon         /////////
    ////////////////////////////////////////////////////////////////////

    function argChallenge2Step3($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selectablemulti"] = [];
        $ret["selected"] = [];
        $ret["selectedmulti"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must respond to the Challenge');
        

        $g = game::$instance;

        $attaquant = game::$instance->getGameStateValue("attaquant");
        $defenseur = game::$instance->getGameStateValue("defenseur");

        $card = [];

        $set_attaque = $parg1;   ///// 1 (last) ou 2 (second last)     
        $nb_set = $g->set->get($defenseur);
        $set = 0;

        if($set_attaque == 1)
        {
            $set = $nb_set;
            $card = game::$instance->getObjectFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$defenseur}' AND position = '{$set}' ORDER BY card_type ASC LIMIT 1");
        }

        if($set_attaque == 2)
        {
            $set = $nb_set - 1;
            $card = game::$instance->getObjectFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$defenseur}' AND position = '{$set}' ORDER BY card_type ASC LIMIT 1");
        }

          
        $handCards = game::$instance->cards_DB->getCardsInLocation('hand', $this->player_id);

        foreach ($handCards as $handCard)
        {
            if($handCard['type'] == $card['type'])
            {
                $ret["selectable"][] = 'my_cards_item_'.$handCard['id'];
            }

            if($handCard['type'] == 12 || $handCard['type'] == 13)
            {
                $ret["selectable"][] = 'my_cards_item_'.$handCard['id'];
            }
        }

        if(count($ret["selectable"]) != null)
        {
            $ret['titleyou'] = clienttranslate('Challenge: ${you} must choose a card');
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
            $g->addPending($this->player_id, "Challenge2Step4", $parg1);
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
            $g->addPending($defenseur, "Challenge2Step3", $parg1);
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
            $g->addPending($attaquant, "Challenge2Step3", $parg1);
        }

        }

        
        
    }

    ////////////////////////////////////////////////////////////////////
    ///////////                Abandon                    //////////////
    ////////////////////////////////////////////////////////////////////

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
        $defenseur_first_turn = game::$instance->getGameStateValue("defenseur_first_turn");

        $set_attaque = $parg1;   ///// 1 (last) ou 2 (second last)     
        $nb_set = $g->set->get($defenseur);

        $cards_defi = $g->getObjectListFromDB("SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='challenge_attack' OR `card_location` ='challenge_defense'");
        
        if($set_attaque == 1)
        {
            $max_position_set_attaquant = $g->getUniqueValueFromDB("SELECT MAX(position) AS valeur_max FROM cards WHERE card_location = 'set' AND card_location_arg = '{$attaquant}'");
            $max_position_set_defenseur = $g->getUniqueValueFromDB("SELECT MAX(position) AS valeur_max FROM cards WHERE card_location = 'set' AND card_location_arg = '{$defenseur}'");

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
                $g->setGameStateValue("win_first_turn", 1);

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

                $attaquant_last_set_type = self::getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$attaquant}' ORDER BY `position` DESC, `card_type` ASC LIMIT 1" );
                $defenseur_last_set_type = self::getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$defenseur}' ORDER BY `position` DESC, `card_type` ASC LIMIT 1" );

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
                        'attaquant_last_set_type' => $attaquant_last_set_type,
                        'defenseur_last_set_type' => $defenseur_last_set_type
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
        }

        if($set_attaque == 2)
        {
            $new_position_attaquant = $g->set->get($attaquant) + 1;
            $position_defenseur = $g->set->get($defenseur) - 1;
            $max_position_defenseur = $g->set->get($defenseur);

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
                    $g->DbQuery("UPDATE cards SET position = {$position_defenseur} WHERE card_id = {$card_defi['id']}");
                }



            }


            /* c'est le defenseur qui abandonne*/
            if($defenseur == $this->player_id)
            {
                $g->setGameStateValue("win_first_turn", 1);

                $g->DbQuery("UPDATE cards SET card_location_arg = {$attaquant}, position = {$new_position_attaquant} WHERE position = '{$position_defenseur}' AND card_location_arg = '{$defenseur}'");
                $g->DbQuery("UPDATE cards SET position = {$position_defenseur} WHERE position = '{$max_position_defenseur}' AND card_location_arg = '{$defenseur}'");

                foreach ($cards_defi as $card_defi)
                {
                    $g->cards_DB->moveCard($card_defi['id'], 'set', $attaquant);
                    $g->DbQuery("UPDATE cards SET position = {$new_position_attaquant} WHERE card_id = {$card_defi['id']}");
                }

                $attaquant_cards = [];
                $sets = self::getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$attaquant}' AND position >= 1 ORDER BY position ASC, card_type ASC");
                foreach ($sets as $card) {
                    // On garde la première carte rencontrée par position
                    if (!isset($attaquant_cards[$card['position']])) {
                        $attaquant_cards[$card['position']] = $card;
                    }
                }

                $defenseur_cards = [];
                $sets = self::getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$defenseur}' AND position >= 1 ORDER BY position ASC, card_type ASC");
                foreach ($sets as $card) {
                    // On garde la première carte rencontrée par position
                    if (!isset($defenseur_cards[$card['position']])) {
                        $defenseur_cards[$card['position']] = $card;
                    }
                }

                $attaquant_last_set_type = self::getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$attaquant}' ORDER BY `position` DESC, `card_type` ASC LIMIT 1" );


                $txt = clienttranslate('${player_name} abandons the challenge');
                $g->notify->all(
                    "challengeWinByAttack2",
                    $txt,
                    [
                        'player_id' => $defenseur,
                        'cards' => $cards_defi,
                        'attaquant' => $attaquant,
                        'defenseur' => $defenseur,
                        'attaquant_last_set_type' => $attaquant_last_set_type,
                        'attaquant_cards' => $attaquant_cards,
                        'defenseur_cards' => $defenseur_cards,
                        'set_attaque' => $position_defenseur

                        
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



        }

        $this->majSetCounters($attaquant);
        $this->majSetCounters($defenseur);
        $this->Lock();

        if($this->player_turn == 2)
        {
            $count_hand_attaquant = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'hand' AND card_location_arg = '{$attaquant}'", true ));
            $count_hand_defenseur = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'hand' AND card_location_arg = '{$defenseur}'", true ));
            $count_hand_defenseur_first_turn = count($g->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'hand' AND card_location_arg = '{$defenseur_first_turn}'", true ));
            $draw_attaquant = 6 - $count_hand_attaquant;
            $draw_defenseur = 6 - $count_hand_defenseur;
            $draw_defenseur_first_turn = 6 - $count_hand_defenseur_first_turn;

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

            if(($draw_defenseur_first_turn < 6)&&($count_deck >= 1)&&($defenseur_first_turn != 0))
            {
                if($count_deck >= $draw_defenseur_first_turn)
                {
                    $draw = $draw_defenseur_first_turn;
                }
                else
                {
                    $draw = $count_deck;
                }

                $newcards = $g->cards_DB->pickCards($draw,'deck', $defenseur_first_turn);
                $g->notify->player(
                    $defenseur_first_turn,
                    "drawCards",
                    '',
                    [
                        'player_id' => $defenseur_first_turn,
                        'cards' => $newcards,
                    ]
                );

                $g->hand->inc($defenseur_first_turn, $draw);
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
            $g->setGameStateValue("defenseur_first_turn", 0);
            $g->setGameStateValue("win_first_turn", 0);
            $g->setGameStateValue("player_turn", 1);
            $g->DbQuery("UPDATE `challenge` SET `value` = '0' WHERE `name` = 'set'");

            $g->addPendingFirst($attaquant, "PlayerTurn2");
        }

        if($this->player_turn == 1)
        {
            $g->setGameStateValue("player_turn", 2);
            $g->setGameStateValue("challenge", 0);
            $g->addPending($attaquant, "PlayerTurn2");


        }
        
    }

    ////////////////////////////////////////////////////////////////////
    ///////////               End of Round                   ///////////
    ////////////////////////////////////////////////////////////////////

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