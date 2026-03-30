
-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- coveryourassets implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
--   `card_type` VARCHAR(16) NOT NULL,
--   `card_type_arg` INT NOT NULL,
--   `card_location` VARCHAR(16) NOT NULL,
--   `card_location_arg` INT NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `pending` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `player_id` int(10) NULL,  
  `function` varchar(50) NULL,
  `target` varchar(50) NULL,
  `arg` varchar(50) NULL,  
  `arg2` varchar(50) NULL,
  `arg3` varchar(50) NULL,
  `arg4` varchar(50) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000 ;


CREATE TABLE IF NOT EXISTS `cards` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` int(11) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `value` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `player` ADD `first_set` int(5) NOT NULL DEFAULT 0;