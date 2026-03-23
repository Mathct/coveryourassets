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
const [stock] = await importDojoLibs(['ebg/stock']);

// STOCK constants
var CARD_WIDTH = 128;
var CARD_HEIGHT = 179;
var CARDS_PER_ROW = 10;

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

      // selectablemulti
      if (Array.isArray(args.selectablemulti) && args.selectablemulti.length > 0) {
        this.game.setupMultiConnections(args.selectablemulti);
      }

      // selected
      if (Array.isArray(args.selected) && args.selected.length > 0) {
        args.selected.forEach((sid) => {
          this.game.safeClass(sid, "add", "selected");
        });
      }

      // selectedmulti
      if (Array.isArray(args.selectedmulti) && args.selectedmulti.length > 0) {
        args.selectedmulti.forEach((sid) => {
          this.game.safeClass(sid, "add", "selectedmulti");
        });
      }

      // event listeners uniquement s'il y a quelque chose à connecter
      if (this.possibles.length > 0) {
        this.game.setupConnections(this.possibles);
      }

      this.game.updateCreateSetButtonState();
      this.game.updateDiscardButtonState();
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
          
          case "create_set_btn":
            this.bga.statusBar.addActionButton(
              _("Create set"),
              () =>
                this.bga.actions.performAction("actButton", {
                  arg1: key,
                  arg2: this.game.getSelectedCardIdsForAction().join(","),
                }),
              { color: "primary", id: "create_set_btn", disabled: true },
            );
            break;

          case "discard_btn":
            this.bga.statusBar.addActionButton(
              _("Discard"),
              () =>
                this.bga.actions.performAction("actButton", {
                  arg1: key,
                  arg2: this.game.getSelectedCardIdsForAction().join(","),
                }),
              { color: "primary", id: "discard_btn", disabled: true },
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
        this.game.safeClass(".selectablemulti", "remove", "selectablemulti");
        this.game.safeClass(".selected", "remove", "selected");
        this.game.safeClass(".selectedmulti", "remove", "selectedmulti");
        this.game.selectedMultiIds = [];
        this.game.updateCreateSetButtonState();
        this.game.updateDiscardButtonState();
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
        this.selectedMultiIds = [];

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
        
        this.my_hand = gamedatas.my_hand;
        this.table = gamedatas.table;

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

    setupMultiConnections(selectables) {
        selectables.forEach((elt_id) => {
        const element = document.getElementById(elt_id);
        if (!element) return;

        this.safeClass(element, "add", "selectablemulti");
        const clickHandler = (evt) => this.onSelectMulti(evt);
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

    onSelectMulti(evt) {
        dojo.stopEvent(evt);

        const el = evt.currentTarget;
        if (!el.classList.contains("selectablemulti")) {
            return;
        }

        const cardId = el.id;
        if (el.classList.contains("selectedmulti")) {
            el.classList.remove("selectedmulti");
            this.selectedMultiIds = this.selectedMultiIds.filter((id) => id !== cardId);
        } else {
            el.classList.add("selectedmulti");
            if (!this.selectedMultiIds.includes(cardId)) {
                this.selectedMultiIds.push(cardId);
            }
        }

        this.updateCreateSetButtonState();
        this.updateDiscardButtonState();
    }

    getSelectedCardIdsForAction() {
        return this.selectedMultiIds
            .map((domId) => Number(String(domId).replace("my_cards_item_", "")))
            .filter((id) => Number.isFinite(id) && id > 0);
    }

    getCardTypeByCardId(cardId) {
        if (this.my_hand && this.my_hand[cardId]) {
            return Number(this.my_hand[cardId].type);
        }
        const card = Object.values(this.my_hand ?? {}).find((c) => Number(c.id) === Number(cardId));
        return card ? Number(card.type) : NaN;
    }

    canCreateSetFromSelection() {
        const ids = this.getSelectedCardIdsForAction();
        if (ids.length !== 2) {
            return false;
        }

        const [typeA, typeB] = ids.map((id) => this.getCardTypeByCardId(id));
        const isAsset = (t) => Number.isFinite(t) && t >= 1 && t <= 10;
        const isJoker = (t) => t === 11 || t === 12;

        if (isAsset(typeA) && isAsset(typeB) && typeA === typeB) {
            return true;
        }

        if ((isAsset(typeA) && isJoker(typeB)) || (isAsset(typeB) && isJoker(typeA))) {
            return true;
        }

        return false;
    }

    updateCreateSetButtonState() {
        const button = document.getElementById("create_set_btn");
        if (!button) {
            return;
        }
        button.disabled = !this.canCreateSetFromSelection();
    }

    updateDiscardButtonState() {
        const button = document.getElementById("discard_btn");
        if (!button) {
            return;
        }
        const ids = this.getSelectedCardIdsForAction();
        button.disabled = ids.length !== 1;
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

      <div id="table_cards_container" class="cards-container">
        <div class="titre">${_("Cards played")}</div>
        <div id="table_cards" class="cards"></div>
      </div> 
                    
      <div id="hand_container" class="cards-container">
        <div class="titre" id="my_cards_title">${_("My hand")}</div>
        <div id="my_cards" class="cards"></div>
      </div>
      
      </div>
    `;

    // Injecte le board
    document.getElementById("game_play_area").insertAdjacentHTML("beforeend", gameBoardHTML);


    this.setupStocks();

    }


    // CARDS AND STOCKS AND INFOS MOMIE AND INFOS CLONE

    getStockCardType(card) {
        // Les cartes issues du Deck PHP arrivent avec { id, type, type_arg, location, location_arg }.
        // Le Stock attend un type numérique correspondant à addItemType(...).
        const type = Number(card?.type);
        return Number.isFinite(type) ? type : 0;
    }
     
    createStockForCards(element)
    {
        let stock = new ebg.stock();
        stock.create(this.bga.gameui, element, CARD_WIDTH, CARD_HEIGHT);
        stock.image_items_per_row = CARDS_PER_ROW;

        return stock;
    }

    
    setupStocks() {

    // Stock pour la main du joueur
    this.handStock = this.createStockForCards($('my_cards'));
    this.handStock.setSelectionMode(0);
    this.handStock.centerItems = false;
    this.handStock.autowidth = true;
    this.handStock.setOverlap(0, 0);
    this.handStock.item_margin = 12;
    this.handStock.use_vertical_overlap_as_offset = false;
    this.handStock.vertical_overlap = -5;
    for( var card_id = 1; card_id <= 15; card_id++) {
        this.handStock.addItemType(card_id, card_id, g_gamethemeurl + 'img/Cards.png', card_id-1);
    }


    // Stock pour la table : mêmes réglages de layout que la main (hauteur cohérente)
    this.tableStock = this.createStockForCards($('table_cards'));
    this.tableStock.setSelectionMode(0);
    this.tableStock.centerItems = false;
    this.tableStock.autowidth = true;
    this.tableStock.setOverlap(0, 0);
    this.tableStock.item_margin = 12;
    this.tableStock.use_vertical_overlap_as_offset = false;
    this.tableStock.vertical_overlap = -5;
    for( var card_id = 1; card_id <= 15; card_id++) {
        this.tableStock.addItemType(card_id, 0, g_gamethemeurl + 'img/Cards.png', card_id-1);
    }


    
    // Cards in player's hand
    Object.values(this.my_hand).forEach( card =>
    {
        const card_type = this.getStockCardType(card);
        this.handStock.addToStockWithId(card_type, card.id);
    } );
    this.handStock.updateDisplay();


    //Cards in table
    Object.values(this.table).forEach((card) => {

        
        const card_type = this.getStockCardType(card);
        this.tableStock.addToStockWithId(card_type, card.id);

        //const player = this.players[card.location_arg];
        //const card_div = document.getElementById('table_cards_item_' + card.id);
        //dojo.place('<div class="player-title" style="color: #' + player.color + '">' + player.name + '</div>', card_div);
    });
    this.tableStock.updateDisplay();

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
