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
          let split = sid.split('_');
          if(split[0] == 'set')
          {
            this.game.safeClass(sid, "add", "selectable_"+this.game.players[split[1]].color);
          }
        });
      }

      // selected
      if (Array.isArray(args.selected) && args.selected.length > 0) {
        args.selected.forEach((sid) => {
          this.game.safeClass(sid, "add", "selected");
          let split = sid.split('_');
          if(split[0] == 'set')
          {
            this.game.safeClass(sid, "add", "selected_"+this.game.players[split[1]].color);
          }
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
          _(args.titleyou)
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

          case "challenge_btn":
            this.bga.statusBar.addActionButton(
              _("Challenge"),
              () =>
                this.bga.actions.performAction("actButton", {
                  arg1: key,
                }),
              { color: "primary"},
            );
            break;

           case "cancel_btn":
            this.bga.statusBar.addActionButton(
              _("Cancel"),
              () =>
                this.bga.actions.performAction("actButton", {
                  arg1: key,
                }),
              { color: "alert"},
            );
            break;

            case "abandon_btn":
            this.bga.statusBar.addActionButton(
              _("Abandon the challenge"),
              () =>
                this.bga.actions.performAction("actButton", {
                  arg1: key,
                }),
              { color: "alert"},
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
        this.game.safeClass(".selectable_ff0000", "remove", "selectable_ff0000");
        this.game.safeClass(".selectable_008000", "remove", "selectable_008000");
        this.game.safeClass(".selectable_0000ff", "remove", "selectable_0000ff");
        this.game.safeClass(".selectable_ffa500", "remove", "selectable_ffa500");
        this.game.safeClass(".selectable_e94190", "remove", "selectable_e94190");
        this.game.safeClass(".selectable_982fff", "remove", "selectable_982fff");
        this.game.safeClass(".selected_ff0000", "remove", "selected_ff0000");
        this.game.safeClass(".selected_008000", "remove", "selected_008000");
        this.game.safeClass(".selected_0000ff", "remove", "selected_0000ff");
        this.game.safeClass(".selected_ffa500", "remove", "selected_ffa500");
        this.game.safeClass(".selected_e94190", "remove", "selected_e94190");
        this.game.safeClass(".selected_982fff", "remove", "selected_982fff");
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
        
        this.all_cards = gamedatas.all_cards;
        this.my_hand = gamedatas.my_hand;
        this.table = gamedatas.table;
        this.discard = gamedatas.discard;
        this.sets = gamedatas.set;
        this.challenge = gamedatas.challenge;
        this.attaquant = gamedatas.attaquant;
        this.defenseur = gamedatas.defenseur;
        this.challenge_attack = gamedatas.challenge_attack;
        this.challenge_defense = gamedatas.challenge_defense;

        this.players_order = gamedatas.players_ordered;

        this.setupPlayersPannel();
        this.setupBoard();

        this.setupCounters();

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

    attachToNewParentNoDestroy(mobile_in, new_parent_in, relation, place_position) 
        {
    
            const mobile = $(mobile_in);
            const new_parent = $(new_parent_in);

            var src = dojo.position(mobile);
            if (place_position)
                mobile.style.position = place_position;
            dojo.place(mobile, new_parent, relation);
            mobile.offsetTop;//force re-flow
            var tgt = dojo.position(mobile);
            var box = dojo.marginBox(mobile);
            var cbox = dojo.contentBox(mobile);
            var left = box.l + src.x - tgt.x;
            var top = box.t + src.y - tgt.y;

            mobile.style.position = "absolute";
            mobile.style.left = left + "px";
            mobile.style.top = top + "px";
            box.l += box.w - cbox.w;
            box.t += box.h - cbox.h;
            mobile.offsetTop;//force re-flow
            return box;
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
          const type1 = this.all_cards[id1[3]].type;  
          const type2 = this.all_cards[id2[3]].type;
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

        if(this.gamedatas.mode_end != 4)
        {

          this.bga.playerPanels.getElement(player.id).insertAdjacentHTML(
              "beforeend",
              `
              <div class="hand_card_pannel">
                <div class="hand_card_pannel_image"></div>
                <div id="counter_hand_${player.id}" class="counter_hand"></div>
              </div>

              <div class="cumul_score_pannel">
                <div class="billet"></div>
                <div>$</div>
                <div id="counter_cumul_score_${player.id}" class="counter_cumul_score"></div>
              </div>
                          
              `,
          );
        }

        else {

          this.bga.playerPanels.getElement(player.id).insertAdjacentHTML(
              "beforeend",
              `
              <div class="hand_card_pannel">
                <div class="hand_card_pannel_image"></div>
                <div id="counter_hand_${player.id}" class="counter_hand"></div>
              </div>
              
              `,
          );
        }

       

        });

    }

    setupBoard() {
      
      /* MODE PLAYER*/
      if(!this.bga.players.isCurrentPlayerSpectator())
      {
        const player_id = this.bga.players.getCurrentPlayer().id;
        const color = this.bga.players.getCurrentPlayer().color;
              
        const gameBoardHTML = `
        <div id="board_id">

          

          <div id="deck_discard_container" class="deck-discard-container">
            <div id="deck_container" class="deck-container">
            <div id="deck-counter" class="deck-counter"></div>
            </div>

            <div id="discard_container" class="discard-container">
              <div id="discard_card" class="discard_card"></div>
            </div>
          </div>
          
       
          
                        
          <div id="hand_container" class="cards-container" style="border: 2px solid #${color};">
            <div class="title" id="my_cards_title" style="color: #${color};">${_("My hand")}</div>
            <div id="my_cards" class="cards"></div>
          </div>

          <div id="table_cards_container" class="cards-container hidden">
            <div class="title">${_("Set created")}</div>
            <div id="table_cards" class="cards"></div>
          </div> 

          <div id="challenge_cards_container" class="challenge_cards_container hidden">
            <div class="title">- ${_("CHALLENGE")} -</div>
            <div id="title_challenge" class="title"></div>
            <div class="challenge_cards_detail">
            <div id="challenge_cards_attack" class="challenge_cards"></div>
            <div id="challenge_cards_defense" class="challenge_cards"></div>
            </div>
          </div> 

          <div id="sets_container" class="sets-container" style="border: 2px solid #${color};">
            <div id="set_opponent_container" class="set-opponent-container">
              <div id="set_${player_id}" class="set">
                <div id="counter_second_to_last_set_${player_id}" class="counter_second_to_last_set"></div>
                <div id="counter_last_set_${player_id}" class="counter_last_set"></div>
                <div id="lock_set_${player_id}" class="lock_set hidden"></div>
                <div class="title title_name_set" style="color: #${color};">${_("My sets")}</div>
                <div id="set_cards_impaire_${player_id}" class="set-cards-impaire"></div>
                <div id="set_cards_paire_${player_id}" class="set-cards-paire"></div>
              </div>
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
              <div id="counter_second_to_last_set_${player.id}" class="counter_second_to_last_set"></div>
              <div id="counter_last_set_${player.id}" class="counter_last_set"></div>
              <div id="lock_set_${player.id}" class="lock_set hidden"></div>
              <div class="title title_name_set" style="color: #${player.color};">${player.name}</div>
              <div id="set_cards_impaire_${player.id}" class="set-cards-impaire"></div>
              <div id="set_cards_paire_${player.id}" class="set-cards-paire"></div>
            </div>
          `);
          }
        }

      }

      /* MODE SPECTATOR*/
      else {

        const gameBoardHTML = `
          <div id="board_id">
 
          <div id="deck_discard_container" class="deck-discard-container">
            <div id="deck_container" class="deck-container">
            <div id="deck-counter" class="deck-counter"></div>
            </div>

            <div id="discard_container" class="discard-container">
              <div id="discard_card" class="discard_card"></div>
            </div>
          </div>
          
          <div id="table_cards_container" class="cards-container hidden">
            <div class="title">${_("Set created")}</div>
            <div id="table_cards" class="cards"></div>
          </div> 

          <div id="challenge_cards_container" class="challenge_cards_container hidden">
            <div id="title_challenge" class="title"></div>
            <div class="challenge_cards_detail">
            <div id="challenge_cards_attack" class="challenge_cards"></div>
            <div id="challenge_cards_defense" class="challenge_cards"></div>
            </div>
          </div> 
          
          <div id="sets_container" class="sets-container" style="border: 2px solid black;">
          <div id="set_opponent_container" class="set-opponent-container"></div>
          </div>
          
          </div>

        `;

        // Injecte le board
        document.getElementById("game_play_area").insertAdjacentHTML("beforeend", gameBoardHTML);

        for(const player of this.players_order) {
          const set_container = document.getElementById(`set_opponent_container`);
          
            set_container.insertAdjacentHTML("beforeend", `
              <div id="set_${player.id}" class="set">
              <div id="counter_second_to_last_set_${player.id}" class="counter_second_to_last_set"></div>
              <div id="counter_last_set_${player.id}" class="counter_last_set"></div>
              <div id="lock_set_${player.id}" class="lock_set hidden"></div>
              <div class="title title_name_set" style="color: #${player.color};">${player.name}</div>
              <div id="set_cards_impaire_${player.id}" class="set-cards-impaire"></div>
              <div id="set_cards_paire_${player.id}" class="set-cards-paire"></div>
            </div>
          `);
          
        }

      }
    


      this.setupStocks();
      this.setupDiscard();
      this.setupSet();
      this.setupLock();

      if(this.challenge == 1) {
        this.setupChallenge();
      }

    }

    setupCounters() {

      const counter = new ebg.counter();
      counter.create(
          `deck-counter`, 
          { value: this.gamedatas.deck, tableCounter: 'deck' }
      );


      Object.values(this.gamedatas.players).forEach((player) => {
        const hand_counter = new ebg.counter();
        hand_counter.create(`counter_hand_${player.id}`, {
        value: player.hand,
        playerCounter: "hand",
        playerId: player.id,
      });
      });

      Object.values(this.gamedatas.players).forEach((player) => {
        const last_set_counter = new ebg.counter();
        last_set_counter.create(`counter_last_set_${player.id}`, {
        value: player.last_set,
        playerCounter: "last_set",
        playerId: player.id,
      });
      });

      Object.values(this.gamedatas.players).forEach((player) => {
        const hand_counter = new ebg.counter();
        hand_counter.create(`counter_second_to_last_set_${player.id}`, {
        value: player.second_to_last_set,
        playerCounter: "second_to_last_set",
        playerId: player.id,
      });
      });

      if(this.gamedatas.mode_end != 4)
      {
        Object.values(this.gamedatas.players).forEach((player) => {
          const cumul_score = new ebg.counter();
          cumul_score.create(`counter_cumul_score_${player.id}`, {
          value: player.cumul_score,
          playerCounter: "cumul_score",
          playerId: player.id,
        });
        });
      }


    }

    setupLock() {

      Object.values(this.gamedatas.players).forEach((player) => {

        if(this.gamedatas.max_position_set[player.id] != null)
        {
          if((this.gamedatas.max_position_set[player.id] == 1)||(this.gamedatas.max_position_set[player.id] == 2))
          {
              const lock = document.getElementById('lock_set_'+player.id);
              lock.classList.remove('hidden');
          }
        }
      
      });

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

    if(!this.bga.players.isCurrentPlayerSpectator())
    {
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


    if(!this.bga.players.isCurrentPlayerSpectator())
    {
      // Cards in player's hand
      Object.values(this.my_hand).forEach( card =>
      {
          const card_type = this.getStockCardType(card);
          this.handStock.addToStockWithId(card_type, card.id);
      } );
      this.handStock.updateDisplay();
    }


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
      
      if(sets != null)
        {

      Object.values(sets).forEach(set_players => {
      Object.values(set_players).forEach(set => {

        this.addCardSet(set);
 
      })       
      });

      }

    }

    addCardSet(set) {

      var zIndex = Number(set.position) + 10;
      var position = '';

      if(set.type <= 10) {
        position = `-${(set.type-1) * 100}% 0%`;
      }
      else if(set.type == 11) {
        position = `0% -100%`;
      }
      else if(set.type == 12) {
        position = `-100% -100%`;
      }

      const emplacement = document.getElementById('set_'+set.location_arg).id;

      if (Number(set.position) % 2 === 0)
        {
          const card = `<div id="set_card_${set.location_arg}_${set.position}" class="card card_paire" style="z-index: ${zIndex}; background-position: ${position};"></div>`;
          dojo.place(card, emplacement);
        }
        else
        {
          const card = `<div id="set_card_${set.location_arg}_${set.position}" class="card card_impaire" style="z-index: ${zIndex}; background-position: ${position};"></div>`;
          dojo.place(card, emplacement);
        }
      
    }

    setupChallenge() {

      const title_challenge = document.getElementById('title_challenge');
      const attaquant = this.attaquant;
      const defenseur = this.defenseur;
      const color_attaquant = this.players[attaquant].color;
      const color_defenseur = this.players[defenseur].color;
      const text = `<span style="color: #${color_attaquant};">${this.players[attaquant].name}</span> ${_("vs")} <span style="color: #${color_defenseur};">${this.players[defenseur].name}</span>`;
      title_challenge.innerHTML = `<div class="title">${text}</div>`;

      const challenge_container = document.getElementById('challenge_cards_container');
      challenge_container.classList.remove('hidden');

      const challenge_attack = this.challenge_attack;
      const challenge_defense = this.challenge_defense;

      const challenge_attack_div = document.getElementById('challenge_cards_attack');
      const challenge_defense_div = document.getElementById('challenge_cards_defense');

      if(challenge_attack != null) {
        challenge_attack_div.innerHTML = '';
        const attackCards = Object.values(challenge_attack).sort(
          (a, b) => Number(a.position) - Number(b.position),
        );
        attackCards.forEach(card => {
          challenge_attack_div.innerHTML += `<div id="card_attack_${card.id}" class="challenge_card"></div>`;
          const card_type = this.getStockCardType(card);
          const card_div = document.getElementById(`card_attack_${card.id}`);
          if(card_type <= 10) {
            card_div.style.backgroundPosition = `-${(card_type-1) * 100}% 0%`;
          }
          else if(card_type == 11) {
            card_div.style.backgroundPosition = `0% -100%`;
          }
          else if(card_type == 12) {
            card_div.style.backgroundPosition = `-100% -100%`;
          }
        });

      }

      if(challenge_defense != null) {
        challenge_defense_div.innerHTML = '';
        const defenseCards = Object.values(challenge_defense).sort(
          (a, b) => Number(a.position) - Number(b.position),
        );
        defenseCards.forEach(card => {
          challenge_defense_div.innerHTML += `<div id="card_defense_${card.id}" class="challenge_card"></div>`;
          const card_type = this.getStockCardType(card);
          const card_div = document.getElementById(`card_defense_${card.id}`);
          if(card_type <= 10) {
            card_div.style.backgroundPosition = `-${(card_type-1) * 100}% 0%`;
          }
          else if(card_type == 11) {
            card_div.style.backgroundPosition = `0% -100%`;
          }
          else if(card_type == 12) {
            card_div.style.backgroundPosition = `-100% -100%`;
          }
        });
      }
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
        let player_id = 0;
        const cards = args.cards;

        if(!this.bga.players.isCurrentPlayerSpectator()) {
          player_id = this.bga.players.getCurrentPlayer().id;
        }
        
        for (const card of cards) {
            
            this.table[card.id] = card;
            const div_id = player_id == card.location_arg ? `my_cards_item_${card.id}` : undefined;
            const card_type = this.getStockCardType(card);
            this.tableStock.addToStockWithId(card_type, card.id, div_id);

            if ((player_id == card.location_arg)&&(!this.bga.players.isCurrentPlayerSpectator())) {
                this.handStock.removeFromStockById(card.id);
            }
        }

        await this.bga.gameui.wait(2000);

        for (const card of cards) {
           const enfant = document.getElementById('table_cards_item_'+card.id);
           const targetId = document.getElementById('set_'+card.location_arg).id;
          this.attachToNewParentNoDestroy( enfant.id, targetId);
          this.bga.gameui.slideToObjectAndDestroy( enfant.id, targetId, 500, 0 );

          
        }

        await this.bga.gameui.wait(500);
        this.tableStock.removeAll();
        this.addCardSet(args.card_for_set)
        container.classList.add("hidden");
       
        await this.bga.gameui.wait(1000);

    }

    async notif_cardsMovedToDiscard(args) {
        let player_id = 0;
        if(!this.bga.players.isCurrentPlayerSpectator()) {
          player_id = this.bga.players.getCurrentPlayer().id;
        }
        const card_id = String(args.card.id);
        const cardDivId = 'my_cards_item_' + card_id;
        const card = document.getElementById(cardDivId);

        if (card) {
            card.style.outline = 'none';
        }

        if ((player_id == args.card.location_arg)&&(!this.bga.players.isCurrentPlayerSpectator())) {
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

    async notif_challengeShow(args) {

      const title_challenge = document.getElementById('title_challenge');
      const attaquant = args.attaquant;
      const defenseur = args.defenseur;
      const color_attaquant = this.players[attaquant].color;
      const color_defenseur = this.players[defenseur].color;
      const text = `<span style="color: #${color_attaquant};">${this.players[attaquant].name}</span> ${_("vs")} <span style="color: #${color_defenseur};">${this.players[defenseur].name}</span>`; 
      title_challenge.innerHTML = `<div class="title">${text}</div>`;

      const challenge_container = document.getElementById('challenge_cards_container');
      challenge_container.classList.remove('hidden');
    }

    async notif_challengeHide(args) {
      const title_challenge = document.getElementById('title_challenge');
      title_challenge.innerHTML = '';
      const challenge_container = document.getElementById('challenge_cards_container');
      challenge_container.classList.add('hidden');
    }

    async notif_cardMoveChallengeAttack(args) {

      const card = args.card;
      const targetDiv = document.getElementById('challenge_cards_attack');
      let currentPlayerId;
      if(!this.bga.players.isCurrentPlayerSpectator()) {
        currentPlayerId = Number(this.bga.players.getCurrentPlayer().id);
      }

      if ((Number(card.location_arg) == currentPlayerId)&&(!this.bga.players.isCurrentPlayerSpectator())) {

        const sourceId = `my_cards_item_${card.id}`;
        const sourceEl = document.getElementById(sourceId);
        sourceEl.classList.remove('selectable');
        this.handStock.removeFromStockById(card.id, targetDiv.id);
    
      }

      setTimeout(() => 
      {
        targetDiv.innerHTML += `<div id="card_attack_${card.id}" class="challenge_card"></div>`;
        const card_type = this.getStockCardType(card);
        const card_div = document.getElementById(`card_attack_${card.id}`);
        if(card_type <= 10) {
          card_div.style.backgroundPosition = `-${(card_type-1) * 100}% 0%`;
        }
        else if(card_type == 11) {
          card_div.style.backgroundPosition = `0% -100%`;
        }
        else if(card_type == 12) {
          card_div.style.backgroundPosition = `-100% -100%`;
        }
                    
      }, "500");


      
    }

    async notif_cardMoveChallengeDefense(args) {
      const card = args.card;
      const targetDiv = document.getElementById('challenge_cards_defense');
      let currentPlayerId;
      if(!this.bga.players.isCurrentPlayerSpectator()) {
        currentPlayerId = Number(this.bga.players.getCurrentPlayer().id);
      }

      if ((Number(card.location_arg) == currentPlayerId)&&(!this.bga.players.isCurrentPlayerSpectator())) {

        const sourceId = `my_cards_item_${card.id}`;
        const sourceEl = document.getElementById(sourceId);
        sourceEl.classList.remove('selectable');
        this.handStock.removeFromStockById(card.id, targetDiv.id);
    
      }

      setTimeout(() => 
      {
        targetDiv.innerHTML += `<div id="card_defense_${card.id}" class="challenge_card"></div>`;
        const card_type = this.getStockCardType(card);
        const card_div = document.getElementById(`card_defense_${card.id}`);
        if(card_type <= 10) {
          card_div.style.backgroundPosition = `-${(card_type-1) * 100}% 0%`;
        }
        else if(card_type == 11) {
          card_div.style.backgroundPosition = `0% -100%`;
        }
        else if(card_type == 12) {
          card_div.style.backgroundPosition = `-100% -100%`;
        }
                    
      }, "500");
    }

    async notif_challengeWinByDefense(args) {

      const cards = args.cards;
      const targetId = document.getElementById('set_'+args.winner).id;
      for(const card of cards) {
        if(card.location == 'challenge_attack') {
          const enfant = document.getElementById('card_attack_'+card.id);
          this.attachToNewParentNoDestroy( enfant.id, targetId);
          this.bga.gameui.slideToObjectAndDestroy( enfant.id, targetId, 500, 0 );
        }
        else if(card.location == 'challenge_defense') {
          const enfant = document.getElementById('card_defense_'+card.id);
          this.attachToNewParentNoDestroy( enfant.id, targetId);
          this.bga.gameui.slideToObjectAndDestroy( enfant.id, targetId, 500, 0 );
        }
      }

      setTimeout(() => 
      {
        const challenge_container = document.getElementById('challenge_cards_container');
        challenge_container.classList.add('hidden');
               
      }, "500");
    }

    async notif_challengeWinByAttack(args) {

      const cards = args.cards;
      const targetId = document.getElementById('set_'+args.winner).id;
      for(const card of cards) {
        if(card.location == 'challenge_attack') {
          const enfant = document.getElementById('card_attack_'+card.id);
          this.attachToNewParentNoDestroy( enfant.id, targetId);
          this.bga.gameui.slideToObjectAndDestroy( enfant.id, targetId, 500, 0 );
        }
        else if(card.location == 'challenge_defense') {
          const enfant = document.getElementById('card_defense_'+card.id);
          this.attachToNewParentNoDestroy( enfant.id, targetId);
          this.bga.gameui.slideToObjectAndDestroy( enfant.id, targetId, 500, 0 );
        }
      }

      const set_steal = document.getElementById('set_card_'+args.player_id+'_'+args.position_def);
      this.attachToNewParentNoDestroy( set_steal.id, targetId);
      this.bga.gameui.slideToObjectAndDestroy( set_steal.id, targetId, 500, 0 );


      setTimeout(() => 
      {
        this.addCardSet(args.card_for_set);
        const challenge_container = document.getElementById('challenge_cards_container');
        challenge_container.classList.add('hidden');
               
      }, "500");
    }

    async notif_initRound(args) {

      // place la card en discard
      this.discard = args.discard;
      this.setupDiscard();
      
      //donne les cartes dans les mains des joueurs
      if(!this.bga.players.isCurrentPlayerSpectator())
      {
        if(this.bga.players.getCurrentPlayer())
        {
            let player_id = this.bga.players.getCurrentPlayer().id;
            Object.values(args.players_hand[player_id]).forEach( card =>
            {
                const card_type = this.getStockCardType(card);
                this.handStock.addToStockWithId(card_type, card.id);
            } );
            this.handStock.updateDisplay();
        }
   
      }

      // suppression des sets
      document.querySelectorAll('.card_impaire').forEach(el => el.remove());
      document.querySelectorAll('.card_paire').forEach(el => el.remove());
      
    }

    async notif_addLock(args) {

      const lock = document.getElementById('lock_set_'+args.player);
      lock.classList.remove('hidden');
      
    }

    async notif_removeLock(args) {

      const lock = document.getElementById('lock_set_'+args.player);
      lock.classList.add('hidden');
      
    }

    
}
