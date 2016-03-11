DROP TABLE IF EXISTS ?:installed_upgrades;
CREATE TABLE ?:installed_upgrades (
  `id` int(11) unsigned NOT NULL auto_increment,
  `type` varchar(10) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `timestamp` int(11) NOT NULL DEFAULT 0,
  `description` text,
  `conflicts` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;