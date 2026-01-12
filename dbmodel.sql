
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

CREATE TABLE IF NOT EXISTS `cards` (
  `card_id` int(2) NOT NULL AUTO_INCREMENT,
  `card_state` int(10) DEFAULT 0,
  `card_location` varchar(32) NOT NULL,
  `type` int(10) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `tiles` (
  `tile_id` int(10) NOT NULL AUTO_INCREMENT,
  `tile_state` int(10) DEFAULT 0,
  `tile_location` varchar(32) NOT NULL,
  `type` int(10) NOT NULL,
  `subtype` int(10) NOT NULL,
  `row` int(10) NULL,
  `col` int(10) NULL,
  `player_id` int(10) NULL,
  PRIMARY KEY (`tile_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `tokens` (
  `token_id` int(2) NOT NULL AUTO_INCREMENT,
  `token_state` int(10) DEFAULT 0,
  `token_location` varchar(32) NOT NULL,
  `type` int(10) NOT NULL,
  `subtype` int(10) NOT NULL,
  `row` int(10) NULL,
  `col` int(10) NULL,
  `player_id` int(10) NULL,
  PRIMARY KEY (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;



-- CORE TABLES from tisaac boilerplate --
CREATE TABLE IF NOT EXISTS `log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `move_id` int(10) NOT NULL,
  `table` varchar(32) NOT NULL,
  `primary` varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  `affected` JSON,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
