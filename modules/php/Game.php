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
    // public tableCounter $countertable;
    

    // counters players
    //public playerCounter $counterplayer;
    
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
            //"variable" => 10,

            // options
            //'game_mode'            => 100,

        ]); // mandatory, even if the array is empty

        self::$instance = $this; // ATTENTION pending MAthCt


        // counters
        //$this->countertable = $this->counterFactory->createTableCounter('countertable');
       
        //$this->counterplayer = $this->counterFactory->createPlayerCounter('counterplayer');
       

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
        //$this->setGameStateInitialValue("variable", 0);


        
        //counters
        //$this->countertable->initDb(1);
        //$this->counterplayer->initDb(array_keys($players));
        
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
        $cards[] = ['type' => 1, 'type_arg' => 0, 'nbr' => 10];
        $cards[] = ['type' => 2, 'type_arg' => 0, 'nbr' => 10];
        $cards[] = ['type' => 3, 'type_arg' => 0, 'nbr' => 10];
        $cards[] = ['type' => 4, 'type_arg' => 0, 'nbr' => 9];
        $cards[] = ['type' => 5, 'type_arg' => 0, 'nbr' => 9];
        $cards[] = ['type' => 6, 'type_arg' => 0, 'nbr' => 9];
        $cards[] = ['type' => 7, 'type_arg' => 0, 'nbr' => 9];
        $cards[] = ['type' => 8, 'type_arg' => 0, 'nbr' => 9];
        $cards[] = ['type' => 9, 'type_arg' => 0, 'nbr' => 9];
        $cards[] = ['type' => 10, 'type_arg' => 0, 'nbr' => 8];
        $cards[] = ['type' => 11, 'type_arg' => 0, 'nbr' => 8];
        $cards[] = ['type' => 12, 'type_arg' => 0, 'nbr' => 4];

        $this->cards_DB->createCards($cards, 'deck');
        $this->cards_DB->shuffle('deck');
        
        //distribution des cartes
        foreach ($players as $player_id => $player) {
            $this->cards_DB->pickCards(5, 'deck', (int) $player_id);
        }

        //mise en defausse de la premier carte du deck
        $this->cards_DB->pickCardForLocation('deck', 'discard', 1);

        
        
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



        foreach (array_keys($players) as $player_id) {
            $this->addPendingFirst($player_id, "PlayerTurn");
        }
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
        return 0;
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

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
        );

                
        $result['my_hand'] = self::getCollectionFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` location, `card_location_arg` location_arg FROM `cards` WHERE `card_location` ='hand' AND `card_location_arg`='{$current_player_id}'" );
        $result['table'] = self::getCollectionFromDB( "SELECT `card_id` `id`, `card_type` `type`, `card_type_arg` `type_arg`, `card_location` location, `card_location_arg` location_arg FROM `cards` WHERE `card_location` ='table'" );

        //counters
        //$this->deck_1->fillResult($result);
        //$this->player_ghosts->fillResult($result);
        

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

    /**
     * Carte en main du joueur (ou null).
     *
     * @return ?array{id: int, type: int, type_arg: int, location: string, location_arg: int}
     */
    public function getHandCard(int $playerId, int $cardId): ?array
    {
        $cards = $this->cards_DB->getCardsInLocation('hand', $playerId);
        foreach ($cards as $card) {
            if ((int) $card['id'] === $cardId) {
                return $card;
            }
        }

        return null;
    }

    public function canCreateSetTypes(int $typeA, int $typeB): bool
    {
        $isAsset = static fn (int $t): bool => $t >= 1 && $t <= 10;
        $isJoker = static fn (int $t): bool => $t === 11 || $t === 12;

        if ($isAsset($typeA) && $isAsset($typeB) && $typeA === $typeB) {
            return true;
        }

        if (($isAsset($typeA) && $isJoker($typeB)) || ($isAsset($typeB) && $isJoker($typeA))) {
            return true;
        }

        return false;
    }

    /**
     * Valide et joue un set : les 2 cartes passent de la main à la table, puis notification à tous.
     *
     * @throws UserException
     */
    public function createSetFromHand(int $playerId, int $cardId1, int $cardId2): void
    {
        if ((int) $this->getActivePlayerId() !== $playerId) {
            throw new UserException(clienttranslate('This is not your turn.'));
        }

        if ($cardId1 === $cardId2) {
            throw new UserException(clienttranslate('You must select two different cards.'));
        }

        $c1 = $this->getHandCard($playerId, $cardId1);
        $c2 = $this->getHandCard($playerId, $cardId2);
        if ($c1 === null || $c2 === null) {
            throw new UserException(clienttranslate('These cards are not in your hand.'));
        }

        $t1 = (int) $c1['type'];
        $t2 = (int) $c2['type'];
        if (!$this->canCreateSetTypes($t1, $t2)) {
            throw new UserException(clienttranslate('This is not a valid set.'));
        }

        $this->cards_DB->moveCard($cardId1, 'table', 0);
        $this->cards_DB->moveCard($cardId2, 'table', 0);

        $this->notifyAllPlayers(
            'cardsMovedToTable',
            clienttranslate('${player_name} played a set'),
            [
                'player_id' => $playerId,
                'player_name' => $this->getPlayerNameById($playerId),
                'cards' => [
                    ['id' => $cardId1, 'type' => $t1],
                    ['id' => $cardId2, 'type' => $t2],
                ],
            ]
        );
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
