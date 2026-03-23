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
        


        return $ret;
    }



    function PlayerTurn($parg1, $parg2, $varg1, $varg2, $varg3, $varg4)
    {

        
        game::$instance->addPending($this->player_id, "PlayerTurn");
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
