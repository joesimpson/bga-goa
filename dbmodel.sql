
-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- Goa implementation : © joesimpson <1324811+joesimpson@users.noreply.github.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- we don't need to represent player money with cards
ALTER TABLE `player` ADD `money` int NOT NULL DEFAULT 0 COMMENT 'This player total count of coins on cards';
ALTER TABLE `player` ADD `ships` int NOT NULL DEFAULT 0 COMMENT 'This player total count of ships on cards';
ALTER TABLE `player` ADD `contracts` int NOT NULL DEFAULT 0 COMMENT 'This player total count of contracts on cards';
ALTER TABLE `player` ADD `compasses` int NOT NULL DEFAULT 0 COMMENT 'This player total count of compasses on cards';

ALTER TABLE `player` ADD `nbActions` int NOT NULL DEFAULT 0 COMMENT 'Current actions turn in current round/step';
ALTER TABLE `player` ADD `passed` tinyint NOT NULL DEFAULT 0 COMMENT 'Player has passed in current round/step';
ALTER TABLE `player` ADD `actionPlayed` tinyint NOT NULL DEFAULT 0 COMMENT 'Player has played an action in current actions turn';
ALTER TABLE `player` ADD `cardsPlayed` JSON COMMENT 'Infos about card played during a round/turn';


CREATE TABLE IF NOT EXISTS `cards` (
  `card_id` int NOT NULL AUTO_INCREMENT,
  `card_state` int DEFAULT 0,
  `card_location` varchar(32) NOT NULL COMMENT 'Current location of card',
  `type` int NOT NULL,
  `subtype` int  NOT NULL,
  `player_id` int NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = UTF8MB4;

CREATE TABLE IF NOT EXISTS `tiles` (
  `tile_id` int NOT NULL AUTO_INCREMENT,
  `tile_state` int DEFAULT 0,
  `tile_location` varchar(32) NOT NULL COMMENT 'Current location of tile',
  `tile_start_location` varchar(32) NOT NULL COMMENT 'Starting location of tile',
  `type` int NOT NULL,
  `subtype` int NOT NULL,
  `tile_row` int NULL,
  `tile_col` int NULL,
  `player_id` int NULL,
  PRIMARY KEY (`tile_id`)
) ENGINE = InnoDB DEFAULT CHARSET = UTF8MB4;

CREATE TABLE IF NOT EXISTS `tokens` (
  `token_id` int NOT NULL AUTO_INCREMENT,
  `token_state` int DEFAULT 0,
  `token_location` varchar(32) NOT NULL COMMENT 'Current location of token',
  `type` int NOT NULL,
  `subtype` int NOT NULL,
  `token_row` int NULL,
  `token_col` int NULL,
  `player_id` int NULL,
  PRIMARY KEY (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 ;

CREATE TABLE IF NOT EXISTS `effects` (
  `effect_id` int unsigned NOT NULL AUTO_INCREMENT,
  `effect_parent_id` int unsigned NULL COMMENT 'Link to parent effect if any',
  `effect_date` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `effect_state` int DEFAULT 0,
  `effect_location` varchar(32),
  `effect_type` int NOT NULL COMMENT 'Type of effect to resolve' ,
  `effect_qty` int COMMENT 'Amount/Power of the effect' ,
  `effect_source_tile` int COMMENT 'Tile id if the effect comes from a tile',
  `effect_source_action` int COMMENT 'Action id if the effect comes from a a standard action',
  `player_id` int NULL COMMENT 'Player affected : who need to resolve the effect' ,
  `effect_result` JSON COMMENT 'Result may be different things : a list of choices to apply the effect...',
  PRIMARY KEY (`effect_id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 ;

-- CORE TABLES from tisaac boilerplate --
CREATE TABLE IF NOT EXISTS `my_global_variables` (
  `name` varchar(50) NOT NULL,
  `value` JSON,
  PRIMARY KEY (`name`)
) ENGINE = InnoDB DEFAULT CHARSET = UTF8MB4;

CREATE TABLE IF NOT EXISTS `log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `date` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `move_id` int NOT NULL,
  `table` varchar(32) NOT NULL,
  `primary` varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  `affected` JSON,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = UTF8MB4;

