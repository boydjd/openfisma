CREATE TABLE `account_logs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `timestamp` datetime NOT NULL,
  `priority` tinyint(3) unsigned NOT NULL,
  `priority_name` varchar(10) NOT NULL,
  `event` enum('CREATION','MODIFICATION','TERMINATION',
               'DISABLING','LOGINFAILURE','LOGIN','LOGOUT') NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE `assets` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `prod_id` int(10) unsigned default NULL,
  `name` varchar(32) NOT NULL default '0',
  `create_ts` datetime NOT NULL default '0000-00-00 00:00:00',
  `source` enum('MANUAL','SCAN','INVENTORY') NOT NULL default 'MANUAL',
  `system_id` int(10) unsigned NOT NULL,
  `is_virgin` tinyint(1) NOT NULL default '0',
  `network_id` int(10) unsigned NOT NULL default '0',
  `address_ip` varchar(23) default NULL,
  `address_port` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `network_id` (`network_id`,`address_ip`,`address_port`)
); 

CREATE TABLE `audit_logs` (
  `id` int(10) NOT NULL auto_increment,
  `poam_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `timestamp` datetime NOT NULL,
  `event` enum('CREATION','MODIFICATION','CLOSE','','UPLOAD EVIDENCE','EVIDENCE EVALUATION') NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
); 

CREATE TABLE `blscrs` (
  `code` varchar(5) NOT NULL,
  `class` enum('MANAGEMENT','OPERATIONAL','TECHNICAL') NOT NULL default 'MANAGEMENT',
  `subclass` text NOT NULL,
  `family` text NOT NULL,
  `control` text NOT NULL,
  `guidance` text NOT NULL,
  `control_level` enum('NONE','LOW','MODERATE','HIGH') NOT NULL,
  `enhancements` text NOT NULL,
  `supplement` text NOT NULL,
  PRIMARY KEY  (`code`)
); 

CREATE TABLE `comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `poam_evaluation_id` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `topic` varchar(64) NOT NULL default '',
  `content` text NOT NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE `evaluations` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(32) NOT NULL,
  `precedence_id` int(10) NOT NULL default '0',
  `function_id` int(10) NOT NULL,
  `group` enum('EVIDENCE','ACTION') NOT NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE `ldap_config` (
  `id` int(10) NOT NULL auto_increment,
  `group` varchar(64) NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` varchar(64) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
);

CREATE TABLE `poam_evaluations` (
  `id` int(10) NOT NULL auto_increment,
  `group_id` int(10) NOT NULL,
  `eval_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `decision` enum('APPROVED','DENIED','EST_CHANGED'),
  `date` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`)
); 

CREATE TABLE `evidences` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `poam_id` int(10) unsigned NOT NULL default '0',
  `submission` varchar(128) NOT NULL default '',
  `submitted_by` int(10) unsigned NOT NULL default '0',
  `submit_ts` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`)
);


CREATE TABLE `networks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `nickname` varchar(8) NOT NULL default '',
  `desc` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name`(`name`),
  UNIQUE KEY `nickname`(`nickname`)
); 

CREATE TABLE `plugins` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `classname` varchar(12) NOT NULL default '',
  `desc` text,
  PRIMARY KEY  (`id`)
);

CREATE TABLE `poams` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `legacy_finding_id` int(10) unsigned NOT NULL default '0',
  `asset_id` int(10) unsigned NOT NULL default '0',
  `source_id` int(10) unsigned NOT NULL default '0',
  `system_id` int(10) unsigned NOT NULL default '0',
  `blscr_id` varchar(5) default NULL,
  `create_ts` datetime NOT NULL default 0,
  `discover_ts` datetime NOT NULL default 0,
  `modify_ts` datetime NOT NULL default 0,
  `close_ts` datetime default 0,
  `type` enum('NONE','CAP','FP','AR') NOT NULL default 'NONE',
  `status` enum('NEW','OPEN','EN','EP','ES','CLOSED','DELETED') NOT NULL default 'NEW',
  `is_repeat` tinyint(1) default NULL,
  `finding_data` text NOT NULL,
  `previous_audits` text,
  `created_by` int(10) unsigned NOT NULL default 0,
  `modified_by` int(10) unsigned default 0,
  `closed_by` int(10) unsigned default 0,
  `action_suggested` text,
  `action_planned` text,
  `action_status` enum('NONE','APPROVED','DENIED') NOT NULL default 'NONE',
  `action_approved_by` int(10) unsigned default NULL,
  `action_resources` text,
  `action_est_date` date default NULL,
  `action_actual_date` date default NULL,
  `cmeasure` text,
  `cmeasure_effectiveness` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `cmeasure_justification` text,
  `threat_source` text,
  `threat_level` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `threat_justification` text,
  PRIMARY KEY  (`id`)
); 


CREATE TABLE `poam_vulns` (
  `poam_id` int(10) unsigned NOT NULL default '0',
  `vuln_seq` int(10) unsigned NOT NULL default '0',
  `vuln_type` char(3) NOT NULL default '',
  PRIMARY KEY  (`poam_id`,`vuln_seq`,`vuln_type`)
); 

CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `nvd_defined` tinyint(1) NOT NULL default '0',
  `meta` text,
  `vendor` varchar(64) NOT NULL default '',
  `name` varchar(64) NOT NULL default '',
  `version` varchar(32) NOT NULL default '',
  `desc` text,
  PRIMARY KEY  (`id`)
); 

CREATE TABLE `sources` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL,
  `nickname` varchar(16) NOT NULL,
  `desc` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `nickname` (`nickname`)
); 

CREATE TABLE `systemgroup_systems` (
  `sysgroup_id` int(10) unsigned NOT NULL default '0',
  `system_id` int(10) unsigned NOT NULL default '0',
  KEY `sysgroup_id` (`sysgroup_id`),
  KEY `system_id` (`system_id`)
); 

CREATE TABLE `systems` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `nickname` varchar(8) NOT NULL default '',
  `desc` text,
  `type` enum('GENERAL SUPPORT SYSTEM','MINOR APPLICATION','MAJOR APPLICATION') default NULL,
  `primary_office` int(10) unsigned NOT NULL default '0' COMMENT 'fk to system_groups',
  `confidentiality` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `integrity` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `availability` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `tier` int(10) unsigned NOT NULL default '0',
  `criticality_justification` text NOT NULL,
  `sensitivity_justification` text NOT NULL,
  `criticality` enum('NONE','SUPPORTIVE','IMPORTANT','CRITICAL') NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `nickname` (`nickname`)
); 

CREATE TABLE `system_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `nickname` varchar(8) NOT NULL default '',
  `is_identity` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
); 


CREATE TABLE `vulnerabilities` (
  `seq` int(10) unsigned NOT NULL auto_increment,
  `type` char(3) NOT NULL default '',
  `description` text NOT NULL,
  `modify_ts` date NOT NULL default '0000-00-00',
  `publish_ts` date NOT NULL default '0000-00-00',
  `severity` int(10) unsigned NOT NULL default '0',
  `impact` text,
  `reference` text,
  `solution` text,
  PRIMARY KEY  (`seq`,`type`)
);

CREATE TABLE `vuln_products` (
  `vuln_seq` int(10) unsigned NOT NULL default '0',
  `vuln_type` char(3) NOT NULL default '',
  `prod_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`vuln_seq`,`vuln_type`,`prod_id`)
);

CREATE TABLE `functions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `screen` varchar(64) NOT NULL default '',
  `action` varchar(64) NOT NULL default '',
  `desc` text NOT NULL,
  `open` char(1) default '1',
  PRIMARY KEY  (`id`),
  KEY `function_name` (`name`)
);

CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `nickname` varchar(16) default NULL,
  `desc` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
);


CREATE TABLE `role_functions` (
  `role_id` int(10) unsigned NOT NULL default '0',
  `function_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`role_id`,`function_id`)
);

CREATE TABLE `user_roles` (
  `user_id` int(10) NOT NULL,
  `role_id` int(10) NOT NULL,
  PRIMARY KEY  (`user_id`,`role_id`)
);

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `account` varchar(32) NOT NULL,
  `password` varchar(32) NOT NULL default '',
  `ldap_dn` varchar(64) NOT NULL,
  `title` varchar(64) default NULL,
  `name_last` varchar(32) NOT NULL default '',
  `name_middle` char(1) default NULL,
  `name_first` varchar(32) NOT NULL default '',
  `created_ts` datetime NOT NULL default '0000-00-00 00:00:00',
  `password_ts` datetime NOT NULL default '0000-00-00 00:00:00',
  `history_password` varchar(100) NOT NULL default '',
  `last_login_ts` datetime NOT NULL default '0000-00-00 00:00:00',
  `termination_ts` datetime default NULL,
  `is_active` tinyint(1) NOT NULL default '0',
  `failure_count` int(2) unsigned default '0',
  `phone_office` varchar(12) NOT NULL,
  `phone_mobile` varchar(12) default NULL,
  `email` varchar(64) NOT NULL default '',
  `auto_role` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `account` (`account`)
);

CREATE TABLE `user_systems` (
  `user_id` int(10) NOT NULL,
  `system_id` int(10) NOT NULL,
  PRIMARY KEY  (`user_id`,`system_id`)
);

CREATE TABLE `configurations` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `key` varchar(64) NOT NULL,
  `value` varchar(64) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
);
