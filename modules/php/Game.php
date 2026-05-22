<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * coveryourassets implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\coveryourassets;

use Bga\Games\coveryourassets\States\NormalTurn;
use Bga\GameFramework\Components\Counters\PlayerCounter;
use Bga\GameFramework\Components\Counters\TableCounter;
use Bga\GameFramework\UserException;

class Game extends \Bga\GameFramework\Table
{
    // material
    public array $_BUILDING_CARD;

    // counters table
    public TableCounter $deck;
    

    // counters players
    public playerCounter $hand;
    public playerCounter $set;
    public playerCounter $last_set;
    public playerCounter $second_to_last_set;
    public playerCounter $cumul_score;
    public playerCounter $first_set;
    
    //databases decks
    public $cards_DB;
    


    public static $instance = null; //ATTENTION pending MAthCt

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If you want to store any type instead of int, use $this->globals instead.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();

        require 'Material.php';

        $this->initGameStateLabels([

            // GSV
            "challenge" => 10,
            "attaquant" => 11,
            "defenseur" => 12,
            "round" => 13,
            "player_turn" => 14,
            "defenseur_first_turn" => 15,
            "win_first_turn" => 16,

            // options
            'game_mode' => 100,
            'winning_condition' => 101,

        ]); // mandatory, even if the array is empty

        self::$instance = $this; // ATTENTION pending MAthCt


        // counters
        $this->deck = $this->counterFactory->createTableCounter('deck');
       
        $this->hand = $this->counterFactory->createPlayerCounter('hand');
        $this->set = $this->counterFactory->createPlayerCounter('set');
        $this->last_set = $this->counterFactory->createPlayerCounter('last_set');
        $this->second_to_last_set = $this->counterFactory->createPlayerCounter('second_to_last_set');
        $this->cumul_score = $this->counterFactory->createPlayerCounter('cumul_score');
        $this->first_set = $this->counterFactory->createPlayerCounter('first_set');
       

        // Deck db_card created with table card 
        $this->cards_DB = $this->deckFactory->createDeck("cards");
        


        // Complète les args des notifications : le 1er paramètre du décorateur est souvent le *type* de notif
        // (ex. cardsMovedToTable), pas le texte du log — ne pas tester str_contains($message, '${player_name}').
        $this->notify->addDecorator(function (string $message, array $args) {
            if (isset($args['player_id']) && !isset($args['player_name'])) {
                $args['player_name'] = $this->getPlayerNameById((int) $args['player_id']);
            }

            return $args;
        });
    }

    /////////////////////////////////////////////////////////////////////////////////  
    //       _____                        _____       _ _   _       _ _          _   _             
    //      / ____|                      |_   _|     (_) | (_)     | (_)        | | (_)            
    //     | |  __  __ _ _ __ ___   ___    | |  _ __  _| |_ _  __ _| |_ ______ _| |_ _  ___  _ __  
    //     | | |_ |/ _` | '_ ` _ \ / _ \   | | | '_ \| | __| |/ _` | | |_  / _` | __| |/ _ \| '_ \ 
    //     | |__| | (_| | | | | | |  __/  _| |_| | | | | |_| | (_| | | |/ / (_| | |_| | (_) | | | |
    //      \_____|\__,_|_| |_| |_|\___| |_____|_| |_|_|\__|_|\__,_|_|_/___\__,_|\__|_|\___/|_| |_|
    //                                                                                               
    /////////////////////////////////////////////////////////////////////////////////   

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {

        //gsv
        $this->setGameStateInitialValue("challenge", 0);
        $this->setGameStateInitialValue("attaquant", 0);
        $this->setGameStateInitialValue("defenseur", 0);
        $this->setGameStateInitialValue("defenseur_first_turn", 0);
        $this->setGameStateInitialValue("win_first_turn", 0);
        $this->setGameStateInitialValue("round", 1);
        $this->setGameStateInitialValue("player_turn", 1);

        
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        //stats
        //$this->bga->playerStats->init('nomstats', 0);
        

        //INIT DES TABLES DB

        //creation du deck
        $cards = [];
        $cards[] = ['type' => 1, 'type_arg' => 0, 'nbr' => 10]; //10
        $cards[] = ['type' => 2, 'type_arg' => 0, 'nbr' => 10]; //10
        $cards[] = ['type' => 3, 'type_arg' => 0, 'nbr' => 10]; //10
        $cards[] = ['type' => 4, 'type_arg' => 0, 'nbr' => 9]; //9
        $cards[] = ['type' => 5, 'type_arg' => 0, 'nbr' => 9]; //9
        $cards[] = ['type' => 6, 'type_arg' => 0, 'nbr' => 9]; //9
        $cards[] = ['type' => 7, 'type_arg' => 0, 'nbr' => 9]; //9
        $cards[] = ['type' => 8, 'type_arg' => 0, 'nbr' => 9]; //9
        $cards[] = ['type' => 9, 'type_arg' => 0, 'nbr' => 9]; //9
        $cards[] = ['type' => 10, 'type_arg' => 0, 'nbr' => 8]; //8
        $cards[] = ['type' => 12, 'type_arg' => 0, 'nbr' => 8]; //8
        $cards[] = ['type' => 13, 'type_arg' => 0, 'nbr' => 4]; //4

        if($this->getGameStateValue('game_mode') == 2)
        {
            $cards[] = ['type' => 11, 'type_arg' => 0, 'nbr' => 2]; //2
            $cards[] = ['type' => 14, 'type_arg' => 0, 'nbr' => 2]; //2
            $cards[] = ['type' => 15, 'type_arg' => 0, 'nbr' => 2]; //2
        }

        $this->cards_DB->createCards($cards, 'deck');
        $this->cards_DB->shuffle('deck');

        //valeur des cartes
        $this->DbQuery("UPDATE cards SET `value` = 5000 WHERE `card_type` = 1");
        $this->DbQuery("UPDATE cards SET `value` = 5000 WHERE `card_type` = 2");
        $this->DbQuery("UPDATE cards SET `value` = 5000 WHERE `card_type` = 3");
        $this->DbQuery("UPDATE cards SET `value` = 10000 WHERE `card_type` = 4");
        $this->DbQuery("UPDATE cards SET `value` = 10000 WHERE `card_type` = 5");
        $this->DbQuery("UPDATE cards SET `value` = 10000 WHERE `card_type` = 6");
        $this->DbQuery("UPDATE cards SET `value` = 15000 WHERE `card_type` = 7");
        $this->DbQuery("UPDATE cards SET `value` = 15000 WHERE `card_type` = 8");
        $this->DbQuery("UPDATE cards SET `value` = 15000 WHERE `card_type` = 9");
        $this->DbQuery("UPDATE cards SET `value` = 20000 WHERE `card_type` = 10");
        $this->DbQuery("UPDATE cards SET `value` = 25000 WHERE `card_type` = 12");
        $this->DbQuery("UPDATE cards SET `value` = 50000 WHERE `card_type` = 13");

        if($this->getGameStateValue('game_mode') == 2)
        {
            $this->DbQuery("UPDATE cards SET `value` = 1000 WHERE `card_type` = 11");
        }
        
        //distribution des cartes
        if($this->getGameStateValue('game_mode') == 1)
        {
            foreach ($players as $player_id => $player) {
                $this->cards_DB->pickCards(5, 'deck', (int) $player_id);
            }
        }

        if($this->getGameStateValue('game_mode') == 2)
        {
            foreach ($players as $player_id => $player) {
                $this->cards_DB->pickCards(6, 'deck', (int) $player_id);
            }
        }

        //mise en defausse de la premier carte du deck
        $this->cards_DB->pickCardForLocation('deck', 'discard', 0);

        $count_deck = count($this->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));

        //counters
        $this->deck->initDb($count_deck);

        if($this->getGameStateValue('game_mode') == 1)
        {
            $this->hand->initDb(array_keys($players), 5);
        }

        if($this->getGameStateValue('game_mode') == 2)
        {
            $this->hand->initDb(array_keys($players), 6);
        }
        
        $this->set->initDb(array_keys($players), 0);
        $this->last_set->initDb(array_keys($players), 0);
        $this->second_to_last_set->initDb(array_keys($players), 0);
        $this->cumul_score->initDb(array_keys($players), 0);
        $this->first_set->initDb(array_keys($players), 0);


        // init table Challenge
        $this->DbQuery("INSERT INTO `challenge` (`name`) VALUES ('set')");




        
        // Init global values with their initial values.

        // Init game statistics.
        //
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.

        // Dummy content.
        // $this->tableStats->init('table_teststat1', 0);
        // $this->playerStats->init('player_teststat1', 0);

        // TODO: Setup the initial game situation here.

        // Activate first player once everything has been initialized and ready.
        //$this->activeNextPlayer();

        //return PlayerTurn::class;


        if($this->getGameStateValue('game_mode') == 1)
        {
            foreach (array_keys($players) as $player_id) {
                $this->addPendingFirst($player_id, "PlayerTurn");
            }
        }

        if($this->getGameStateValue('game_mode') == 2)
        {
            foreach (array_keys($players) as $player_id) {
                $this->addPendingFirst($player_id, "PlayerTurn2");
            }
        }

        $first = (int)$this->getUniqueValueFromDB("SELECT player_id FROM player WHERE player_no = 1");
        $this->addPending($first, "Round1");
    }

    /////////////////////////////////////////////////////////////////////////////////  
    //     _____                      _____                                   _             
    //    / ____|                    |  __ \                                 (_)            
    //   | |  __  __ _ _ __ ___   ___| |__) | __ ___   __ _ _ __ ___  ___ ___ _  ___  _ __  
    //   | | |_ |/ _` | '_ ` _ \ / _ \  ___/ '__/ _ \ / _` | '__/ _ \/ __/ __| |/ _ \| '_ \ 
    //   | |__| | (_| | | | | | |  __/ |   | | | (_) | (_| | | |  __/\__ \__ \ | (_) | | | |
    //    \_____|\__,_|_| |_| |_|\___|_|   |_|  \___/ \__, |_|  \___||___/___/_|\___/|_| |_|
    //                                                 __/ |                                
    //                                                |___/                                 
    /////////////////////////////////////////////////////////////////////////////////  

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        $game_mode = $this->getGameStateValue('game_mode');
        $winning_condition = $this->getGameStateValue('winning_condition');
        $max_score = intval($this->getUniqueValueFromDB("SELECT MAX(cumul_value) AS valeur_max FROM player"));
        $nb_players = count($this->getObjectListFromDB( "SELECT player_id FROM player", true ));
        $round = intval($this->getGameStateValue("round"));
        $round_win = intval($this->getUniqueValueFromDB("SELECT MAX(round_win) AS valeur_max FROM player"));

        if($winning_condition == 1)
        {
            $progression = floor($max_score*100/1000000);
            return $progression;
        }
        elseif($winning_condition == 2)
        {
            $nb_deck_max = 0;
            if($game_mode == 1)
            {
                $nb_deck_max = 104 - 1 - ($nb_players*5);
            }
            else
            {
                $nb_deck_max = 110 - 1 - ($nb_players*6);
            }

            $deck = count($this->getObjectListFromDB( "SELECT `card_id` `id` FROM cards WHERE card_location = 'deck'", true ));
            $progression = floor(100 - ($deck*100/$nb_deck_max));

            
            return $progression;
        }
        elseif($winning_condition == 3)
        {
            $progression = floor($round*100/3);
            return $progression;
        }
        elseif($winning_condition == 4)
        {
            $progression = floor($round_win*100/2);
            return $progression;
        }
        else
        {
            return 0;
        }
        
        
    }



    /////////////////////////////////////////////////////////////////////////////////  
    //               _            _ _ _____        _            
    //              | |     /\   | | |  __ \      | |           
    //     __ _  ___| |_   /  \  | | | |  | | __ _| |_ __ _ ___ 
    //    / _` |/ _ \ __| / /\ \ | | | |  | |/ _` | __/ _` / __|
    //   | (_| |  __/ |_ / ____ \| | | |__| | (_| | || (_| \__ \
    //    \__, |\___|\__/_/    \_\_|_|_____/ \__,_|\__\__,_|___/
    //     __/ |                                                
    //    |___/                                                 
    /////////////////////////////////////////////////////////////////////////////////

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas(): array
    {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();
        $players = self::getObjectListFromDB( "SELECT `player_id` FROM `player`", true );

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score`, `player_color` `color` FROM `player`"
        );

        $sql = "SELECT player_no no FROM player WHERE player_id = $current_player_id";
        $current_player_no = $this->getUniqueValueFromDb($sql);
        if (is_null($current_player_no)) {
            $current_player_no = 0;
        }

        // ordered players list
        $sql = "SELECT player_no no, player_id id, player_score score, player_name name, player_color color 
                FROM player
                ORDER BY (player_no >= $current_player_no) DESC, player_no ASC";
        $ordered_list = $this->getObjectListFromDB($sql);
        $result['players_ordered'] = $ordered_list;

        $result['all_cards'] = self::getCollectionFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards`" );        
        $result['my_hand'] = self::getCollectionFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='hand' AND `card_location_arg`='{$current_player_id}'" );
        $result['table'] = self::getCollectionFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='table'" );
        $result['discard'] = self::getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='discard' ORDER BY `position` ASC" );

        
        foreach ($players as $player)
        {
            $sets = self::getObjectListFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' AND position >= 1 ORDER BY position ASC, card_type ASC");
            foreach ($sets as $card) {
                // On garde la première carte rencontrée par position
                if (!isset($result['set'][$player][$card['position']])) {
                    $result['set'][$player][$card['position']] = $card;
                }
            }
        }

        $result['challenge'] = $this->getGameStateValue("challenge");
        $result['attaquant'] = $this->getGameStateValue("attaquant");
        $result['defenseur'] = $this->getGameStateValue("defenseur");
        $result['challenge_attack'] = self::getCollectionFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='challenge_attack' ORDER BY `position` ASC" );
        $result['challenge_defense'] = self::getCollectionFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` `location`, `card_location_arg` `location_arg`, `position` `position` FROM `cards` WHERE `card_location` ='challenge_defense' ORDER BY `position` ASC" );

        foreach ($players as $player)
        {
            $position_max = self::getUniqueValueFromDB( "SELECT `position` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' ORDER BY `position` DESC LIMIT 1" );
            $result['max_position_set'][$player] = (int)$position_max;
            
        }

        foreach ($players as $player)
        {
            $last_set_type = self::getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `card_location_arg`='{$player}' ORDER BY `position` DESC, `card_type` ASC LIMIT 1" );
            $result['last_set_type'][$player] = (int)$last_set_type;
            
        }

        foreach ($players as $player)
        {
            $first_set_type = self::getUniqueValueFromDB( "SELECT `card_type` FROM `cards` WHERE `card_location` ='set' AND `position` = 1 AND `card_location_arg`='{$player}' ORDER BY `card_type` ASC LIMIT 1" );
            $result['first_set_type'][$player] = (int)$first_set_type;
            
        }

    
        
        //counters
        $this->deck->fillResult($result);
        $this->hand->fillResult($result);
        $this->set->fillResult($result);
        $this->last_set->fillResult($result);
        $this->second_to_last_set->fillResult($result);
        $this->cumul_score->fillResult($result);
        $this->first_set->fillResult($result);

        //mode_end
        $result['mode_end'] = $this->getGameStateValue('winning_condition');

        //mode
        $result['mode'] = $this->getGameStateValue('game_mode');

        //set attack
        $result['set_attack'] = self::getUniqueValueFromDB( "SELECT `value` FROM `challenge` WHERE `name` ='set'");
               

        // TODO: Gather all information about current game situation (visible by player $current_player_id).

        return $result;
    }


    /////////////////////////////////////////////////////////////////////////////////  
    //     _    _ _   _ _ _ _            __                  _   _                 
    //    | |  | | | (_) (_) |          / _|                | | (_)                
    //    | |  | | |_ _| |_| |_ _   _  | |_ _   _ _ __   ___| |_ _  ___  _ __  ___ 
    //    | |  | | __| | | | __| | | | |  _| | | | '_ \ / __| __| |/ _ \| '_ \/ __|
    //    | |__| | |_| | | | |_| |_| | | | | |_| | | | | (__| |_| | (_) | | | \__ \
    //     \____/ \__|_|_|_|\__|\__, | |_|  \__,_|_| |_|\___|\__|_|\___/|_| |_|___/
    //                           __/ |                                             
    //                          |___/                                              
    /////////////////////////////////////////////////////////////////////////////////  

    // le pending sera exécuté juste après
    public function addPending(int $player_id, string $function, ?string $arg = NULL, ?string $arg2 = NULL, ?string $arg3 = NULL, ?string $arg4 = NULL): void
    {
        $sql = "INSERT INTO `pending` (`player_id`, `function`, `arg`, `arg2`, `arg3`, `arg4`) 
                VALUES (" . $player_id . ", '" . $function . "', '" . $arg . "', '" . $arg2 . "', '" . $arg3 . "', '" . $arg4 . "')";
        $this->DbQuery($sql);
    }


    // le pending est envoyé au fond (First mais on lit de Bas en Haut)
    public function addPendingFirst(int $player_id, string $function, ?string $arg = NULL, ?string $arg2 = NULL, ?string $arg3 = NULL, ?string $arg4 = NULL): void
    {
        $minid = $this->getUniqueValueFromDB("SELECT MIN(`id`) FROM `pending`") - 1;
        $sql = "INSERT INTO `pending` (`id`, `player_id`, `function`, `arg`, `arg2`, `arg3`, `arg4`)
                VALUES (" . $minid . "," . $player_id . ", '" . $function . "', '" . $arg . "', '" . $arg2 . "', '" . $arg3 . "', '" . $arg4 . "')";
        $this->DbQuery($sql);
    }

    ///////////////////////////////////////////////////////////////////////////////// 
    //      _____                            _        _                    _   _                 
    //     / ____|                          | |      | |                  | | (_)                
    //    | |  __  __ _ _ __ ___   ___   ___| |_ __ _| |_ ___    __ _  ___| |_ _  ___  _ __  ___ 
    //    | | |_ |/ _` | '_ ` _ \ / _ \ / __| __/ _` | __/ _ \  / _` |/ __| __| |/ _ \| '_ \/ __|
    //    | |__| | (_| | | | | | |  __/ \__ \ || (_| | ||  __/ | (_| | (__| |_| | (_) | | | \__ \
    //     \_____|\__,_|_| |_| |_|\___| |___/\__\__,_|\__\___|  \__,_|\___|\__|_|\___/|_| |_|___/
    //                                                                                       
    /////////////////////////////////////////////////////////////////////////////////     


    public function callPending($pending, $execute, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null)
    {
        // Par défaut, on appelle la fonction sur l'objet principal du jeu
        $obj = $this;

        // Si l'action pending est liée à un joueur précis,
        // on crée un objet Pending pour ce joueur
        if ($pending['player_id'] != null) {
            $obj = new Pending($pending['player_id']);
        }

        // Nom de la fonction à appeler
        $fname = "";

        // Si on est en phase de préparation (pas d'exécution),
        // on appelle la version "arg..." de la fonction
        if (!$execute) {
            $fname .= "arg";
        }

        // On ajoute le nom réel de la fonction stocké dans le pending
        // Exemple :
        //  - arg + drawCard  → argdrawCard
        //  - drawCard        → drawCard
        $fname .= $pending['function'];

        // Valeur de retour par défaut
        $ret = null;

        // On vérifie que la fonction existe avant de l'appeler
        if (method_exists($obj, $fname)) {

            // Appel de la fonction avec :
            // - les arguments enregistrés dans le pending
            // - les arguments supplémentaires passés à callPending
            $ret = $obj->$fname(
                $pending['arg'],
                $pending['arg2'],
                $arg1,
                $arg2,
                $arg3,
                $arg4
            );
        }

        // On retourne le résultat de la fonction appelée
        return $ret;
    }

    
    /////////////////////////////////////////////////////////////////////////////////
    //    ______               _     _      
    //   |___  /              | |   (_)     
    //      / / ___  _ __ ___ | |__  _  ___ 
    //     / / / _ \| '_ ` _ \| '_ \| |/ _ \
    //    / /_| (_) | | | | | | |_) | |  __/
    //   /_____\___/|_| |_| |_|_.__/|_|\___|
    //                                   
    /////////////////////////////////////////////////////////////////////////////////     

    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default: {
                        $player_id = $this->getActivePlayerId();
                        self::DbQuery("DELETE FROM pending WHERE player_id = {$player_id}");
                        $this->gamestate->nextState("zombiePass");
                        break;
                    }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \BgaSystemException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }

    ///////////////////////////////////////////////////////////////////////////////// 
    //     _____  ____                                    _      
    //    |  __ \|  _ \                                  | |     
    //    | |  | | |_) |  _   _ _ __   __ _ _ __ __ _  __| | ___ 
    //    | |  | |  _ <  | | | | '_ \ / _` | '__/ _` |/ _` |/ _ \
    //    | |__| | |_) | | |_| | |_) | (_| | | | (_| | (_| |  __/
    //    |_____/|____/   \__,_| .__/ \__, |_|  \__,_|\__,_|\___|
    //                         | |     __/ |                     
    //                         |_|    |___/                      
    /////////////////////////////////////////////////////////////////////////////////  

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
        //       if ($from_version <= 1404301345)
        //       {
        //            // ! important ! Use `DBPREFIX_<table_name>` for all tables
        //
        //            $sql = "ALTER TABLE `DBPREFIX_xxxxxxx` ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
        //
        //       if ($from_version <= 1405061421)
        //       {
        //            // ! important ! Use `DBPREFIX_<table_name>` for all tables
        //
        //            $sql = "CREATE TABLE `DBPREFIX_xxxxxxx` ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
    }

    ///////////////////////////////////////////////////////////////////////////////// 
    //     _____       _                 
    //    |  __ \     | |                
    //    | |  | | ___| |__  _   _  __ _ 
    //    | |  | |/ _ \ '_ \| | | |/ _` |
    //    | |__| |  __/ |_) | |_| | (_| |
    //    |_____/ \___|_.__/ \__,_|\__, |
    //                            __/ |
     //                           |___/ 
    ///////////////////////////////////////////////////////////////////////////////// 

    /**
     * Example of debug function.
     * Here, jump to a state you want to test (by default, jump to next player state)
     * You can trigger it on Studio using the Debug button on the right of the top bar.
     */
    public function debug_goToState(int $state = 3)
    {
        $this->gamestate->jumpToState($state);
    }

    /**
     * Another example of debug function, to easily test the zombie code.
     */
    public function debug_playOneMove()
    {
        $this->debug->playUntil(fn(int $count) => $count == 1);
    }

    /*
    Another example of debug function, to easily create situations you want to test.
    Here, put a card you want to test in your hand (assuming you use the Deck component).

    public function debug_setCardInHand(int $cardType, int $playerId) {
        $card = array_values($this->cards->getCardsOfType($cardType))[0];
        $this->cards->moveCard($card['id'], 'hand', $playerId);
    }
    */
}
