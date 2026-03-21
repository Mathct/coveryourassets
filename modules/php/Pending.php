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
        $ret["selected"] = [];
        $ret['buttons'] = [];
        $ret['title'] = clienttranslate('${actplayer} must ...');
        $ret['titleyou'] = clienttranslate('${you} must ...');


        $ret['buttons'][] = 'yes_btn';
        $ret['buttons'][] = 'no_btn';
        


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
