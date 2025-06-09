CREATE TABLE IF NOT EXISTS `proxies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `proxies_realms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(11) NOT NULL,
  `realm_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `proxy_decision_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(11) NOT NULL,
  `ssid` varchar(32) DEFAULT NULL,
  `user_name_regex` text DEFAULT NULL,
  `priority` int(5) DEFAULT 5,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `home_servers` (
  `id` int(11) NOT NULL auto_increment,
  `home_server_pool_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `secret` varchar(60) NOT NULL,
  `type` varchar(30) NOT NULL,
  `proto` varchar(5) NOT NULL,
  `ipaddr` varchar(64) NOT NULL,
  `port` int(5) NOT NULL,
  `status_check` varchar(16) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `home_server_pools` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `proxy_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `type` varchar(30) NOT NULL,
  `virtual_server` varchar(30) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `attribute_converts` (
  `id` int(11) NOT NULL auto_increment,
  `src` varchar(64) NOT NULL DEFAULT '',
  `dst` varchar(64) NOT NULL DEFAULT '',
  `nas_type` varchar(30) DEFAULT 'other',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
