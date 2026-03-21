# Analyse d’implémentation — *Cover Your Assets* (BGA Studio)

> Document généré à partir du dépôt local.  
> Référence framework : [Studio — Board Game Arena](https://en.doc.boardgamearena.com/Studio).

---

## 1. Vue d’ensemble

Le projet est un **squelette BGA moderne** (PHP 8 strict, namespaces `Bga\Games\coveryourassets`, états en classes PHP dans `modules/php/States/`, client en `modules/js/Game.js` avec import ESM `bga-animations`).

**Ce qui est déjà en place :**

| Zone | Contenu actuel |
|------|----------------|
| **Serveur** | `Game.php` : `setupNewGame`, `getAllDatas`, file d’attente `pending`, décorateur de notifications, zombie mode basique |
| **États** | `Pending` (id 2), `NormalTurn` (id 3, joueur actif), `EndScore` (id 98) |
| **Client** | Classe `Game`, état JS `NormalTurn` : titres, boutons oui/non, sélection par `actSelect`, `safeClass`, `setupConnections` |
| **Base** | Table `pending` (+ tables standard BGA hors fichier) |
| **Matériel** | `Material.php` : `$_BUILDING_CARD` **vide** |
| **Méta** | `gameinfos.inc.php` encore **template** (« My Great Game », éditeur fictif, `bgg_id` 0) |
| **Graphismes** | Sprites / planches dans `img/` : dos de carte, cartes, wild, action, advanced wild |

**Ce qui manque ou n’est pas dans ce dépôt (normal selon Studio) :**

- Fichiers racine typiques : `states.inc.php`, `*.view.php`, `*.tpl`, `stats.json`, `material.inc.php` complet, point d’entrée `coveryourassets.game.php` — souvent gérés côté [BGA Studio](https://studio.boardgamearena.com) ou non versionnés selon ta config (voir `misc/README` : le dossier `misc/` peut être exclu du déploiement).

---

## 2. Règles du jeu (*Cover Your Assets*) — lien avec le code

Le PDF `misc/Rulebook.pdf` n’a pas été analysé ligne par ligne ici ; en revanche le jeu est un **jeu de cartes** où les joueurs :

- Constituent des **paires d’actifs** face visible devant eux ;
- Utilisent des **Wild** pour compléter des paires ;
- Jouent des **cartes Action** (vol d’actifs, protection, etc.) selon l’édition ;
- L’objectif est d’**accumuler des points** (valeur des piles / cartes) jusqu’à une fin de partie déclenchée par un seuil de score.

**Implications pour BGA :**

1. **Deck + mains + zones “tableau” par joueur** → composant PHP **Deck** + table `card` (ou équivalent) + emplacements `card_location` (main, défausse, pile joueur, etc.).
2. **Information cachée** → `getAllDatas` doit filtrer ce que voit chaque joueur (mains adverses).
3. **Chaînes de décisions** (jouer une action → réactions → choix) → ton pattern **`pending` + `callPending` + `argXxx` / `Xxx`** est adapté, mais doit être **rigoureusement aligné** sur les séquences des règles (voir section 4).

---

## 3. Architecture actuelle : file `pending`

### Idée

- Une ligne dans `pending` = **une micro-action** à résoudre (souvent avec un `function` qui mappe vers `arg{function}` / `{function}` sur `Game` ou sur l’objet `Pending` joueur).
- L’état **`Pending`** (machine à états) décide : changer de joueur actif, exécuter sans UI, ou passer à **`NormalTurn`** pour afficher sélections / boutons.

### Points positifs

- Séparation **préparation des args** (`arg…`, `execute = false`) et **exécution** (`execute = true`) ;
- Réutilisation possible d’un même flux pour plusieurs types d’actions en empilant des `pending`.

### Points de vigilance

1. **`ORDER BY id DESC LIMIT 1`** : tu assumes une **pile LIFO** stricte. Toute erreur d’ordre d’insertion ou de suppression cassera le flux.
2. **`addPending` / `addPendingFirst`** : concaténation SQL directe → risque d’**injection SQL** et de cas limites si `MIN(id)` est `NULL` au premier tour (à tester avec table vide).
3. **Deux classes nommées « Pending »** :  
   - `Bga\Games\coveryourassets\States\Pending` (état de jeu) ;  
   - `Bga\Games\coveryourassets\Pending` (contexte joueur pour `callPending`).  
   C’est gérable mais **à documenter** pour éviter les imports ambigus.
4. **`States\Pending::onEnteringState`** : accès à `game::$instance->dump` — en PHP les noms de classes sont insensibles à la casse, mais pour la lisibilité utilise **`Game::$instance`**. Vérifie aussi que `dump` est bien disponible en prod (debug uniquement ?).

---

## 4. Incohérences / bugs potentiels repérés dans le code

| Sujet | Détail |
|-------|--------|
| **Transition fin de partie** | `NormalTurn` déclare `transitions: ['end' => 99]` alors que `EndScore` a l’**id 98**. À harmoniser avec ta vraie `states.inc.php` sur Studio. |
| **`getGameProgression`** | Retourne toujours `0` → barre de progression BGA non représentative tant que non calculée. |
| **`Material.php`** | Tableau vide : aucune définition de types de cartes / valeurs pour le moteur ou les notifs. |
| **`Pending` joueur — `argPlayerTurn`** | Retourne des boutons **oui/non** en dur ; utile pour tester, mais à remplacer par les vrais choix du jeu. |
| **Client `Game.js`** | `setupBoard` injecte `#board_id` **sans garde** si `setup` est rappelé ; pas encore de `Stock` / `bga-cards` pour les cartes. |
| **Zombie** | `NormalTurn::zombie` renvoie `Pending::class` sans nettoyer la pile comme dans `Game::zombieTurn` — à aligner avec les règles et les états bloquants. |

---

## 5. Pistes d’implémentation par couches

### 5.1 Données et matériel

- Définir dans `Material.php` (ou `material.inc.php` selon ton habitude BGA) :  
  **types de cartes** (Asset, Wild, Action…), **valeurs**, **textes** pour `clienttranslate` / i18n.
- Créer la table **`card`** (Deck) + initialisation dans `setupNewGame` : pioche, mains de départ, défausse.

### 5.2 Logique serveur

- Remplacer les placeholders `PlayerTurn` par des noms de fonctions **explicites** (`argDraw`, `argPlayAction`, …) une fois le tour formalisé.
- **Notifications** : `notifyAllPlayers` / `notifyPlayer` pour chaque mouvement visible ; args enrichis par ton décorateur (`player_name` déjà partiellement géré).
- **Stats** : fichier `stats.json` + `playerStats` / `tableStats` pour suivre mains jouées, vols, etc. (optionnel mais valorisant sur BGA).

### 5.3 Interface (JS / CSS)

- **Sprites** : tes fichiers `img/Cards.jpg`, `WildCards.jpg`, etc. sont prêts pour du **CSS sprite** (comme indiqué dans ton `coveryourassets.css`).
- Composants BGA utiles selon la [doc Studio](https://en.doc.boardgamearena.com/Studio) :  
  **`Stock`** ou **`bga-cards`** pour mains et piles, **`bga-animations`** (déjà instancié) pour déplacements de cartes.
- États JS : un objet par état PHP majeur (`NormalTurn`, plus tard `React`, `Scoring`, etc.) avec `onEnteringState` / `onLeavingState`.

### 5.4 Qualité et maintenance

- Échapper / requêtes préparées pour `pending`.
- Tests sur **2, 3 et 4 joueurs** (`gameinfos` autorise déjà `[2,3,4]`).
- **Mobile** : `game_interface_width` min à 740 px dans `gameinfos` — à assouplir si tu vises le confort mobile (voir doc *Mobile Users* sur le wiki Studio).

---

## 6. Synthèse

Le squelette montre une **intention claire** : moteur **orienté file d’actions `pending`**, avec un premier tour de jeu factice (`PlayerTurn` + boutons oui/non) et une UI minimale mais **propre** (sélection, classes CSS, barre de statut).

Pour passer à un jeu jouable fidèle au **Rulebook** :

1. Modéliser cartes et zones (Deck + DB).  
2. Décomposer chaque tour / contre-joueur en **séquences `pending`** stables.  
3. Brancher **notifications + client** (`Stock` / cartes).  
4. Finaliser **scores, fin de partie** (`EndScore` → état terminal 99) et **métadonnées** (`gameinfos`, BGG id, nom éditeur).

---

*Tu peux déplacer ce fichier dans `misc/` si tu préfères le garder hors dépôt public, selon ta politique de commit.*
