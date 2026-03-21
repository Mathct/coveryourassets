/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * coveryourassets implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

/**
 * We create one State class per declared state on the PHP side, to handle all state specific code here.
 * onEnteringState, onLeavingState and onPlayerActivationChange are predefined names that will be called by the framework.
 * When executing code in this state, you can access the args using this.args
 */

const BgaAnimations = await importEsmLib("bga-animations", "1.x");

class NormalTurn {
    constructor(game, bga) {
        this.game = game;
        this.bga = bga;
    }

    /**
     * This method is called each time we are entering the game state. You can use this method to perform some user interface changes at this moment.
     */
    onEnteringState(args, isCurrentPlayerActive) {
        
        if (isCurrentPlayerActive) {

      this.possibles = [];
                      
      // selectable
      if (Array.isArray(args.selectable) && args.selectable.length > 0) {
        args.selectable.forEach((sid) => {
          this.game.safeClass(sid, "add", "selectable");
          this.possibles.push(sid);
        });
      }

      // selected
      if (Array.isArray(args.selected) && args.selected.length > 0) {
        args.selected.forEach((sid) => {
          this.game.safeClass(sid, "add", "selected");
        });
      }

      // event listeners uniquement s'il y a quelque chose à connecter
      if (this.possibles.length > 0) {
        this.game.setupConnections(this.possibles);
      }
    }

    // PART 2 Titles
    if (isCurrentPlayerActive && args.titleyou) {
      this.bga.statusBar.setTitle(
        this.bga.gameui.format_string_recursive(
          args.titleyou
            .replace("${you}", this.game.divYou())
            .replace(/#opponent#/g, args.opponent ?? "")
            .replace("#nb#", args.nb ?? "")
            .replace("#nb2#", args.nb2 ?? "")
            .replace("#icon#", args.icon ?? "")
            .replace("#icon2#", args.icon2 ?? ""),
          args,
        ),
      );
    } else if (args.title) {
      $("pagemaintitletext").innerHTML = this.bga.gameui.format_string_recursive(
        _(args.title)
          .replace("${actplayer}", this.game.divActPlayer())
          .replace("#nb#", args.nb ?? "")
          .replace("#nb2#", args.nb2 ?? "")
          .replace("#icon#", args.icon ?? "")
          .replace("#icon2#", args.icon2 ?? ""),
        args,
      );
    }

    // PART 3 updateActionButtons
    if (isCurrentPlayerActive && Array.isArray(args.buttons) && args.buttons.length > 0) {
      for (const key of args.buttons) {
        switch (key) {
          case "yes_btn":
            this.bga.statusBar.addActionButton(
              _("Yes"),
              () =>
                this.bga.actions.performAction("actButton", {
                  arg1: key,
                }),
              { color: "primary"},
            );
            break;

          case "no_btn":
            this.bga.statusBar.addActionButton(
              _("No"),
              () =>
                this.bga.actions.performAction("actButton", {
                  arg1: key,
                }),
              { color: "alert" },
            );
            break;
          
          
        }
      }
    }


    }

    /**
     * This method is called each time we are leaving the game state. You can use this method to perform some user interface changes at this moment.
     */
    onLeavingState(args, isCurrentPlayerActive) {
        this.game.safeClass(".selectable", "remove", "selectable");
        this.game.safeClass(".selected", "remove", "selected");
        this.game.removeConnections();
    }

    /**
     * This method is called each time the current player becomes active or inactive in a MULTIPLE_ACTIVE_PLAYER state. You can use this method to perform some user interface changes at this moment.
     * on MULTIPLE_ACTIVE_PLAYER states, you may want to call this function in onEnteringState using `this.onPlayerActivationChange(args, isCurrentPlayerActive)` at the end of onEnteringState.
     * If your state is not a MULTIPLE_ACTIVE_PLAYER one, you can delete this function.
     */
    onPlayerActivationChange(args, isCurrentPlayerActive) {
    }

}

export class Game {
    constructor(bga) {
        console.log('coveryourassets constructor');
        this.bga = bga;

        // Declare the State classes
        this.normalTurn = new NormalTurn(this, bga);
        this.bga.states.register("NormalTurn", this.normalTurn);

        // Uncomment the next line to show debug informations about state changes in the console. Remove before going to production!
        // this.bga.states.logger = console.log;
            
        // Here, you can init the global variables of your user interface
        // Example:
        // this.myGlobalValue = 0;
    }
    
    /*
        setup:
        
        This method must set up the game user interface according to current game situation specified
        in parameters.
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
        
        "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
    */
    
    setup( gamedatas ) {
        console.log( "Starting game setup" );
        this.gamedatas = gamedatas;

        this.animationManager = new BgaAnimations.Manager({
        animationsActive: () => this.bga.gameui.bgaAnimationsActive(),
        });

        this.players = gamedatas.players; // A RAJOUTER POUR MOTEUR (UTILITY METHODS)
        this.players_ordered = gamedatas.players_ordered;

        this.setupPlayersPannel();
        this.setupBoard();

        this.connections = [];
        

        // Setup game notifications to handle (see "setupNotifications" method below)
        this.setupNotifications();

        console.log( "Ending game setup" );
    }

    ///////////////////////////////////////////////////
    //// Utility methods
    
    /*
    
        Here, you can defines some utility methods that you can use everywhere in your javascript
        script. Typically, functions that are used in multiple state classes or outside a state class.
    
    */

    divYou() {
        var color = this.players[this.bga.players.getCurrentPlayerId()].color;
        var color_bg = "";
        var you = '<span style="font-weight:bold;color:#' + color + ";" + color_bg + '">' + _("You") + "</span>";
        return you;
    }

    divActPlayer() {
        var color = this.players[this.bga.players.getActivePlayerId()].color;
        var name = this.players[this.bga.players.getActivePlayerId()].name;
        var color_bg = "";
        var you = '<span style="font-weight:bold;color:#' + color + ";" + color_bg + '">' + name + "</span>";
        return you;
    }

    safeClass(target, action, className) {
        let elements = [];

        if (typeof target == "string") {
        // c’est un sélecteur ou id brut
        let selectors = [];
        if (target.startsWith("#") || target.startsWith(".")) {
            selectors = [target];
        } else {
            selectors = [`#${target}`, `.${target}`];
        }
        selectors.forEach((sel) => {
            const els = document.querySelectorAll(sel);
            if (els.length > 0) elements.push(...els);
        });
        } else if (target instanceof Element) {
        // c’est un élément DOM direct
        elements = [target];
        }

        if (elements.length == 0) {
        //console.log(`❌ No element found for "${target}"`);
        return;
        }

        elements.forEach((el) => {
        try {
            if (typeof el.classList[action] == "function") {
            el.classList[action](className);
            } else {
            console.log(`❌ Invalid action "${action}" on "${target}"`);
            }
        } catch (e) {
            console.log(`❌ Error on "${target}": ${e.message}`);
        }
        });
    }


    /*************************************************
   *
   *  setup connections from this.args.selectable
   * on each beginning of new State (Player Turn)
   *
   ************************************************/

    setupConnections(selectables) {
        this.connections = [];

        selectables.forEach((elt_id) => {
        const element = document.getElementById(elt_id);
        if (!element) return;

        const clickHandler = (evt) => this.onSelect(evt);
        element.addEventListener("click", clickHandler);
        this.connections.push({
            element,
            event: "click",
            handler: clickHandler,
        });

        });
    }

    /*************************************************
   *
   *  reset all connections
   *  on leaving a State
   *
   ************************************************/

    removeConnections() {
        this.connections.forEach((connection) => {
        const { element, event, handler } = connection;
        if (element) {
            element.removeEventListener(event, handler);
        }
        });

        this.connections = [];
    }

    onSelect(evt) {
        // Preventing default browser reaction
        dojo.stopEvent(evt);

        if (evt.currentTarget.classList.contains("selectable")) {
        this.bga.actions.performAction("actSelect", { arg1: evt.currentTarget.id });
        }
    }


    setupPlayersPannel() {

        Object.values(this.gamedatas.players).forEach((player) => {

        this.bga.playerPanels.getElement(player.id).insertAdjacentHTML(
            "beforeend",
            `
            <div>

            </div>
            `,
        );
        });

    }

    setupBoard() {  
  
    const gameBoardHTML = `
      <div id="board_id">


      </div>
    `;

    // Injecte le board
    document.getElementById("game_play_area").insertAdjacentHTML("beforeend", gameBoardHTML);

    }

  


    
    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
        setupNotifications:
        
        In this method, you associate each of your game notifications with your local method to handle it.
        
        Note: game notification names correspond to "bga->notify->all" calls in your Game.php file.
    
    */
    setupNotifications() {
        console.log( 'notifications subscriptions setup' );
        
        // automatically listen to the notifications, based on the `notif_xxx` function on this class. 
        // Uncomment the logger param to see debug information in the console about notifications.
        this.bga.notifications.setupPromiseNotifications({
            // logger: console.log
        });
    }
    
    // TODO: from this point and below, you can write your game notifications handling methods
    
    /*
    Example:
    async notif_cardPlayed( args ) {
        // Note: args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
        
        // TODO: play the card in the user interface.
    }
    */
}
