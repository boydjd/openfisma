SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

INSERT INTO `NETWORKS` (`network_id`, `network_name`, `network_nickname`, `network_desc`) VALUES 
(1, 'N/A', 'N/A', 'Use when Location is not required'),
(2, 'Eastern Data Center', 'EDC', 'Data Center located in New York.'),
(3, 'Central Data Center', 'CDC', 'Data Center located in Nevada'),
(4, 'Pacific Data Center', 'PDC', 'Data Center located in California');


INSERT INTO `ASSET_ADDRESSES` (`asset_id`, `network_id`, `address_date_created`, `address_ip`, `address_port`) VALUES 
(18, 3, '2008-01-17 16:33:10', '192.168.0.3', 22),
(17, 2, '2008-01-17 16:32:35', '192.168.0.2', 22),
(16, 1, '2008-01-17 16:31:58', '192.168.0.1', 22),
(15, 3, '2008-01-17 16:31:13', '192.168.0.3', 22),
(14, 2, '2008-01-17 16:30:27', '192.168.0.2', 22),
(13, 1, '2008-01-17 16:29:51', '192.168.0.1', 22),
(12, 3, '2008-01-17 16:29:12', '192.168.0.3', 22),
(11, 2, '2008-01-17 16:28:42', '192.168.0.2', 22),
(10, 1, '2008-01-17 16:28:05', '192.168.0.1', 22);


INSERT INTO `ASSETS` (`asset_id`, `prod_id`, `asset_name`, `asset_date_created`, `asset_source`) VALUES 
(14, 275, 'SNMS-Internet Explorer', '2008-01-17 16:30:27', 'MANUAL'),
(13, 271, 'SNMS-BSD/OS', '2008-01-17 16:29:51', 'MANUAL'),
(12, 13, 'SLS-IIS', '2008-01-17 16:29:12', 'MANUAL'),
(11, 221, 'SLS-FreeBSD', '2008-01-17 16:28:42', 'MANUAL'),
(10, 295, 'SLS-Debian Linux', '2008-01-17 16:28:05', 'MANUAL'),
(15, 6, 'SNMS-Netscape Messaging', '2008-01-17 16:31:13', 'MANUAL'),
(16, 227, 'TWMS-SunOS', '2008-01-17 16:31:58', 'MANUAL'),
(17, 267, 'TWMS-TCP/IP', '2008-01-17 16:32:35', 'MANUAL'),
(18, 16, 'TWMS-UX/4800', '2008-01-17 16:33:10', 'MANUAL');


INSERT INTO `FINDINGS` (`finding_id`, `source_id`, `asset_id`, `finding_status`, `finding_date_created`, `finding_date_discovered`, `finding_date_closed`, `finding_data`) VALUES 
(30, 10, 14, 'OPEN', '2008-01-17 08:01:17', '2008-01-17 00:00:00', NULL, 'finding a2'),
(29, 10, 12, 'OPEN', '2008-01-17 08:01:06', '2008-01-17 00:00:00', NULL, 'finding a1'),
(28, 4, 12, 'OPEN', '2008-01-17 08:01:17', '2008-01-17 00:00:00', NULL, 'finding e'),
(27, 10, 18, 'OPEN', '2008-01-17 08:01:09', '2008-01-17 00:00:00', NULL, 'finding c'),
(26, 10, 17, 'OPEN', '2008-01-17 08:01:42', '2008-01-17 00:00:00', NULL, 'finding b'),
(25, 10, 16, 'OPEN', '2008-01-17 08:01:18', '2008-01-17 00:00:00', NULL, 'finding a'),
(24, 3, 15, 'REMEDIATION', '2008-01-17 08:01:57', '2008-01-17 00:00:00', NULL, 'finding 9'),
(23, 8, 14, 'REMEDIATION', '2008-01-17 08:01:36', '2008-01-17 00:00:00', NULL, 'finding 8'),
(22, 10, 14, 'OPEN', '2008-01-17 08:01:16', '2008-01-17 00:00:00', NULL, 'finding 6'),
(21, 8, 13, 'REMEDIATION', '2008-01-17 08:01:38', '2008-01-17 00:00:00', NULL, 'finding 7'),
(20, 8, 13, 'OPEN', '2008-01-17 08:01:20', '2008-01-17 00:00:00', NULL, 'finding 5'),
(19, 1, 12, 'OPEN', '2008-01-17 08:01:37', '2008-01-17 00:00:00', NULL, 'finding 3'),
(18, 10, 12, 'OPEN', '2008-01-17 08:01:28', '2008-01-17 00:00:00', NULL, 'finding 3'),
(17, 2, 11, 'OPEN', '2008-01-17 08:01:07', '2008-01-17 00:00:00', NULL, 'finding 2:'),
(16, 4, 10, 'OPEN', '2008-01-17 08:01:32', '2008-01-17 00:00:00', NULL, 'finding 1 : '),
(31, 10, 18, 'OPEN', '2008-01-17 08:01:40', '2008-01-17 00:00:00', NULL, 'finding a3'),
(32, 10, 15, 'OPEN', '2008-01-17 08:01:34', '2008-01-17 00:00:00', NULL, 'finding e');

INSERT INTO `FINDING_VULNS` (`finding_id`, `vuln_seq`, `vuln_type`) VALUES 
(16, 20041917, 'CVE'),
(17, 20041916, 'CVE'),
(19, 20041915, 'CVE'),
(20, 20041914, 'CVE'),
(21, 20041914, 'CVE'),
(25, 20041916, 'CVE'),
(25, 20041917, 'CVE'),
(26, 20041913, 'CVE'),
(26, 20041914, 'CVE'),
(27, 20041868, 'CVE'),
(27, 20041869, 'CVE'),
(27, 20041915, 'CVE'),
(27, 20041916, 'CVE'),
(28, 20041915, 'CVE'),
(28, 20041916, 'CVE'),
(29, 20041913, 'CVE'),
(29, 20041914, 'CVE'),
(30, 20041912, 'CVE'),
(31, 20041910, 'CVE'),
(31, 20041911, 'CVE'),
(32, 20041909, 'CVE'),
(32, 20041910, 'CVE');


INSERT INTO `POAMS` (`poam_id`, `finding_id`, `legacy_poam_id`, `poam_is_repeat`, `poam_previous_audits`, `poam_type`, `poam_status`, `poam_blscr`, `poam_created_by`, `poam_modified_by`, `poam_closed_by`, `poam_date_created`, `poam_date_modified`, `poam_date_closed`, `poam_action_owner`, `poam_action_suggested`, `poam_action_planned`, `poam_action_status`, `poam_action_approved_by`, `poam_cmeasure`, `poam_cmeasure_effectiveness`, `poam_cmeasure_justification`, `poam_action_resources`, `poam_action_date_est`, `poam_action_date_actual`, `poam_threat_source`, `poam_threat_level`, `poam_threat_justification`) VALUES 
(16, 24, NULL, NULL, NULL, 'NONE', 'OPEN', NULL, 17, 17, NULL, '2008-01-17 16:56:29', '2008-01-17 08:56:49', NULL, 2, NULL, 'NULL', 'NONE', NULL, NULL, 'NONE', 'NULL', 'NULL', '0000-00-00', NULL, NULL, 'NONE', NULL),
(15, 23, NULL, NULL, NULL, 'NONE', 'OPEN', NULL, 17, 17, NULL, '2008-01-17 16:55:53', '2008-01-17 08:56:15', NULL, 2, NULL, 'NULL', 'NONE', NULL, 'NULL', 'NONE', 'NULL', NULL, '0000-00-00', NULL, 'NULL', 'NONE', 'NULL'),
(14, 21, NULL, NULL, NULL, 'NONE', 'OPEN', NULL, 17, 17, NULL, '2008-01-17 16:54:58', '2008-01-17 08:55:26', NULL, 2, NULL, 'NULL', 'NONE', NULL, 'NULL', 'NONE', 'NULL', 'NULL', '0000-00-00', NULL, 'NULL', 'NONE', 'NULL');

INSERT INTO `POAM_COMMENTS` (`comment_id`, `poam_id`, `user_id`, `comment_parent`, `comment_date`, `comment_topic`, `comment_body`, `comment_log`, `comment_type`) VALUES 
(16, 16, 17, NULL, '2008-01-17 16:56:29', 'SYSTEM: NEW REMEDIATION CREATED', 'A new remediation was created from finding 24', '', 'NONE'),
(15, 15, 17, NULL, '2008-01-17 16:55:53', 'SYSTEM: NEW REMEDIATION CREATED', 'A new remediation was created from finding 23', '', 'NONE'),
(14, 14, 17, NULL, '2008-01-17 16:54:58', 'SYSTEM: NEW REMEDIATION CREATED', 'A new remediation was created from finding 21', '', 'NONE');

INSERT INTO `PRODUCTS` (`prod_id`, `prod_nvd_defined`, `prod_meta`, `prod_vendor`, `prod_name`, `prod_version`, `prod_desc`) VALUES 
(1, 1, 'Red Hat Red Hat Linux 5.0', 'Red Hat', 'Red Hat Linux', '5.0', '0'),
(2, 1, 'Caldera OpenLinux 1.2', 'Caldera', 'OpenLinux', '1.2', '0'),
(3, 1, 'IBM AIX 4.3', 'IBM', 'AIX', '4.3', '0'),
(4, 1, 'HP HP-UX 11.0', 'HP', 'HP-UX', '11.0', '0'),
(5, 1, 'SCO UnixWare 7.0', 'SCO', 'UnixWare', '7.0', '0'),
(6, 1, 'Netscape Netscape Messaging Server 3.55', 'Netscape', 'Netscape Messaging Server', '3.55', '0'),
(7, 1, 'University of Washington IMAP 10.234', 'University of Washington', 'IMAP', '10.234', '0'),
(8, 1, 'C2Net StongHold Web Server 2.3', 'C2Net', 'StongHold Web Server', '2.3', '0'),
(9, 1, 'Open Market Secure WebServer 2.1', 'Open Market', 'Secure WebServer', '2.1', '0'),
(10, 1, 'Microsoft Site Server 3.0', 'Microsoft', 'Site Server', '3.0', '0'),
(11, 1, 'Netscape Netscape Enterprise Server 2.0', 'Netscape', 'Netscape Enterprise Server', '2.0', '0'),
(12, 1, 'Netscape Certificate Server 1.0P1', 'Netscape', 'Certificate Server', '1.0P1', '0'),
(13, 1, 'Microsoft IIS 4.0', 'Microsoft', 'IIS', '4.0', '0'),
(14, 1, 'SGI IRIX 5.1.1', 'SGI', 'IRIX', '5.1.1', '0'),
(15, 1, 'SCO Open Desktop 5.0', 'SCO', 'Open Desktop', '5.0', '0'),
(16, 1, 'NEC UX/4800 64', 'NEC', 'UX/4800', '64', '0'),
(17, 1, 'Data General DG/UX 5.4 4.11', 'Data General', 'DG/UX', '5.4 4.11', '0'),
(139, 1, 'NetBSD NetBSD 1.3.1', 'NetBSD', 'NetBSD', '1.3.1', '0'),
(157, 1, 'Sun Solaris 5.6', 'Sun', 'Solaris', '5.6', '0'),
(159, 1, 'SCO Unix 3.2v4', 'SCO', 'Unix', '3.2v4', '0'),
(165, 1, 'Microsoft Microsoft Personal Web Server 4.0', 'Microsoft', 'Microsoft Personal Web Server', '4.0', '0'),
(167, 1, 'Netscape FastTrack 3.01', 'Netscape', 'FastTrack', '3.01', '0'),
(168, 1, 'Microsoft Frontpage', 'Microsoft', 'Frontpage', '0', '0'),
(183, 1, 'SSH Communications Security SSH daemon version 1 1.2.0', 'SSH Communications Security', 'SSH daemon', 'version 1 1.2.0', '0'),
(184, 1, 'CDE CDE 1.2', 'CDE', 'CDE', '1.2', '0'),
(199, 1, 'Microsoft Windows NT 4.0 SP2', 'Microsoft', 'Windows NT', '4.0 SP2', '0'),
(202, 1, 'Microsoft Windows 95 0.0a', 'Microsoft', 'Windows 95', '0.0a', '0'),
(214, 1, 'Cisco IOS 7000', 'Cisco', 'IOS', '7000', '0'),
(216, 1, 'Microsoft WinSock 2.0', 'Microsoft', 'WinSock', '2.0', '0'),
(218, 1, 'SCO OpenServer 5.0.4', 'SCO', 'OpenServer', '5.0.4', '0'),
(220, 1, 'GNU inet 6.02', 'GNU', 'inet', '6.02', '0'),
(221, 1, 'FreeBSD FreeBSD 2.1', 'FreeBSD', 'FreeBSD', '2.1', '0'),
(224, 1, 'Siemens Reliant UNIX', 'Siemens', 'Reliant UNIX', '0', '0'),
(226, 1, 'Washington University wu-ftpd 2.4', 'Washington University', 'wu-ftpd', '2.4', '0'),
(227, 1, 'Sun SunOS 5.5.1', 'Sun', 'SunOS', '5.5.1', '0'),
(238, 1, 'NCR MP-RAS 3.0', 'NCR', 'MP-RAS', '3.0', '0'),
(240, 1, 'NightHawk CX/UX', 'NightHawk', 'CX/UX', '0', '0'),
(243, 1, 'NightHawk PowerUX', 'NightHawk', 'PowerUX', '0', '0'),
(245, 1, 'Muhammad A. Muquit wwwcount 2.3', 'Muhammad A. Muquit', 'wwwcount', '2.3', '0'),
(267, 1, 'SCO TCP/IP 1.2.1', 'SCO', 'TCP/IP', '1.2.1', '0'),
(268, 1, 'NEC EWS-UX/V 4.2MP', 'NEC', 'EWS-UX/V', '4.2MP', '0'),
(271, 1, 'BSDI BSD/OS 3.0', 'BSDI', 'BSD/OS', '3.0', '0'),
(275, 1, 'Microsoft Internet Explorer 4.0', 'Microsoft', 'Internet Explorer', '4.0', '0'),
(278, 1, 'Netscape Communicator 4.0', 'Netscape', 'Communicator', '4.0', '0'),
(279, 1, 'NeXT NeXTstep 4.1', 'NeXT', 'NeXTstep', '4.1', '0'),
(283, 1, 'SGI Freeware 2.0', 'SGI', 'Freeware', '2.0', '0'),
(285, 1, 'Larry Wall Perl 5.003', 'Larry Wall', 'Perl', '5.003', '0'),
(295, 1, 'Debian Debian Linux 1.3', 'Debian', 'Debian Linux', '1.3', '0');

INSERT INTO `SYSTEMS` (`system_id`, `system_name`, `system_nickname`, `system_desc`, `system_type`, `system_primary_office`, `system_confidentiality`, `system_integrity`, `system_availability`, `system_tier`, `system_criticality_justification`, `system_sensitivity_justification`, `system_criticality`) VALUES 
(1, 'Financial Management System', 'FMS', 'The Financial Management System is responsible for budget execution information and supports the day-to-day requisitions and obligations for agency expenditures, invoices, billing dispute resolution, reconciliation, service level agreements, and distributions of shared expenses.', 'MAJOR APPLICATION', 0, 'LOW', 'MODERATE', 'LOW', 0, '', '', 'NONE'),
(2, 'Personnel Security System', 'PSS', 'The Personnel Security System is responsible for security clearance management and supports the processes associated with ensuring employees, contractors, and others have been approved to enter Federal buildings, utilize Federal services, and access sensitive information. This includes eligibility determination, badge issuance, clearance tracking, and security verification services.', 'MAJOR APPLICATION', 0, 'MODERATE', 'MODERATE', 'LOW', 0, '', '', 'NONE'),
(3, 'Staff Recruitment System', 'SRS', 'The Staff Recruitment System supports the active marketing and hiring of personnel to fill opportunities and vacancies within the organization.', 'MAJOR APPLICATION', 0, 'LOW', 'LOW', 'LOW', 0, '', '', 'NONE'),
(4, 'Asset Management System', 'AMS', 'The Asset Management System supports the maintenance, administration, and operation of offices buildings, fleets, machinery, and other capital assets considered as possessions of the agency.', 'MAJOR APPLICATION', 0, 'LOW', 'LOW', 'LOW', 0, '', '', 'NONE'),
(5, 'Network General Support System', 'NGSS', 'The Network General Support System supports all Major Applications which require network communication. The network general support system is comprised of routers, switches, hubs, ATM, and other network telecom gear used to facilitate the communication across applications, facilities, and buildings.', 'GENERAL SUPPORT SYSTEM', 0, 'HIGH', 'HIGH', 'HIGH', 0, '', '', 'NONE'),
(6, 'Desktop General Support System', 'DGSS', 'The Desktop General Support System provides desktop machines and applications to all system staff, facilities, and buildings throughout the agency.', 'GENERAL SUPPORT SYSTEM', 0, 'LOW', 'MODERATE', 'LOW', 0, '', '', 'NONE'),
(7, 'Oracle General Support System', 'OGSS', 'The Oracle General Support System provides All Major Applications and standardized database environment to store, warehouse, access, and distribute information.', 'GENERAL SUPPORT SYSTEM', 0, 'MODERATE', 'MODERATE', 'MODERATE', 0, '', '', 'NONE'),
(8, 'Federal Acquisition System', 'FSA', '', 'GENERAL SUPPORT SYSTEM', 0, 'LOW', 'LOW', 'LOW', 0, '', '', 'NONE');

INSERT INTO `SYSTEM_ASSETS` (`system_id`, `asset_id`, `system_is_owner`) VALUES 
(3, 18, 1),
(3, 17, 1),
(3, 16, 1),
(2, 15, 1),
(2, 14, 1),
(2, 13, 1),
(1, 12, 1),
(1, 11, 1),
(1, 10, 1);

INSERT INTO `SYSTEM_GROUP_SYSTEMS` (`sysgroup_id`, `system_id`) VALUES 
(1,2),
(1,3),
(1,1),
(1,4),
(1,6),
(1,5),
(1,7),
(4,2),
(3,4),
(2,4),
(2,2),
(5,8),
(2,8);

INSERT INTO `SYSTEM_GROUPS` (`sysgroup_id`, `sysgroup_name`, `sysgroup_nickname`, `sysgroup_is_identity`) VALUES 
(1, 'Organization ACME', 'ACME', 0),
(2, 'Federal System Acquisitions', 'FSA', 0),
(3, 'Asset Management System', 'AMS', 1),
(4, 'Personnel Security System', 'PSS', 1),
(5, 'Federal System Acquisitions', 'FSA', 1);

INSERT INTO `USERS` VALUES 
(18,'REVIEWER','9d1fee901b933a42978f2eacbcddff65',NULL,NULL,NULL,'Reviewer','Demo',NULL,'Account','2007-03-13 13:03:26','2008-02-07 11:27:49',':9d1fee901b933a42978f2eacbcddff65:7b24afc8bc80e548d66c4e7ff72171c5','2008-02-07 11:28:12','0000-00-00 00:00:00',1,'555-555-5555','555-555-5555','john.doe@openfisma.org',5,'REVIEWER_r'),
(19,'IV&V','9d1fee901b933a42978f2eacbcddff65',NULL,NULL,NULL,'IV&V','Demo',NULL,'Account','2007-08-21 07:08:26','2008-02-07 11:27:49',':9d1fee901b933a42978f2eacbcddff65:7b24afc8bc80e548d66c4e7ff72171c5','2008-02-07 11:28:12','0000-00-00 00:00:00',1,'555-555-5555','555-555-5555','john.doe@openfisma.org',7,'IV&V_r'),
(20,'ISSO','9d1fee901b933a42978f2eacbcddff65',NULL,NULL,NULL,'ISSO','Demo',NULL,'Account','2007-08-28 10:08:30','2008-02-07 11:27:49',':9d1fee901b933a42978f2eacbcddff65:7b24afc8bc80e548d66c4e7ff72171c5','2008-02-07 11:28:12','0000-00-00 00:00:00',1,'555-555-5555','555-555-5555','john.doe@openfisma.org',3,'ISSO_r'),
(21,'SOP','9d1fee901b933a42978f2eacbcddff65',NULL,NULL,NULL,'System Operator','Demo',NULL,'Account','2007-09-12 16:09:44','2008-02-07 11:27:49',':9d1fee901b933a42978f2eacbcddff65:7b24afc8bc80e548d66c4e7ff72171c5','2008-02-07 11:28:12','0000-00-00 00:00:00',1,'555-555-5555','555-555-5555','john.doe@openfisma.org',6,'SOP_r'),
(22,'CA','9d1fee901b933a42978f2eacbcddff65',NULL,NULL,NULL,'Certification Agent','Demo',NULL,'Account','2008-02-07 11:02:37','2008-02-07 11:27:49',':9d1fee901b933a42978f2eacbcddff65:7b24afc8bc80e548d66c4e7ff72171c5','2008-02-07 11:30:50','0000-00-00 00:00:00',1,'555-555-5555','555-555-5555','john.doe@openfisma.org',11,'CA_r'),
(23,'ISO','9d1fee901b933a42978f2eacbcddff65',NULL,NULL,NULL,'Information System Owner','Demo',NULL,'Account','2008-02-07 11:02:22','2008-02-07 11:27:49',':9d1fee901b933a42978f2eacbcddff65:7b24afc8bc80e548d66c4e7ff72171c5','2008-02-07 11:28:12','0000-00-00 00:00:00',1,'555-555-5555','555-555-5555','john.doe@openfisma.org',13,'ISO_r');

INSERT INTO `USER_ROLES` (`user_id`,`role_id`) VALUES (17,10),(18,5),(19,7),(20,3),(21,6),(22,11),(23,13);

INSERT INTO `USER_SYSTEM_ROLES` (`user_id`, `system_id`, `role_id`) VALUES (17,3,10),(17,2,10),(17,1,10),(18,1,5),(18,2,5),(18,3,5),(18,4,5),(18,5,5),(18,6,5),(18,7,5),(19,1,7),(19,2,7),(19,3,7),(19,4,7),(19,5,7),(19,6,7),(19,7,7),(20,1,3),(20,2,3),(20,3,3),(20,4,3),(20,5,3),(20,6,3),(20,7,3),(21,7,6),(21,6,6),(21,5,6),(21,4,6),(21,3,6),(21,2,6),(21,1,6),(22,1,11),(22,2,11),(22,3,11),(22,4,11),(22,5,11),(22,6,11),(22,7,11),(23,1,13),(23,2,13),(23,3,13),(23,4,13),(23,5,13),(23,6,13),(23,7,13),(17,4,10),(17,5,10),(17,6,10),(17,7,10);

INSERT INTO `VULNERABILITIES` (`vuln_seq`, `vuln_type`, `vuln_desc_primary`, `vuln_desc_secondary`, `vuln_date_discovered`, `vuln_date_modified`, `vuln_date_published`, `vuln_severity`, `vuln_loss_availability`, `vuln_loss_confidentiality`, `vuln_loss_integrity`, `vuln_loss_security_admin`, `vuln_loss_security_user`, `vuln_loss_security_other`, `vuln_type_access`, `vuln_type_input`, `vuln_type_input_bound`, `vuln_type_input_buffer`, `vuln_type_design`, `vuln_type_exception`, `vuln_type_environment`, `vuln_type_config`, `vuln_type_race`, `vuln_type_other`, `vuln_range_local`, `vuln_range_remote`, `vuln_range_user`) VALUES 
(20041917, 'CVE', 'Format string vulnerability in test_func_func in LCDProc 0.4.1 and earlier allows remote attackers to execute arbitrary code via format string specifiers in the str variable.', '0', '0000-00-00', '2005-10-20', '2004-04-08', 70, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(20041916, 'CVE', 'Multiple buffer overflows in LCDProc 0.4.1, and possibly other 0.4.x versions up to 0.4.4, allows remote attackers to execute arbitrary code via (1) a long invalid command to parse_all_client_messages function, or (2) long argv command to test_func_func function.', '0', '0000-00-00', '2005-10-20', '2004-04-08', 70, 0, 0, 0, 0, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(20041915, 'CVE', 'Buffer overflow in the parse_all_client_messages function in LCDproc 0.4.x up to 0.4.4 allows remote attackers to execute arbitrary code via a large number of arguments.', '0', '0000-00-00', '2005-10-20', '2004-04-08', 80, 1, 0, 0, 0, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(20041914, 'CVE', 'SQL injection vulnerability in modules.php in NukeCalendar 1.1.a, as used in PHP-Nuke, allows remote attackers to execute arbitrary SQL commands via the eid parameter.', '0', '0000-00-00', '2006-09-22', '2004-12-31', 80, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(20041913, 'CVE', 'Cross-site scripting (XSS) vulnerability in modules.php in NukeCalendar 1.1.a, as used in PHP-Nuke, allows remote attackers to inject arbitrary web script or HTML via the eid parameter.', '0', '0000-00-00', '2006-09-22', '2004-12-31', 33, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(20041912, 'CVE', 'The (1) modules.php, (2) block-Calendar.php, (3) block-Calendar1.php, (4) block-Calendar_center.php scripts in NukeCalendar 1.1.a, as used in PHP-Nuke, allow remote attackers to obtain sensitive information via a URL with an invalid argument, which reveals the full path in an error message.', '0', '0000-00-00', '2006-09-22', '2004-12-31', 33, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(20041911, 'CVE', 'Cross-site scripting (XSS) vulnerability in AzDGDatingLite 2.1.1 allows remote attackers to inject arbitrary web script or HTML via the (1) l parameter (aka language variable) to index.php or (2) id parameter to view.php.', '0', '0000-00-00', '2006-08-23', '2004-12-31', 33, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(20041910, 'CVE', 'rufsi.dll in Symantec Virus Detection allows remote attackers to cause a denial of service (crash) via a long string to the GetPrivateProfileString function.  NOTE: this issue was originally reported as a buffer overflow, but that specific claim is disputed by the vendor, although a crash is acknowledged.', '0', '0000-00-00', '2005-10-20', '2004-12-31', 33, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0);
