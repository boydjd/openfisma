--
-- Table structure for table `ASSETS`
--

DROP TABLE IF EXISTS `ASSETS`;
CREATE TABLE `ASSETS` (
  `asset_id` int(10) unsigned NOT NULL auto_increment,
  `prod_id` int(10) unsigned default NULL,
  `asset_name` varchar(32) NOT NULL default '0',
  `asset_date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `asset_source` enum('MANUAL','SCAN','INVENTORY') NOT NULL default 'MANUAL',
  PRIMARY KEY  (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `ASSET_ADDRESSES`
--

DROP TABLE IF EXISTS `ASSET_ADDRESSES`;
CREATE TABLE `ASSET_ADDRESSES` (
  `asset_id` int(10) unsigned NOT NULL default '0',
  `network_id` int(10) unsigned NOT NULL default '0',
  `address_date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `address_ip` varchar(23) default NULL,
  `address_port` int(10) unsigned default NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `AUDIT_LOG`
--

DROP TABLE IF EXISTS `AUDIT_LOG`;
CREATE TABLE `AUDIT_LOG` (
  `log_id` int(10) NOT NULL auto_increment,
  `finding_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `date` int(10) NOT NULL,
  `event` varchar(256) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `BLSCR`
--

DROP TABLE IF EXISTS `BLSCR`;
CREATE TABLE `BLSCR` (
  `blscr_number` varchar(5) NOT NULL default '',
  `blscr_class` enum('MANAGEMENT','OPERATIONAL','TECHNICAL') NOT NULL default 'MANAGEMENT',
  `blscr_subclass` text NOT NULL,
  `blscr_family` text NOT NULL,
  `blscr_control` text NOT NULL,
  `blscr_guidance` text NOT NULL,
  `blscr_low` tinyint(1) unsigned NOT NULL default '0',
  `blscr_moderate` tinyint(1) unsigned NOT NULL default '0',
  `blscr_high` tinyint(1) unsigned NOT NULL default '0',
  `blscr_enhancements` text NOT NULL,
  `blscr_supplement` text NOT NULL,
  PRIMARY KEY  (`blscr_number`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `FINDINGS`
--

DROP TABLE IF EXISTS `FINDINGS`;
CREATE TABLE `FINDINGS` (
  `finding_id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL default '0',
  `asset_id` int(10) unsigned NOT NULL default '0',
  `finding_status` enum('OPEN','CLOSED','REMEDIATION','DELETED') NOT NULL default 'OPEN',
  `finding_date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `finding_date_discovered` datetime NOT NULL default '0000-00-00 00:00:00',
  `finding_date_closed` datetime default NULL,
  `finding_data` text,
  PRIMARY KEY  (`finding_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `FINDING_SOURCES`
--

DROP TABLE IF EXISTS `FINDING_SOURCES`;
CREATE TABLE `FINDING_SOURCES` (
  `source_id` int(10) unsigned NOT NULL auto_increment,
  `source_name` varchar(64) NOT NULL,
  `source_nickname` varchar(16) NOT NULL,
  `source_desc` text,
  PRIMARY KEY  (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `FINDING_VULNS`
--

DROP TABLE IF EXISTS `FINDING_VULNS`;
CREATE TABLE `FINDING_VULNS` (
  `finding_id` int(10) unsigned NOT NULL default '0',
  `vuln_seq` int(10) unsigned NOT NULL default '0',
  `vuln_type` char(3) NOT NULL default '',
  PRIMARY KEY  (`finding_id`,`vuln_seq`,`vuln_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `FUNCTIONS`
--

DROP TABLE IF EXISTS `FUNCTIONS`;
CREATE TABLE `FUNCTIONS` (
  `function_id` int(10) unsigned NOT NULL auto_increment,
  `function_name` varchar(64) NOT NULL default '',
  `function_screen` varchar(64) NOT NULL default '',
  `function_action` varchar(64) NOT NULL default '',
  `function_desc` text NOT NULL,
  `function_open` char(1) default '1',
  PRIMARY KEY  (`function_id`),
  KEY `function_name` (`function_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `NETWORKS`
--

DROP TABLE IF EXISTS `NETWORKS`;
CREATE TABLE `NETWORKS` (
  `network_id` int(10) unsigned NOT NULL auto_increment,
  `network_name` varchar(64) NOT NULL default '',
  `network_nickname` varchar(8) NOT NULL default '',
  `network_desc` text,
  PRIMARY KEY  (`network_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `PLUGINS`
--

DROP TABLE IF EXISTS `PLUGINS`;
CREATE TABLE `PLUGINS` (
  `plugin_id` int(10) unsigned NOT NULL auto_increment,
  `plugin_name` varchar(64) NOT NULL default '',
  `plugin_nickname` varchar(12) NOT NULL default '',
  `plugin_abbreviation` char(3) default NULL,
  `plugin_desc` text,
  PRIMARY KEY  (`plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='contains information on all registered OVMS plugins';

--
-- Table structure for table `POAMS`
--

DROP TABLE IF EXISTS `POAMS`;
CREATE TABLE `POAMS` (
  `poam_id` int(10) unsigned NOT NULL auto_increment,
  `finding_id` int(10) unsigned NOT NULL default '0',
  `legacy_poam_id` varchar(32) default NULL,
  `poam_is_repeat` tinyint(1) default NULL,
  `poam_previous_audits` text,
  `poam_type` enum('NONE','CAP','FP','AR') NOT NULL default 'NONE',
  `poam_status` enum('OPEN','EN','EP','ES','CLOSED') NOT NULL default 'OPEN',
  `poam_blscr` varchar(5) default NULL,
  `poam_created_by` int(10) unsigned NOT NULL default '0',
  `poam_modified_by` int(10) unsigned NOT NULL default '0',
  `poam_closed_by` int(10) unsigned default NULL,
  `poam_date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `poam_date_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `poam_date_closed` datetime default NULL,
  `poam_action_owner` int(10) unsigned NOT NULL default '0',
  `poam_action_suggested` text,
  `poam_action_planned` text,
  `poam_action_status` enum('NONE','APPROVED','DENIED') NOT NULL default 'NONE',
  `poam_action_approved_by` int(10) unsigned default NULL,
  `poam_cmeasure` text,
  `poam_cmeasure_effectiveness` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `poam_cmeasure_justification` text,
  `poam_action_resources` text,
  `poam_action_date_est` date default NULL,
  `poam_action_date_actual` date default NULL,
  `poam_threat_source` text,
  `poam_threat_level` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `poam_threat_justification` text,
  PRIMARY KEY  (`poam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='InnoDB free: 18432 kB';

--
-- Table structure for table `POAM_COMMENTS`
--

DROP TABLE IF EXISTS `POAM_COMMENTS`;
CREATE TABLE `POAM_COMMENTS` (
  `comment_id` int(10) unsigned NOT NULL auto_increment,
  `poam_id` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `comment_parent` int(10) unsigned default NULL,
  `comment_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `comment_topic` varchar(64) NOT NULL default '',
  `comment_body` text NOT NULL,
  `comment_log` text NOT NULL,
  `comment_type` ENUM( 'EST', 'SSO', 'NONE' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'NONE',
  PRIMARY KEY  (`comment_id`,`poam_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `POAM_EVIDENCE`
--

DROP TABLE IF EXISTS `POAM_EVIDENCE`;
CREATE TABLE `POAM_EVIDENCE` (
  `ev_id` int(10) unsigned NOT NULL auto_increment,
  `poam_id` int(10) unsigned NOT NULL default '0',
  `ev_submission` varchar(128) NOT NULL default '',
  `ev_submitted_by` int(10) unsigned NOT NULL default '0',
  `ev_date_submitted` date NOT NULL default '0000-00-00',
  `ev_sso_evaluation` enum('NONE','APPROVED','DENIED','EXCLUDED') NOT NULL default 'NONE',
  `ev_date_sso_evaluation` datetime default NULL,
  `ev_fsa_evaluation` enum('NONE','APPROVED','DENIED','EXCLUDED') NOT NULL default 'NONE',
  `ev_fsa_evaluation_by` int(10) unsigned default NULL,
  `ev_date_fsa_evaluation` datetime default NULL,
  `ev_ivv_evaluation` enum('NONE','APPROVED','DENIED','EXCLUDED') NOT NULL default 'NONE',
  `ev_ivv_evaluation_by` int(10) unsigned default NULL,
  `ev_date_ivv_evaluation` varchar(45) NOT NULL default '',
  `ev_type` enum('CAP','AR','FP') default NULL,
  PRIMARY KEY  (`ev_id`,`poam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `PRODUCTS`
--

DROP TABLE IF EXISTS `PRODUCTS`;
CREATE TABLE `PRODUCTS` (
  `prod_id` int(10) unsigned NOT NULL auto_increment,
  `prod_nvd_defined` tinyint(1) NOT NULL default '0',
  `prod_meta` text,
  `prod_vendor` varchar(64) NOT NULL default '',
  `prod_name` varchar(64) NOT NULL default '',
  `prod_version` varchar(32) NOT NULL default '',
  `prod_desc` text,
  PRIMARY KEY  (`prod_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `ROLES`
--

DROP TABLE IF EXISTS `ROLES`;
CREATE TABLE `ROLES` (
  `role_id` int(10) unsigned NOT NULL auto_increment,
  `role_name` varchar(64) NOT NULL default '',
  `role_nickname` varchar(16) default NULL,
  `role_desc` text NOT NULL,
  PRIMARY KEY  (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `ROLE_FUNCTIONS`
--

DROP TABLE IF EXISTS `ROLE_FUNCTIONS`;
CREATE TABLE `ROLE_FUNCTIONS` (
  `role_func_id` int(10) unsigned NOT NULL auto_increment,
  `role_id` int(10) unsigned NOT NULL default '0',
  `function_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`role_func_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `ROLE_SYSGROUPS`
--

DROP TABLE IF EXISTS `ROLE_SYSGROUPS`;
CREATE TABLE `ROLE_SYSGROUPS` (
  `role_group_id` int(10) unsigned NOT NULL auto_increment,
  `role_id` int(10) unsigned NOT NULL default '0',
  `sysgroup_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`role_group_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `ROLE_SYSTEMS`
--

DROP TABLE IF EXISTS `ROLE_SYSTEMS`;
CREATE TABLE `ROLE_SYSTEMS` (
  `role_system_id` int(10) unsigned NOT NULL auto_increment,
  `role_id` int(10) unsigned NOT NULL default '0',
  `system_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`role_system_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `SYSTEMS`
--

DROP TABLE IF EXISTS `SYSTEMS`;
CREATE TABLE `SYSTEMS` (
  `system_id` int(10) unsigned NOT NULL auto_increment,
  `system_name` varchar(128) NOT NULL default '',
  `system_nickname` varchar(8) NOT NULL default '',
  `system_desc` text,
  `system_type` enum('GENERAL SUPPORT SYSTEM','MINOR APPLICATION','MAJOR APPLICATION') default NULL,
  `system_primary_office` int(10) unsigned NOT NULL default '0' COMMENT 'fk to system_groups',
  `system_availability` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `system_integrity` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `system_confidentiality` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `system_tier` int(10) unsigned NOT NULL default '0',
  `system_criticality_justification` text NOT NULL,
  `system_sensitivity_justification` text NOT NULL,
  `system_criticality` enum('NONE','SUPPORTIVE','IMPORTANT','CRITICAL') NOT NULL,
  PRIMARY KEY  (`system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `SYSTEM_ASSETS`
--

DROP TABLE IF EXISTS `SYSTEM_ASSETS`;
CREATE TABLE `SYSTEM_ASSETS` (
  `system_id` int(10) unsigned NOT NULL default '0',
  `asset_id` int(10) unsigned NOT NULL default '0',
  `system_is_owner` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`system_id`,`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `SYSTEM_GROUPS`
--

DROP TABLE IF EXISTS `SYSTEM_GROUPS`;
CREATE TABLE `SYSTEM_GROUPS` (
  `sysgroup_id` int(10) unsigned NOT NULL auto_increment,
  `sysgroup_name` varchar(64) NOT NULL default '',
  `sysgroup_nickname` varchar(8) NOT NULL default '',
  `sysgroup_is_identity` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`sysgroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `SYSTEM_GROUP_SYSTEMS`
--

DROP TABLE IF EXISTS `SYSTEM_GROUP_SYSTEMS`;
CREATE TABLE `SYSTEM_GROUP_SYSTEMS` (
  `sysgroup_id` int(10) unsigned NOT NULL default '0',
  `system_id` int(10) unsigned NOT NULL default '0',
  KEY `sysgroup_id` (`sysgroup_id`),
  KEY `system_id` (`system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `USERS`
--

DROP TABLE IF EXISTS `USERS`;
CREATE TABLE `USERS` (
  `user_id` int(10) unsigned NOT NULL auto_increment,
  `user_name` varchar(32) default NULL,
  `user_password` varchar(32) NOT NULL default '',
  `user_old_password1` varchar(32) default NULL,
  `user_old_password2` varchar(32) default NULL,
  `user_old_password3` varchar(32) default NULL,
  `user_title` varchar(64) default NULL,
  `user_name_last` varchar(32) NOT NULL default '',
  `user_name_middle` char(1) default NULL,
  `user_name_first` varchar(32) NOT NULL default '',
  `user_date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `user_date_password` datetime NOT NULL default '0000-00-00 00:00:00',
  `user_history_password` varchar(100) NOT NULL default '',
  `user_date_last_login` datetime NOT NULL default '0000-00-00 00:00:00',
  `user_date_deleted` datetime default NULL,
  `user_is_active` tinyint(1) NOT NULL default '0',
  `user_phone_office` varchar(12) NOT NULL,
  `user_phone_mobile` varchar(12) default NULL,
  `user_email` varchar(64) NOT NULL default '',
  `role_id` int(10) unsigned default NULL COMMENT 'seems to be redundant with the USER/ROLE/SYSTEMS table - please advise',
  PRIMARY KEY  (`user_id`),
  KEY `user_name` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `USER_SYSGROUPS`
--

DROP TABLE IF EXISTS `USER_SYSGROUPS`;
CREATE TABLE `USER_SYSGROUPS` (
  `user_group_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `sysgroup_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`user_group_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `USER_SYSTEM_ROLES`
--

DROP TABLE IF EXISTS `USER_SYSTEM_ROLES`;
CREATE TABLE `USER_SYSTEM_ROLES` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `system_id` int(10) unsigned NOT NULL default '0',
  `role_id` int(10) unsigned NOT NULL default '0',
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `VULNERABILITIES`
--

DROP TABLE IF EXISTS `VULNERABILITIES`;
CREATE TABLE `VULNERABILITIES` (
  `vuln_seq` int(10) unsigned NOT NULL auto_increment,
  `vuln_type` char(3) NOT NULL default '',
  `vuln_desc_primary` text NOT NULL,
  `vuln_desc_secondary` text,
  `vuln_date_discovered` date NOT NULL default '0000-00-00',
  `vuln_date_modified` date NOT NULL default '0000-00-00',
  `vuln_date_published` date NOT NULL default '0000-00-00',
  `vuln_severity` int(10) unsigned NOT NULL default '0',
  `vuln_loss_availability` tinyint(1) NOT NULL default '0',
  `vuln_loss_confidentiality` tinyint(1) NOT NULL default '0',
  `vuln_loss_integrity` tinyint(1) NOT NULL default '0',
  `vuln_loss_security_admin` tinyint(1) NOT NULL default '0',
  `vuln_loss_security_user` tinyint(1) NOT NULL default '0',
  `vuln_loss_security_other` tinyint(1) NOT NULL default '0',
  `vuln_type_access` tinyint(1) NOT NULL default '0',
  `vuln_type_input` tinyint(1) NOT NULL default '0',
  `vuln_type_input_bound` tinyint(1) NOT NULL default '0',
  `vuln_type_input_buffer` tinyint(1) NOT NULL default '0',
  `vuln_type_design` tinyint(1) NOT NULL default '0',
  `vuln_type_exception` tinyint(1) NOT NULL default '0',
  `vuln_type_environment` tinyint(1) NOT NULL default '0',
  `vuln_type_config` tinyint(1) NOT NULL default '0',
  `vuln_type_race` tinyint(1) NOT NULL default '0',
  `vuln_type_other` tinyint(1) NOT NULL default '0',
  `vuln_range_local` tinyint(1) NOT NULL default '0',
  `vuln_range_remote` tinyint(1) NOT NULL default '0',
  `vuln_range_user` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`vuln_seq`,`vuln_type`),
  FULLTEXT KEY `vuln_desc_primary` (`vuln_desc_primary`,`vuln_desc_secondary`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `VULN_IMPACTS`
--

DROP TABLE IF EXISTS `VULN_IMPACTS`;
CREATE TABLE `VULN_IMPACTS` (
  `vuln_seq` int(10) unsigned NOT NULL default '0',
  `vuln_type` char(3) NOT NULL default '',
  `imp_desc` text NOT NULL,
  `imp_source` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `VULN_PRODUCTS`
--

DROP TABLE IF EXISTS `VULN_PRODUCTS`;
CREATE TABLE `VULN_PRODUCTS` (
  `vuln_seq` int(10) unsigned NOT NULL default '0',
  `vuln_type` char(3) NOT NULL default '',
  `prod_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`vuln_seq`,`vuln_type`,`prod_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `VULN_REFERENCES`
--

DROP TABLE IF EXISTS `VULN_REFERENCES`;
CREATE TABLE `VULN_REFERENCES` (
  `vuln_type` char(3) NOT NULL default '',
  `vuln_seq` int(10) unsigned NOT NULL default '0',
  `ref_name` text,
  `ref_source` text NOT NULL,
  `ref_url` text NOT NULL,
  `ref_is_advisory` tinyint(1) NOT NULL default '0',
  `ref_has_tool_sig` tinyint(1) NOT NULL default '0',
  `ref_has_patch` tinyint(1) NOT NULL default '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `VULN_SOLUTIONS`
--

DROP TABLE IF EXISTS `VULN_SOLUTIONS`;
CREATE TABLE `VULN_SOLUTIONS` (
  `vuln_seq` int(10) unsigned NOT NULL default '0',
  `vuln_type` char(3) NOT NULL default '',
  `sol_desc` text NOT NULL,
  `sol_source` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


