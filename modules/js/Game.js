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

    /*************************************************
   *
   *  onEnteringState
   *
   ************************************************/

    onEnteringState(args, isCurrentPlayerActive) {
        
        if (isCurrentPlayerActive) {

      this.possibles = [];
      this.possiblesMulti = [];
                      
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

      // selectablemulti
      if (Array.isArray(args.selectablemulti) && args.selectablemulti.length > 0) {
        args.selectablemulti.forEach((sid) => {
          this.game.safeClass(sid, "add", "selectablemulti");
          this.possiblesMulti.push(sid);
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
      if (this.possiblesMulti.length > 0) {
        this.game.setupConnectionsMulti(this.possiblesMulti);
      }

    }

    /*************************************************
   *
   *  Titles
   *
   ************************************************/

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

    /*************************************************
   *
   *  Buttons
   *
   ************************************************/
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
                  arg2: this.game.SelectSet(),
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
                  arg2: this.game.SelectDiscard(),
                }),
              { color: "primary", id: "discard_btn", disabled: true },
            );
            break;
          
          
        }
      }
    }


    }

    /*************************************************
   *
   *  onLeavingState
   *
   ************************************************/

    onLeavingState(args, isCurrentPlayerActive) {
        this.game.safeClass(".selectable", "remove", "selectable");
        this.game.safeClass(".selectablemulti", "remove", "selectablemulti");
        this.game.safeClass(".selected", "remove", "selected");
        this.game.safeClass(".selectedmulti", "remove", "selectedmulti");
        this.game.removeConnections();
    }


     /*************************************************
   *
   *  onPlayerActivationChange
   *
   ************************************************/

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
    
    /*************************************************
   *
   *  Gamedatas
   *
   ************************************************/
    
    setup( gamedatas ) {
        console.log( "Starting game setup" );
        this.gamedatas = gamedatas;

        this.animationManager = new BgaAnimations.Manager({
        animationsActive: () => this.bga.gameui.bgaAnimationsActive(),
        });

        this.players = gamedatas.players; // A RAJOUTER POUR MOTEUR (UTILITY METHODS)
        
        this.my_hand = gamedatas.my_hand;
        this.table = gamedatas.table;
        this.discard = gamedatas.discard;
        this.sets = gamedatas.set;

        this.players_order = gamedatas.players_ordered;

        this.setupPlayersPannel();
        this.setupBoard();

        this.connections = [];
        this.connectionsMulti = [];
        

        // Setup game notifications to handle (see "setupNotifications" method below)
        this.setupNotifications();

        console.log( "Ending game setup" );
    }

     /*************************************************
   *
   *  Utility
   *
   ************************************************/

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
   *  Connections
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

    setupConnectionsMulti(selectables) {
        this.connectionsMulti = [];

        selectables.forEach((elt_id) => {
        const element = document.getElementById(elt_id);
        if (!element) return;

        const clickHandler = (evt) => this.onSelectMulti(evt);
        element.addEventListener("click", clickHandler);
        this.connectionsMulti.push({
            element,
            event: "click",
            handler: clickHandler,
        });

        });
    }

    removeConnections() {
        this.connections.forEach((connection) => {
        const { element, event, handler } = connection;
        if (element) {
            element.removeEventListener(event, handler);
        }
        });

        this.connectionsMulti.forEach((connection) => {
        const { element, event, handler } = connection;
        if (element) {
            element.removeEventListener(event, handler);
        }
        });

        this.connections = [];
        this.connectionsMulti = [];
    }

    /*************************************************
   *
   *  Selects
   *
   ************************************************/

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
        
        if (el.classList.contains("selectablemulti")) {
            el.classList.remove("selectablemulti");
            el.classList.add("selectedmulti");
        } 

        else if (el.classList.contains("selectedmulti")) {
            el.classList.remove("selectedmulti");
            el.classList.add("selectablemulti");
        }

        this.TestSetButton();
        this.TestDiscardButton();
        
    }

    TestSetButton() {
      const ids = Array.from(document.querySelectorAll('.selectedmulti')).map(el => el.id);
      const count = ids.length;
      const btn = document.getElementById('create_set_btn');

      if(btn) {
        if(count == 2)
        { 
          const id1 = ids[0].split("_");
          const id2 = ids[1].split("_");
          const type1 = this.my_hand[id1[3]].type;  
          const type2 = this.my_hand[id2[3]].type;
          if((type1 <= 10)&&(type2 <= 10)&&(type1 == type2))
          {
            btn.disabled = false;
          }

          else if ((type1 <= 10 && type2 == 11)||(type1 <= 10 && type2 == 12)||(type2 <= 10 && type1 == 11)||(type2 <= 10 && type1 == 12))
          {
            btn.disabled = false;
          }

          else{
            btn.disabled = true;
          }
          
        }

        else
        {        
          btn.disabled = true;
        }
      }

    }

    TestDiscardButton() {
      const ids = Array.from(document.querySelectorAll('.selectedmulti')).map(el => el.id);
      const count = ids.length;
      const btn = document.getElementById('discard_btn');

      if(btn) {
        if(count == 1)
        { 
          btn.disabled = false;
        }

        else
        {        
          btn.disabled = true;
        }
      }        
        
    }

    SelectSet() {

      const ids = Array.from(document.querySelectorAll('.selectedmulti')).map(el => el.id);
      const id1 = ids[0].split("_")[3];
      const id2 = ids[1].split("_")[3];

      return id1+'_'+id2;
        
        
    }

    SelectDiscard() {

      const ids = Array.from(document.querySelectorAll('.selectedmulti')).map(el => el.id);
      const id1 = ids[0].split("_")[3];
      return id1;
    }


    

    /*************************************************
   *
   *  Setup
   *
   ************************************************/

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
      const player_id = this.bga.players.getCurrentPlayer().id;

            
      const gameBoardHTML = `
        <div id="board_id">

        <div id="deck_discard_container" class="deck-discard-container">
          <div id="deck_container" class="deck-container">
            <div id="deck_cards" class="cards"></div>
          </div>

          <div id="discard_container" class="discard-container">
            <div id="discard_card" class="discard_card"></div>
          </div>
        </div>

        <div id="set_opponent_container" class="set-opponent-container"></div>

        <div id="table_cards_container" class="cards-container hidden">
          <!--<div class="titre">${_("Cards played")}</div>-->
          <div id="table_cards" class="cards"></div>
        </div> 
                      
        <div id="hand_container" class="cards-container">
          <!--<div class="titre" id="my_cards_title">${_("My hand")}</div>-->
          <div id="my_cards" class="cards"></div>
        </div>

        <div id="set_my_container" class="set-my-container">
          <div id="set_${player_id}" class="set">
            <div id="set_cards_impaire_${player_id}" class="set-cards-impaire"></div>
            <div id="set_cards_paire_${player_id}" class="set-cards-paire"></div>
          </div>
        </div>
        
        </div>

        
      `;

      // Injecte le board
      document.getElementById("game_play_area").insertAdjacentHTML("beforeend", gameBoardHTML);

      for(const player of this.players_order) {
        const set_container = document.getElementById(`set_opponent_container`);
        if(player.id != player_id) {
          set_container.insertAdjacentHTML("beforeend", `
            <div id="set_${player.id}" class="set">
            <div id="set_cards_impaire_${player.id}" class="set-cards-impaire"></div>
            <div id="set_cards_paire_${player.id}" class="set-cards-paire"></div>
          </div>
        `);
        }
      }


      this.setupStocks();
      this.setupDiscard();
      this.setupSet();

    }



    /*************************************************
   *
   *  Functions
   *
   ************************************************/


    // CARDS AND STOCKS AND INFOS MOMIE AND INFOS CLONE

    getStockCardType(card) {
        // Les cartes issues du Deck PHP arrivent avec { id, type, type_arg, location, location_arg }.
        // Le Stock attend un type numérique correspondant à addItemType(...).
        const type = Number(card?.type);
        return Number.isFinite(type) ? type : 0;
    }

    /** Sprite carte (Cards.png) sur un div : même logique que discard / setupSet. */
    applyCardFaceToElement(element, type) {
        const t = Number(type);
        if (!element || !Number.isFinite(t)) {
            return;
        }
        if (t <= 10) {
            element.style.backgroundPosition = `-${(t - 1) * 100}% 0%`;
        } else if (t === 11) {
            element.style.backgroundPosition = `0% -100%`;
        } else if (t === 12) {
            element.style.backgroundPosition = `-100% -100%`;
        }
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
        this.tableStock.addItemType(card_id, card_id, g_gamethemeurl + 'img/Cards.png', card_id-1);
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

    setupDiscard() {
      const discard_type = this.discard;
      
      const discard_div = document.getElementById('discard_card');
      if(discard_type <= 10) {
        discard_div.style.backgroundPosition = `-${(discard_type-1) * 100}% 0%`;
      }
      else if(discard_type == 11) {
        discard_div.style.backgroundPosition = `0% -100%`;
      }
      else if(discard_type == 12) {
        discard_div.style.backgroundPosition = `-100% -100%`;
      }
    }

    setupSet() {
      const sets = this.sets;
      let emplacement = '';

      if(sets != null)
        {

      Object.values(sets).forEach(set_players => {
      Object.values(set_players).forEach(set => {

        

        if (Number(set.position) % 2 === 0)
        {
          emplacement = document.getElementById('set_cards_paire_'+set.location_arg);
          emplacement.classList.add('card-paire');
        }
        else
        {
          emplacement = document.getElementById('set_cards_impaire_'+set.location_arg);
          emplacement.classList.add('card-impaire');
        }
        
        
        if(set.type <= 10) {
        emplacement.style.backgroundPosition = `-${(set.type-1) * 100}% 0%`;
        }
        else if(set.type == 11) {
          emplacement.style.backgroundPosition = `0% -100%`;
        }
        else if(set.type == 12) {
          emplacement.style.backgroundPosition = `-100% -100%`;
        }

        emplacement.style.zIndex = set.position * 100;
      
        
      })       
      });

      }

      
      // if(sets[1] != null)
      // console.warn(sets[1])
    }

    /*************************************************
   *
   *  Notifs
   *
   ************************************************/
    
    
    setupNotifications() {
        console.log( 'notifications subscriptions setup' );
        
        // automatically listen to the notifications, based on the `notif_xxx` function on this class. 
        // Uncomment the logger param to see debug information in the console about notifications.
        this.bga.notifications.setupPromiseNotifications({
            // logger: console.log
        });
    }
    
    
    async notif_cardsMovedToTable(args) {

        const container = document.getElementById('table_cards_container');
        container.classList.remove('hidden');
        
        const cards = args.cards;
        const player_id = this.bga.players.getCurrentPlayer().id;
        let position = 0;

        for (const card of cards) {
            position = Number(card.position);

            this.table[card.id] = card;
            const div_id = player_id == card.location_arg ? `my_cards_item_${card.id}` : undefined;
            const card_type = this.getStockCardType(card);
            this.tableStock.addToStockWithId(card_type, card.id, div_id);

            if (player_id == card.location_arg) {
                this.handStock.removeFromStockById(card.id);
            }
        }

        const actorId = Number(args.player_id);
        // card.position vient du SELECT *avant* UPDATE PHP : faux pour le slot. Utiliser set_position (nouvel indice de set).
        const setSlotPosition = Number(
            args.set_position !== undefined && args.set_position !== null ? args.set_position : position,
        );
        const targetSetDivId =
            setSlotPosition % 2 === 0
                ? `set_cards_paire_${actorId}`
                : `set_cards_impaire_${actorId}`;
        const targetFallbackId = `set_${actorId}`;
        const targetId =
            document.getElementById(targetSetDivId) != null ? targetSetDivId : targetFallbackId;

        await this.bga.gameui.wait(2500);

        await Promise.all(
            cards.map((card, i) => {
                const tableItemId = `table_cards_item_${card.id}`;
                const el = document.getElementById(tableItemId);
                if (!el) {
                    return Promise.resolve();
                }
                const html = el.outerHTML;
                const anim = this.bga.gameui.slideTemporaryObject(
                    html,
                    "game_play_area",
                    tableItemId,
                    targetId,
                    500,
                    i * 150,
                );
                // Éviter double affichage : seul le clone animé reste visible.
                el.style.opacity = "0";
                el.style.visibility = "hidden";
                el.style.pointerEvents = "none";
                return anim.promise;
            }),
        );

        const types = cards
            .map((c) => this.getStockCardType(c))
            .filter((t) => Number.isFinite(t) && t > 0);
        const faceType = types.length ? Math.min(...types) : 0;
        const emplacement =
            document.getElementById(targetSetDivId) || document.getElementById(targetFallbackId);
        this.applyCardFaceToElement(emplacement, faceType);
        if (emplacement) {
            emplacement.classList.add(setSlotPosition % 2 === 0 ? "card-paire" : "card-impaire");
            emplacement.style.zIndex = String(setSlotPosition * 100);
        }

        for (const card of cards) {
            this.tableStock.removeFromStockById(String(card.id));
            delete this.table[card.id];
        }
        this.tableStock.updateDisplay();

        await this.bga.gameui.wait(250);
        container.classList.add("hidden");
    }

    async notif_cardsMovedToDiscard(args) {
        const player_id = this.bga.players.getCurrentPlayer().id;
        const card_id = String(args.card.id);
        const cardDivId = 'my_cards_item_' + card_id;
        const card = document.getElementById(cardDivId);

        if (card) {
            card.style.outline = 'none';
        }

        if (player_id == args.card.location_arg) {
            // Clone animé vers la défausse ; la main se met à jour dès le départ (le clone porte le visuel).
            if (card) {
                const anim = this.bga.gameui.slideTemporaryObject(
                    card.outerHTML,
                    'game_play_area',
                    cardDivId,
                    'discard_card',
                    500,
                    0
                );
                this.handStock.removeFromStockById(card_id);
                await anim.promise;
            }
        }

        const discard_type = args.card.type;
        const discard_div = document.getElementById("discard_card");
        this.applyCardFaceToElement(discard_div, discard_type);

        await this.bga.gameui.wait(500);
    }

    async notif_drawCards(args) {
        const cards = args.cards;
        const player_id = args.player_id;
        const deck_container = document.getElementById('deck_container');
        if(player_id == this.bga.players.getCurrentPlayer().id) {
          for(const card of cards) {
            const card_type = this.getStockCardType(card);
            this.handStock.addToStockWithId(card_type, card.id, deck_container);
          }
          this.handStock.updateDisplay();
        }
    }
}
