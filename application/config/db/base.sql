--
-- WARNING This file is created automatically and should not be
-- edited by hand.
--
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `account_logs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `timestamp` datetime NOT NULL,
  `priority` tinyint(3) unsigned NOT NULL,
  `priority_name` varchar(10) NOT NULL,
  `event` enum('ACCOUNT_CREATED','ACCOUNT_MODIFICATION','ACCOUNT_DELETED','ACCOUNT_LOCKOUT','DISABLING','LOGINFAILURE','LOGIN','LOGOUT','ROB_ACCEPT') NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
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
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `audit_logs` (
  `id` int(10) NOT NULL auto_increment,
  `poam_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `timestamp` datetime NOT NULL,
  `event` enum('CREATION','MODIFICATION','CLOSE','','UPLOAD EVIDENCE','EVIDENCE EVALUATION') NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
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
) ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
INSERT INTO `blscrs` VALUES ('AC-01','TECHNICAL','ACCESS CONTROL POLICY AND PROCEDURES','Access Control','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, access control policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the access control policy and associated access controls.','The access control policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The access control policy can be included as part of the general information security policy for the organization.  Access control procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-02','TECHNICAL','ACCOUNT MANAGEMENT','Access Control','The organization manages information system accounts, including establishing, activating, modifying, reviewing, disabling, and removing accounts.  The organization reviews information system accounts [Assignment: organization-defined frequency].','Account management includes the identification of account types (i.e., individual, group, and system), establishment of conditions for group membership, and assignment of associated authorizations.  The organization identifies authorized users of the information system and specifies access rights/privileges.  The organization grants access to the information system based on: (i) a valid need-to-know that is determined by assigned official duties and satisfying all personnel security criteria; and (ii) intended system usage. The organization requires proper identification for requests to establish information system accounts and approves all such requests.  The organization specifically authorizes and monitors the use of guest/anonymous accounts and removes, disables, or otherwise secures unnecessary accounts.  The organization ensures that account managers are notified when information system users are terminated or transferred and associated accounts are removed, disabled, or otherwise secured.  Account managers are also notified when users information system usage or need-to-know changes.','LOW','(1) The organization employs automated mechanisms to support the management of information system accounts. (2) The information system automatically terminates temporary and emergency accounts after [Assignment: organization-defined time period for each type of account]. (3) The information system automatically disables inactive accounts after [Assignment: organization-defined time period]. (4) The organization employs automated mechanisms to ensure that account creation, modification, disabling, and termination actions are audited and, as required, appropriate individuals are notified.','Medium: (1) (2) (3) High: (1) (2) (3) (4)');
INSERT INTO `blscrs` VALUES ('AC-03','TECHNICAL','ACCESS ENFORCEMENT','Access Control','The information system enforces assigned authorizations for controlling access to the system in accordance with applicable policy.','Access control policies (e.g., identity-based policies, role-based policies, ruled-based policies) and associated access enforcement mechanisms (e.g., access control lists, access control matrices, cryptography) are employed by organizations to control access between users (or processes acting on behalf of users) and objects (e.g., devices, files, records, processes, programs, domains) in the information system.  In addition to controlling access at the information system level, access enforcement mechanisms are employed at the application level, when necessary, to provide increased information security for the organization.  If encryption of stored information is employed as an access enforcement mechanism, the cryptography used is FIPS 140-2 compliant.','LOW','(1) The information system ensures that access to security functions (deployed in hardware, software, and firmware) and information is restricted to authorized personnel (e.g., security administrators).','Medium: (1) High: (1)');
INSERT INTO `blscrs` VALUES ('AC-04','TECHNICAL','INFORMATION FLOW ENFORCEMENT','Access Control','The information system enforces assigned authorizations for controlling the flow of information within the system and between interconnected systems in accordance with applicable policy.','Information flow control policies and enforcement mechanisms are employed by organizations to control the flow of information between designated sources and destinations (e.g., individuals, devices) within information systems and between interconnected systems based on the characteristics of the information.  Simple examples of flow control enforcement can be found in firewall and router devices that employ rule sets or establish configuration settings that restrict information system services or provide a packet filtering capability.  Flow control enforcement can also be found in information systems that use explicit labels on information, source, and destination objects as the basis for flow control decisions (e.g., to control the release of certain types of information).','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-05','TECHNICAL','SEPARATION OF DUTIES','Access Control','The information system enforces separation of duties through assigned access authorizations.','The organization establishes appropriate divisions of responsibility and separates duties as needed to eliminate conflicts of interest in the responsibilities and duties of individuals.  There is access control software on the information system that prevents users from having all of the necessary authority or information access to perform fraudulent activity without collusion.  Examples of separation of duties include: (i) mission functions and distinct information system support functions are divided among different individuals/roles; (ii) different individuals perform information system support functions (e.g., system management, systems programming, quality assurance/testing, configuration management, and network security); and (iii) security personnel who administer access control functions do not administer audit functions.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-06','TECHNICAL','LEAST PRIVILEGE','Access Control','The information system enforces the most restrictive set of rights/privileges or accesses needed by users (or processes acting on behalf of users) for the performance of specified tasks.','The organization employs the concept of least privilege for specific duties and information systems (including specific ports, protocols, and services) in accordance with risk assessments as necessary to adequately mitigate risk to organizational operations, organizational assets, and individuals.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-07','TECHNICAL','UNSUCCESSFUL LOGIN ATTEMPTS','Access Control','The information system enforces a limit of [Assignment: organization-defined number] consecutive invalid access attempts by a user during a [Assignment: organization-defined time period] time period.  The information system automatically [Selection: locks the account/node for an [Assignment: organization-defined time period], delays next login prompt according to [Assignment: organization-defined delay algorithm.]] when the maximum number of unsuccessful attempts is exceeded.','Due to the potential for denial of service, automatic lockouts initiated by the information system are usually temporary and automatically release after a predetermined time period established by the organization.','LOW','(1) The information system automatically locks the account/node until released by an administrator when the maximum number of unsuccessful attempts is exceeded.','N/A');
INSERT INTO `blscrs` VALUES ('AC-08','TECHNICAL','SYSTEM USE NOTIFICATION ','Access Control','The information system displays an approved, system use notification message before granting system access informing potential users: (i) that the user is accessing a U.S. Government information system; (ii) that system usage may be monitored, recorded, and subject to audit; (iii) that unauthorized use of the system is prohibited and subject to criminal and civil penalties; and (iv) that use of the system indicates consent to monitoring and recording.  The system use notification message provides appropriate privacy and security notices (based on associated privacy and security policies or summaries) and remains on the screen until the user takes explicit actions to log on to the information system.','Privacy and security policies are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  For publicly accessible systems: (i) the system use information is available as opposed to displaying the information before granting access; (ii) there are no references to monitoring, recording, or auditing since privacy accommodations for such systems generally prohibit those activities; and (iii) the notice given to public users of the information system includes a description of the authorized uses of the system.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-09','TECHNICAL','PREVIOUS LOGON NOTIFICATION','Access Control','The information system notifies the user, upon successful logon, of the date and time of the last logon, and the number of unsuccessful logon attempts since the last successful logon.','None.','NONE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-10','TECHNICAL','CONCURRENT SESSION CONTROL','Access Control','The information system limits the number of concurrent sessions for any user to [Assignment: organization-defined number of sessions].','None.','HIGH','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-11','TECHNICAL','SESSION LOCK','Access Control','The information system prevents further access to the system by initiating a session lock that remains in effect until the user reestablishes access using appropriate identification and authentication procedures.','Users can directly initiate session lock mechanisms.  The information system also activates session lock mechanisms automatically after a specified period of inactivity defined by the organization.  A session lock is not a substitute for logging out of the information system.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-12','TECHNICAL','SESSION TERMINATION','Access Control','The information system automatically terminates a session after [Assignment: organization-defined time period] of inactivity.','None.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-13','TECHNICAL','SUPERVISION AND REVIEW - ACCESS CONTROL','Access Control','The organization supervises and reviews the activities of users with respect to the enforcement and usage of information system access controls.','The organization reviews audit records (e.g., user activity logs) for inappropriate activities in accordance with organizational procedures.  The organization investigates any unusual information system-related activities and periodically reviews changes to access authorizations.  The organization reviews more frequently, the activities of users with significant information system roles and responsibilities.','LOW','(1) The organization employs automated mechanisms to facilitate the review of user activities.','High: (1)');
INSERT INTO `blscrs` VALUES ('AC-14','TECHNICAL','PERMITTED ACTIONS WITHOUT IDENTIFICATION OR AUTHENTICATION','Access Control','The organization identifies specific user actions that can be performed on the information system without identification or authentication.','The organization allows limited user activity without identification and authentication for public websites or other publicly available information systems. ','LOW','(1) The organization permits actions to be performed without identification and authentication only to the extent necessary to accomplish mission objectives.','Medium: (1) High: (1)');
INSERT INTO `blscrs` VALUES ('AC-15','TECHNICAL','AUTOMATED MARKING','Access Control','The information system marks output using standard naming conventions to identify any special dissemination, handling, or distribution instructions.','None.','HIGH','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-16','TECHNICAL','AUTOMATED LABELING','Access Control','The information system appropriately labels information in storage, in process, and in transmission. ','Information labeling is accomplished in accordance with special dissemination, handling, or distribution instructions, or as otherwise required to enforce information system security policy.','NONE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AC-17','TECHNICAL','REMOTE ACCESS','Access Control','The organization documents, monitors, and controls all methods of remote access (e.g., dial-up, Internet) to the information system including remote access for privileged functions.  Appropriate organization officials authorize each remote access method for the information system and authorize only the necessary users for each access method.','Remote access controls are applicable to information systems other than public web servers or systems specifically designed for public access.  The organization restricts access achieved through dial-up connections (e.g., limiting dial-up access based upon source of request) or protects against unauthorized connections or subversion of authorized connections (e.g., using virtual private network technology).  The organization permits remote access for privileged functions only for compelling operational needs.  NIST Special Publication 800-63 provides guidance on remote electronic authentication.  ','LOW','(1) The organization employs automated mechanisms to facilitate the monitoring and control of remote access methods. (2) The organization uses encryption to protect the confidentiality of remote access sessions. (3) The organization controls all remote accesses through a managed access control point.','Medium: (1) (2) (3) High: (1) (2) (3)');
INSERT INTO `blscrs` VALUES ('AC-18','TECHNICAL','WIRELESS ACCESS RESTRICTIONS','Access Control','The organization: (i) establishes usage restrictions and implementation guidance for wireless technologies; and (ii) documents, monitors, and controls wireless access to the information system.  Appropriate organizational officials authorize the use of wireless technologies.','NIST Special Publication 800-48 provides guidance on wireless network security with particular emphasis on the IEEE 802.11b and Bluetooth standards.','MODERATE','(1) The organization uses authentication and encryption to protect wireless access to the information system.','Medium: (1) High: (1)');
INSERT INTO `blscrs` VALUES ('AC-19','TECHNICAL','ACCESS CONTROL FOR PORTABLE AND MOBILE DEVICES','Access Control','The organization: (i) establishes usage restrictions and implementation guidance for portable and mobile devices; and (ii) documents, monitors, and controls device access to organizational networks.  Appropriate organizational officials authorize the use of portable and mobile devices.','Portable and mobile devices (e.g., notebook computers, workstations, personal digital assistants) are not allowed access to organizational networks without first meeting organizational security policies and procedures.  Security policies and procedures might include such activities as scanning the devices for malicious code, updating virus protection software, scanning for critical software updates and patches, conducting primary operating system (and possibly other resident software) integrity checks, and disabling unnecessary hardware (e.g., wireless).','MODERATE','(1) The organization employs removable hard drives or cryptography to protect information residing on portable and mobile devices.','High: (1)');
INSERT INTO `blscrs` VALUES ('AC-20','TECHNICAL','PERSONALLY OWNED INFORMATION SYSTEMS','Access Control','The organization restricts the use of personally owned information systems for official U.S. Government business involving the processing, storage, or transmission of federal information.','The organization establishes strict terms and conditions for the use of personally owned information systems.  The terms and conditions should address, at a minimum: (i) the types of applications that can be accessed from personally owned information systems; (ii) the maximum FIPS 199 security category of information that can processed, stored, and transmitted; (iii) how other users of the personally owned information system will be prevented from accessing federal information; (iv) the use of virtual private networking (VPN) and firewall technologies; (v) the use of and protection against the vulnerabilities of wireless technologies; (vi) the maintenance of adequate physical security controls; (vii) the use of virus and spyware protection software; and (viii) how often the security capabilities of installed software are to be updated (e.g., operating system and other software security patches, virus definitions, firewall version updates, spyware definitions).','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AT-01','OPERATIONAL','SECURITY AWARENESS AND TRAINING POLICY AND PROCEDURES','Awareness & Training','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, security awareness and training policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the security awareness and training policy and associated security awareness and training controls.','The security awareness and training policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The security awareness and training policy can be included as part of the general information security policy for the organization.  Security awareness and training procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publications 800-16 and 800-50 provide guidance on security awareness and training.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AT-02','OPERATIONAL','SECURITY AWARENESS','Awareness & Training','The organization ensures all users (including managers and senior executives) are exposed to basic information system security awareness materials before authorizing access to the system and [Assignment: organization-defined frequency, at least annually] thereafter.','The organization determines the appropriate content of security awareness training based on the specific requirements of the organization and the information systems to which personnel have authorized access.  The organization security awareness program is consistent with the requirements contained in 5 C.F.R. Part 930.301 and with the guidance in NIST Special Publication 800-50.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AT-04','OPERATIONAL','SECURITY TRAINING RECORDS','Awareness & Training','The organization documents and monitors individual information system security training activities including basic security awareness training and specific information system security training.','None.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AT-03','OPERATIONAL','SECURITY TRAINING','Awareness & Training','The organization identifies personnel with significant information system security roles and responsibilities, documents those roles and responsibilities, and provides appropriate information system security training before authorizing access to the system and [Assignment: organization-defined frequency] thereafter.','The organization determines the appropriate content of security training based on the specific requirements of the organization and the information systems to which personnel have authorized access.  In addition, the organization ensures system managers, system administrators, and other personnel having access to system-level software have adequate technical training to perform their assigned duties.  The organizations security training program is consistent with the requirements contained in 5 C.F.R. Part 930.301 and with the guidance in NIST Special Publication 800-50.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AU-01','TECHNICAL','AUDIT AND ACCOUNTABILITY POLICY AND PROCEDURES','Audit and Accountability','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, audit and accountability policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the audit and accountability policy and associated audit and accountability controls.','The audit and accountability policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The audit and accountability policy can be included as part of the general information security policy for the organization.  Audit and accountability procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AU-02','TECHNICAL','AUDITABLE EVENTS','Audit and Accountability','The information system generates audit records for the following events: [Assignment: organization-defined auditable events].','The organization specifies which information system components carry out auditing activities.  Auditing activity can affect information system performance.  Therefore, the organization decides, based upon a risk assessment, which events require auditing on a continuous basis and which events require auditing in response to specific situations.  The checklists and configuration guides at http://csrc.nist.gov/pcig/cig.html provide recommended lists of auditable events.  The organization defines auditable events that are adequate to support after-the-fact investigations of security incidents.  ','LOW','(1) The information system provides the capability to compile audit records from multiple components throughout the system into a systemwide (logical or physical), time-correlated audit trail. (2) The information system provides the capability to manage the selection of events to be audited by individual components of the system.','N/A');
INSERT INTO `blscrs` VALUES ('AU-03','TECHNICAL','CONTENT OF AUDIT RECORDS','Audit and Accountability','The information system captures sufficient information in audit records to establish what events occurred, the sources of the events, and the outcomes of the events.  ','Audit record content includes, for most audit records: (i) date and time of the event; (ii) the component of the information system (e.g., software component, hardware component) where the event occurred; (iii) type of event; (iv) subject identity; and (v) the outcome (success or failure) of the event.  ','LOW','(1) The information system provides the capability to include additional, more detailed information in the audit records for audit events identified by type, location, or subject. (2) The information system provides the capability to centrally manage the content of audit records generated by individual components throughout the system.','Medium: (1) High: (1) (2)');
INSERT INTO `blscrs` VALUES ('AU-04','TECHNICAL','AUDIT STORAGE CAPACITY','Audit and Accountability','The organization allocates sufficient audit record storage capacity and configures auditing to prevent such capacity being exceeded.','None.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AU-05','TECHNICAL','AUDIT PROCESSING','Audit and Accountability','In the event of an audit failure or audit storage capacity being reached, the information system alerts appropriate organizational officials and takes the following additional actions: [Assignment: organization-defined actions to be taken (e.g., shutdown information system, overwrite oldest audit records, stop generating audit records)].    ',' None.','LOW','(1) The information system provides a warning when allocated audit record storage volume reaches [Assignment: organization-defined percentage of maximum audit record storage capacity].','High: (1)');
INSERT INTO `blscrs` VALUES ('AU-06','TECHNICAL','AUDIT MONITORING, ANALYSIS, AND REPORTING','Audit and Accountability','The organization regularly reviews/analyzes audit records for indications of inappropriate or unusual activity, investigates suspicious activity or suspected violations, reports findings to appropriate officials, and takes necessary actions.','None.','MODERATE','(1) The organization employs automated mechanisms to integrate audit monitoring, analysis, and reporting into an overall process for investigation and response to suspicious activities. (2) The organization employs automated mechanisms to immediately alert security personnel of inappropriate or unusual activities with security implications.','High: (1)');
INSERT INTO `blscrs` VALUES ('AU-07','TECHNICAL','AUDIT REDUCTION AND REPORT GENERATION','Audit and Accountability','The information system provides an audit reduction and report generation capability.','Audit reduction, review, and reporting tools support after-the-fact investigations of security incidents without altering original audit records.','MODERATE','(1) The information system provides the capability to automatically process audit records for events of interest based upon selectable, event criteria.','High: (1)');
INSERT INTO `blscrs` VALUES ('AU-08','TECHNICAL','TIME STAMPS','Audit and Accountability','The information system provides time stamps for use in audit record generation.','Time stamps of audit records are generated using internal system clocks that are synchronized system wide.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AU-09','TECHNICAL','PROTECTION OF AUDIT INFORMATION','Audit and Accountability','The information system protects audit information and audit tools from unauthorized access, modification, and deletion.','None.','LOW','(1) The information system produces audit information on hardware-enforced, write-once media.','N/A');
INSERT INTO `blscrs` VALUES ('AU-10','TECHNICAL','NON-REPUDIATION','Audit and Accountability','The information system provides the capability to determine whether a given individual took a particular action (e.g., created information, sent a message, approved information [e.g., to indicate concurrence or sign a contract] or received a message).',' Non-repudiation protects against later false claims by an individual of not having taken a specific action.  Non-repudiation protects individuals against later claims by an author of not having authored a particular document, a sender of not having transmitted a message, a receiver of not having received a message, or a signatory of having signed a document.  Non-repudiation services can be used to determine if information originated from an individual, or if an individual took specific actions (e.g., sending an email, signing a contract, approving a procurement request) or received specific information.  Non-repudiation services are obtained by employing various techniques or mechanisms (e.g., digital signatures, digital message receipts, time stamps).','NONE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('AU-11','TECHNICAL','AUDIT RETENTION','Audit and Accountability','The organization retains audit logs for [Assignment: organization-defined time period] to provide support for after-the-fact investigations of security incidents and to meet regulatory and organizational information retention requirements.','NIST Special Publication 800-61 provides guidance on computer security incident handling and audit log retention.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CA-01','MANAGEMENT','CERTIFICATION, ACCREDITATION, AND SECURITY ASSESSMENT POLICIES AND PROCEDURES','Certification, Accreditation, and Security Assessments','The organization develops, disseminates, and periodically reviews/updates: (i) formal, documented, security assessment and certification and accreditation policies that address purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the security assessment and certification and accreditation policies and associated assessment, certification, and accreditation controls.','The security assessment and certification and accreditation policies and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The security assessment and certification and accreditation policies can be included as part of the general information security policy for the organization.  Security assessment and certification and accreditation procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-53A provides guidance on security control assessments.  NIST Special Publication 800-37 provides guidance on processing security certification and accreditation.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CA-02','MANAGEMENT','SECURITY ASSESSMENTS','Certification, Accreditation, and Security Assessments','The organization conducts an assessment of the security controls in the information system [Assignment: organization-defined frequency, at least annually] to determine the extent to which the controls are implemented correctly, operating as intended, and producing the desired outcome with respect to meeting the security requirements for the system.','This control is intended to support the FISMA requirement that the management, operational, and technical controls in each information system contained in the inventory of major information systems be tested with a frequency depending on risk, but no less than annually.  NIST Special Publications 800-53A and 800-26 provide guidance on security control assessments.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CA-03','MANAGEMENT','INFORMATION SYSTEM CONNECTIONS','Certification, Accreditation, and Security Assessments','The organization authorizes all connections from the information system to other information systems outside of the accreditation boundary and monitors/controls the system interconnections on an ongoing basis.  Appropriate organizational officials approve information system interconnection agreements.','Since FIPS 199 security categorizations apply to individual information systems, the organization should carefully consider the risks that may be introduced when systems are connected to other information systems with different security requirements and security controls, both within the organization and external to the organization.  Risk considerations should also include information systems sharing the same networks.  NIST Special Publication 800-47 provides guidance on interconnecting information systems.  ','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CA-04','MANAGEMENT','SECURITY CERTIFICATION','Certification, Accreditation, and Security Assessments','The organization conducts an assessment of the security controls in the information system to determine the extent to which the controls are implemented correctly, operating as intended, and producing the desired outcome with respect to meeting the security requirements for the system.','A security certification is conducted by the organization in support of the OMB Circular A-130, Appendix III requirement for accrediting the information system.  The security certification is integrated into and spans the System Development Life Cycle (SDLC).  NIST Special Publication 800-53A provides guidance on the assessment of security controls.  NIST Special Publication 800-37 provides guidance on security certification and accreditation.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CA-05','MANAGEMENT','PLAN OF ACTION AND MILESTONES','Certification, Accreditation, and Security Assessments','The organization develops and updates [Assignment: organization-defined frequency], a plan of action and milestones for the information system that documents the organizations planned, implemented, and evaluated remedial actions to correct any deficiencies noted during the assessment of the security controls and to reduce or eliminate known vulnerabilities in the system.','The plan of action and milestones updates are based on the findings from security control assessments, security impact analyses, and continuous monitoring activities.  The plan of action and milestones is a key document in the security accreditation package developed for the authorizing official.  NIST Special Publication 800-37 provides guidance on the security certification and accreditation of information systems.  NIST Special Publication 800-30 provides guidance on risk mitigation.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CA-06','MANAGEMENT','SECURITY ACCREDITATION','Certification, Accreditation, and Security Assessments','The organization authorizes (i.e., accredits) the information system for processing before operations and updates the authorization [Assignment: organization-defined frequency].  A senior organizational official signs and approves the security accreditation.','OMB Circular A-130, Appendix III, establishes policy for security accreditations of federal information systems.  The organization assesses the security controls employed within the information system before and in support of the security accreditation.  Security assessments conducted in support of security accreditations are called security certifications.  NIST Special Publication 800-37 provides guidance on the security certification and accreditation of information systems.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CA-07','MANAGEMENT','CONTINUOUS MONITORING','Certification, Accreditation, and Security Assessments','The organization monitors the security controls in the information system on an ongoing basis.','Continuous monitoring activities include configuration management and control of information system components, security impact analyses of changes to the system, ongoing assessment of security controls, and status reporting.  The organization establishes the selection criteria for control monitoring and subsequently selects a subset of the security controls employed within the information system for purposes of continuous monitoring.  NIST Special Publication 800-37 provides guidance on the continuous monitoring process.  NIST Special Publication 800-53A provides guidance on the assessment of security controls.  ','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CM-01','OPERATIONAL','CONFIGURATION MANAGEMENT POLICY AND PROCEDURES','Configuration Management','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, configuration management policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the configuration management policy and associated configuration management controls.','The configuration management policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The configuration management policy can be included as part of the general information security policy for the organization.  Configuration management procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CM-02','OPERATIONAL','BASELINE CONFIGURATION','Configuration Management','The organization develops, documents, and maintains a current, baseline configuration of the information system and an inventory of the systems constituent components.','The configuration of the information system is consistent with the Federal Enterprise Architecture and the organizations information system architecture.  The inventory of information system components includes manufacturer, type, serial number, version number, and location (i.e., physical location and logical position within the information system architecture).','LOW','(1) The organization updates the baseline configuration as an integral part of information system component installations. (2) The organization employs automated mechanisms to maintain an up-to-date, complete, accurate, and readily available baseline configuration.','Medium: (1) High: (1) (2)');
INSERT INTO `blscrs` VALUES ('CM-03','OPERATIONAL','CONFIGURATION CHANGE CONTROL','Configuration Management','The organization documents and controls changes to the information system.  Appropriate organizational officials approve information system changes in accordance with organizational policies and procedures.','Configuration change control involves the systematic proposal, justification, test/evaluation, review, and disposition of proposed changes.  The organization includes emergency changes in the configuration change control process.','MODERATE','(1) The organization employs automated mechanisms to: (i) document proposed changes to the information system; (ii) notify appropriate approval authorities; (iii) highlight approvals that have not been received in a timely manner; (iv) inhibit change until necessary approvals are received; and (v) document completed changes to the information system.','High: (1)');
INSERT INTO `blscrs` VALUES ('CM-04','OPERATIONAL','MONITORING CONFIGURATION CHANGES','Configuration Management','The organization monitors changes to the information system and conducts security impact analyses to determine the effects of the changes.','The organization documents the installation of information system components.  After the information system is changed, the organizations checks the security features to ensure the features are still functioning properly.  The organization audits activities associated with configuration changes to the information system.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CM-05','OPERATIONAL','ACCESS RESTRICTIONS FOR CHANGE','Configuration Management','The organization enforces access restrictions associated with changes to the information system. ','None.','MODERATE','(1) The organization employs automated mechanisms to enforce access restrictions and support auditing of the enforcement actions.','High: (1)');
INSERT INTO `blscrs` VALUES ('CM-06','OPERATIONAL','CONFIGURATION SETTINGS','Configuration Management','The organization configures the security settings of information technology products to the most restrictive mode consistent with information system operational requirements.','NIST Special Publication 800-70 provides guidance on configuration settings (i.e., checklists) for information technology products.  ','LOW','(1) The organization employs automated mechanisms to centrally manage, apply, and verify configuration settings.','High: (1)');
INSERT INTO `blscrs` VALUES ('CM-07','OPERATIONAL','LEAST FUNCTIONALITY','Configuration Management','The organization configures the information system to provide only essential capabilities and specifically prohibits and/or restricts the use of the following functions, ports, protocols, and/or services: [Assignment: organization-defined list of prohibited and/or restricted functions, ports, protocols, and/or services].','Information systems are capable of providing a wide variety of functions and services.  Some of the functions and services, provided by default, may not be necessary to support essential organizational operations (e.g., key missions, functions).  The functions and services provided by information systems should be carefully reviewed to determine which functions and services are candidates for elimination (e.g., Voice Over Internet Protocol, Instant Messaging, File Transfer Protocol, Hyper Text Transfer Protocol, file sharing).','MODERATE','(1) The organization reviews the information system [Assignment: organization-defined frequency], to identify and eliminate unnecessary functions, ports, protocols, and/or services.','High: (1)');
INSERT INTO `blscrs` VALUES ('CP-01','OPERATIONAL','CONTINGENCY PLANNING POLICY AND PROCEDURES','Contingency Planning','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, contingency planning policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the contingency planning policy and associated contingency planning controls.','The contingency planning policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The contingency planning policy can be included as part of the general information security policy for the organization.  Contingency planning procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-34 provides guidance on contingency planning.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CP-02','OPERATIONAL','CONTINGENCY PLAN','Contingency Planning','The organization develops and implements a contingency plan for the information system addressing contingency roles, responsibilities, assigned individuals with contact information, and activities associated with restoring the system after a disruption or failure.  Designated officials within the organization review and approve the contingency plan and distribute copies of the plan to key contingency personnel.','None.','LOW','(1) The organization coordinates contingency plan development with organizational elements responsible for related plans (e.g., Business Continuity Plan, Disaster Recovery Plan, Continuity of Operations Plan, Business Recovery Plan, Incident Response Plan).','High: (1)');
INSERT INTO `blscrs` VALUES ('CP-03','OPERATIONAL','CONTINGENCY TRAINING','Contingency Planning','The organization trains personnel in their contingency roles and responsibilities with respect to the information system and provides refresher training [Assignment: organization-defined frequency, at least annually].','None.','MODERATE','(1) The organization incorporates simulated events into contingency training to facilitate effective response by personnel in crisis situations. (2) The organization employs automated mechanisms to provide a more thorough and realistic training environment.  ','High: (1) ');
INSERT INTO `blscrs` VALUES ('CP-04','OPERATIONAL','CONTINGENCY PLAN TESTING','Contingency Planning','The organization tests the contingency plan for the information system [Assignment: organization-defined frequency, at least annually] using [Assignment: organization-defined tests and exercises] to determine the plans readiness to execute the plan.  Appropriate officials within the organization review the contingency plan test results and initiate corrective actions.','There are several methods for testing contingency plans to identify potential weaknesses (e.g., full-scale contingency plan testing, functional/tabletop exercises).','MODERATE','(1) The organization coordinates contingency plan testing with organizational elements responsible for related plans (e.g., Business Continuity Plan, Disaster Recovery Plan, Continuity of Operations Plan, Business Recovery Plan, Incident Response Plan). (2) The organization tests the contingency plan at the alternate processing site to familiarize contingency personnel with the facility and available resources and to evaluate the sites capabilities to support contingency operations. (3) The organization employs automated mechanisms to more thoroughly and effectively test the contingency plan.','Medium: (1) High: (1) (2)');
INSERT INTO `blscrs` VALUES ('CP-05','OPERATIONAL','CONTINGENCY PLAN UPDATE','Contingency Planning','The organization reviews the contingency plan for the information system [Assignment: organization-defined frequency, at least annually] and revises the plan to address system/organizational changes or problems encountered during plan implementation, execution, or testing.','  Organizational changes include changes in mission, functions, or business processes supported by the information system.  The organization communicates changes to appropriate organizational elements responsible for related plans (e.g., Business Continuity Plan, Disaster Recovery Plan, Continuity of Operations Plan, Business Recovery Plan, Incident Response Plan).','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('CP-06','OPERATIONAL','ALTERNATE STORAGE SITES','Contingency Planning','The organization identifies an alternate storage site and initiates necessary agreements to permit the storage of information system backup information.','None.','MODERATE','(1) The alternate storage site is geographically separated from the primary storage site so as not to be susceptible to the same hazards. (2) The alternate storage site is configured to facilitate timely and effective recovery operations.  (3) The organization identifies potential accessibility problems to the alternate storage site in the event of an area-wide disruption or disaster and outlines explicit mitigation actions.','Medium: (1) High: (1) (2) (3)');
INSERT INTO `blscrs` VALUES ('CP-07','OPERATIONAL','ALTERNATE PROCESSING SITES','Contingency Planning','The organization identifies an alternate processing site and initiates necessary agreements to permit the resumption of information system operations for critical mission/business functions within [Assignment: organization-defined time period] when the primary processing capabilities are unavailable.','Equipment and supplies required to resume operations within the organization-defined time period are either available at the alternate site or contracts are in place to support delivery to the site.','MODERATE','(1) The alternate processing site is geographically separated from the primary processing site so as not to be susceptible to the same hazards. (2) The organization identifies potential accessibility problems to the alternate processing site in the event of an area-wide disruption or disaster and outlines explicit mitigation actions. (3) Alternate processing site agreements contain priority-of-service provisions in accordance with the organizations availability requirements. (4) The alternate processing site is fully configured to support a minimum required operational capability and ready to use as the operational site.','Medium: (1) (2) (3) High: (1) (2) (3) (4)');
INSERT INTO `blscrs` VALUES ('CP-08','OPERATIONAL','TELECOMMUNICATIONS SERVICES','Contingency Planning','The organization identifies primary and alternate telecommunications services to support the information system and initiates necessary agreements to permit the resumption of system operations for critical mission/business functions within [Assignment: organization-defined time period] when the primary telecommunications capabilities are unavailable.','In the event that the primary and/or alternate telecommunications services are provided by a wireline carrier, the organization should ensure that it requests Telecommunications Service Priority (TSP) for all telecommunications services used for national security emergency preparedness (see http://tsp.ncs.gov for a full explanation of the TSP program).','MODERATE','(1) Primary and alternate telecommunications service agreements contain priority-of-service provisions in accordance with the organizations availability requirements. (2) Alternate telecommunications services do not share a single point of failure with primary telecommunications services. (3) Alternate telecommunications service providers are sufficiently separated from primary service providers so as not to be susceptible to the same hazards. (4) Primary and alternate telecommunications service providers have adequate contingency plans.','Medium: (1) (2) High: (1) (2) (3) (4)');
INSERT INTO `blscrs` VALUES ('CP-09','OPERATIONAL','INFORMATION SYSTEM BACKUP','Contingency Planning','The organization conducts backups of user-level and system-level information (including system state information) contained in the information system [Assignment: organization-defined frequency] and stores backup information at an appropriately secured location.','The frequency of information system backups and the transfer rate of backup information to alternate storage sites (if so designated) are consistent with the organizations recovery time objectives and recovery point objectives.','LOW','(1) The organization tests backup information [Assignment: organization-defined frequency] to ensure media reliability and information integrity. (2) The organization selectively uses backup information in the restoration of information system functions as part of contingency plan testing. (3) The organization stores backup copies of the operating system and other critical information system software in a separate facility or in a fire-rated container that is not collocated with the operational software.','Medium: (1) High: (1) (2) (3)');
INSERT INTO `blscrs` VALUES ('CP-10','OPERATIONAL','INFORMATION SYSTEM RECOVERY AND RECONSTITUTION','Contingency Planning','The organization employs mechanisms with supporting procedures to allow the information system to be recovered and reconstituted to the systems original state after a disruption or failure.','Secure information system recovery and reconstitution to the systems original state means that all system parameters (either default or organization-established) are reset, patches are reinstalled, configuration settings are reestablished, system documentation and operating procedures are available, application and system software is reinstalled, information from the most recent backups is available, and the system is fully tested.','LOW','(1) The organization includes a full recovery and reconstitution of the information system as part of contingency plan testing.','High: (1)');
INSERT INTO `blscrs` VALUES ('IA-01','TECHNICAL','IDENTIFICATION AND AUTHENTICATION POLICY AND PROCEDURES','Identification and Authentication','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, identification and authentication policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the identification and authentication policy and associated identification and authentication controls.','The identification and authentication policy and procedures are consistent with: (i) FIPS 201 and Special Publications 800-73 and 800-76; and (ii) other applicable federal laws, directives, policies, regulations, standards, and guidance.  The identification and authentication policy can be included as part of the general information security policy for the organization.  Identification and authentication procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.  NIST Special Publication 800-63 provides guidance on remote electronic authentication. ','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('IA-02','TECHNICAL','USER IDENTIFICATION AND AUTHENTICATION','Identification and Authentication','The information system uniquely identifies and authenticates users (or processes acting on behalf of users).','Authentication of user identities is accomplished through the use of passwords, tokens, biometrics, or in the case of multifactor authentication, some combination therein.  FIPS 201 and Special Publications 800-73 and 800-76 specify a personal identity verification (PIV) card token for use in the unique identification and authentication of federal employees and contractors.  NIST Special Publication 800-63 provides guidance on remote electronic authentication.  For other than remote situations, when users identify and authenticate to information systems within a specified security perimeter which is considered to offer sufficient protection, NIST Special Publication 800-63 guidance should be applied as follows: (i) for low-impact information systems, tokens that meet Level 1, 2, 3, or 4 requirements are acceptable; (ii) for moderate-impact information systems, tokens that meet Level 2, 3, or 4 requirements are acceptable; and (iii) for high-impact information systems, tokens that meet Level 3 or 4 requirements are acceptable.  In addition to identifying and authenticating users at the information system level, identification and authentication mechanisms are employed at the application level, when necessary, to provide increased information security for the organization.','LOW','(1) The information system employs multifactor authentication. ','High: (1)');
INSERT INTO `blscrs` VALUES ('IA-03','TECHNICAL','DEVICE IDENTIFICATION AND AUTHENTICATION','Identification and Authentication','The information system identifies and authenticates specific devices before establishing a connection.','The information system typically uses either shared known information (e.g., Media Access Control (MAC) or Transmission Control Program/Internet Protocol (TCP/IP) addresses) or an organizational authentication solution (e.g., IEEE 802.1x and Extensible Authentication Protocol (EAP) or a Radius server with EAP-Transport Layer Security (TLS) authentication) to identify and authenticate devices on local and/or wide area networks. ','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('IA-04','TECHNICAL','IDENTIFIER MANAGEMENT','Identification and Authentication','The organization manages user identifiers by: (i) uniquely identifying each user; (ii) verifying the identity of each user; (iii) receiving authorization to issue a user identifier from an appropriate organization official; (iv) ensuring that the user identifier is issued to the intended party; (v) disabling user identifier after [Assignment: organization-defined time period] of inactivity; and (vi) archiving user identifiers.','Identifier management is not applicable to shared information system accounts (e.g., guest and anonymous accounts).  FIPS 201 and Special Publications 800-73 and 800-76 specify a personal identity verification (PIV) card token for use in the unique identification and authentication of federal employees and contractors.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('IA-05','TECHNICAL','AUTHENTICATOR MANAGEMENT','Identification and Authentication','The organization manages information system authenticators (e.g., tokens, PKI certificates, biometrics, passwords, key cards) by: (i) defining initial authenticator content; (ii) establishing administrative procedures for initial authenticator distribution, for lost/compromised, or damaged authenticators, and for revoking authenticators; and (iii) changing default authenticators upon information system installation.','Users take reasonable measures to safeguard authenticators including maintaining possession of their individual authenticators, not loaning or sharing authenticators with others, and reporting lost or compromised authenticators immediately.  For password-based authentication, the information system: (i) protects passwords from unauthorized disclosure and modification when stored and transmitted; (ii) prohibits passwords from being displayed when entered; (iii) enforces password minimum and maximum lifetime restrictions; and (iv) prohibits password reuse for a specified number of generations.  For PKI-based authentication, the information system: (i) validates certificates by constructing a certification path to an accepted trust anchor; (ii) establishes user control of the corresponding private key; and (iii) maps the authenticated identity to the user account.  FIPS 201 and Special Publications 800-73 and 800-76 specify a personal identity verification (PIV) card token for use in the unique identification and authentication of federal employees and contractors.  NIST Special Publication 800-63 provides guidance on remote electronic authentication.  ','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('IA-06','TECHNICAL','AUTHENTICATOR FEEDBACK','Identification and Authentication','The information system provides feedback to a user during an attempted authentication and that feedback does not compromise the authentication mechanism.','The information system may obscure feedback of authentication information during the authentication process (e.g., displaying asterisks when a user types in a password).','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('IA-07','TECHNICAL','CRYPTOGRAPHIC MODULE AUTHENTICATION','Identification and Authentication','For authentication to a cryptographic module, the information system employs authentication methods that meet the requirements of FIPS 140-2.','None.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('IR-01','OPERATIONAL','INCIDENT RESPONSE POLICY AND PROCEDURES','Incident Response','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, incident response policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the incident response policy and associated incident response controls.','The incident response policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The incident response policy can be included as part of the general information security policy for the organization.  Incident response procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-61 provides guidance on incident handling and reporting.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('IR-02','OPERATIONAL','INCIDENT RESPONSE TRAINING','Incident Response','The organization trains personnel in their incident response roles and responsibilities with respect to the information system and provides refresher training [Assignment: organization-defined frequency, at least annually].','None.','MODERATE','(1) The organization incorporates simulated events into incident response training to facilitate effective response by personnel in crisis situations.  (2)  The organization employs automated mechanisms to provide a more thorough and realistic training environment.','High: (1) (2)');
INSERT INTO `blscrs` VALUES ('IR-03','OPERATIONAL','INCIDENT RESPONSE TESTING','Incident Response','The organization tests the incident response capability for the information system [Assignment: organization-defined frequency, at least annually] using [Assignment: organization-defined tests and exercises] to determine the incident response effectiveness and documents the results.','None.','MODERATE','(1) The organization employs automated mechanisms to more thoroughly and effectively test the incident response capability.','High: (1)');
INSERT INTO `blscrs` VALUES ('IR-04','OPERATIONAL','INCIDENT HANDLING','Incident Response','The organization implements an incident handling capability for security incidents that includes preparation, detection and analysis, containment, eradication, and recovery.','The organization incorporates the lessons learned from ongoing incident handling activities into the incident response procedures and implements the procedures accordingly.','LOW','(1) The organization employs automated mechanisms to support the incident handling process.','Medium: (1) High: (1)');
INSERT INTO `blscrs` VALUES ('IR-05','OPERATIONAL','INCIDENT MONITORING','Incident Response','The organization tracks and documents information system security incidents on an ongoing basis.','None.','MODERATE','(1) The organization employs automated mechanisms to assist in the tracking of security incidents and in the collection and analysis of incident information.','High: (1)');
INSERT INTO `blscrs` VALUES ('IR-06','OPERATIONAL','INCIDENT REPORTING','Incident Response','The organization promptly reports incident information to appropriate authorities.','The types of incident information reported, the content and timeliness of the reports, and the list of designated reporting authorities or organizations are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.','LOW','(1) The organization employs automated mechanisms to assist in the reporting of security incidents.','Medium: (1) High: (1)');
INSERT INTO `blscrs` VALUES ('IR-07','OPERATIONAL','INCIDENT RESPONSE ASSISTANCE','Incident Response','The organization provides an incident support resource that offers advice and assistance to users of the information system for the handling and reporting of security incidents.  The support resource is an integral part of the organizations incident response capability. ','Possible implementations of incident support resources in an organization include a help desk or an assistance group and access to forensics services, when required.','LOW','(1) The organization employs automated mechanisms to increase the availability of incident response-related information and support.','Medium: (1) High: (1)');
INSERT INTO `blscrs` VALUES ('MA-01','OPERATIONAL','SYSTEM MAINTENANCE POLICY AND PROCEDURES','Maintenance','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, information system maintenance policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the information system maintenance policy and associated system maintenance controls.','The information system maintenance policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The information system maintenance policy can be included as part of the general information security policy for the organization.  System maintenance procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('MA-02','OPERATIONAL','PERIODIC MAINTENANCE','Maintenance','The organization schedules, performs, and documents routine preventative and regular maintenance on the components of the information system in accordance with manufacturer or vendor specifications and/or organizational requirements.','Appropriate organizational officials approve the removal of the information system or information system components from the facility when repairs are necessary.  If the information system or component of the system requires off-site repair, the organization removes all information from associated media using approved procedures.  After maintenance is performed on the information system, the organization checks the security features to ensure that they are still functioning properly.','LOW','(1) The organization maintains a maintenance log for the information system that includes: (i) the date and time of maintenance; (ii) name of the individual performing the maintenance; (iii) name of escort, if necessary; (iv) a description of the maintenance performed; and (v) a list of equipment removed or replaced (including identification numbers, if applicable). (2) The organization employs automated mechanisms to ensure that periodic maintenance is scheduled and conducted as required, and that a log of maintenance actions, both needed and completed, is up to date, accurate, complete, and available.','Medium: (1) High: (1) (2)');
INSERT INTO `blscrs` VALUES ('MA-03','OPERATIONAL','MAINTENANCE TOOLS','Maintenance','The organization approves, controls, and monitors the use of information system maintenance tools and maintains the tools on an ongoing basis.','None.','MODERATE','(1) The organization inspects all maintenance tools (e.g., diagnostic and test equipment) carried into a facility by maintenance personnel for obvious improper modifications. (2) The organization checks all media containing diagnostic test programs (e.g., software or firmware used for system maintenance or diagnostics) for malicious code before the media are used in the information system. (3) The organization checks all maintenance equipment with the capability of retaining information to ensure that no organizational information is written on the equipment or the equipment is appropriately sanitized before release; if the equipment cannot be sanitized, the equipment remains within the facility or is destroyed, unless an appropriate organization official explicitly authorizes an exception. (4) The organization employs automated mechanisms to ensure only authorized personnel use maintenance tools.','High: (1) (2) (3)');
INSERT INTO `blscrs` VALUES ('MA-04','OPERATIONAL','REMOTE MAINTENANCE','Maintenance','The organization approves, controls, and monitors remotely executed maintenance and diagnostic activities.','The organization describes the use of remote diagnostic tools in the security plan for the information system.  The organization maintains maintenance logs for all remote maintenance, diagnostic, and service activities.  Appropriate organization officials periodically review maintenance logs.  Other techniques to consider for improving the security of remote maintenance include: (i) encryption and decryption of diagnostic communications; (ii) strong identification and authentication techniques, such as Level 3 or 4 tokens as described in NIST Special Publication 800-63; and (iii) remote disconnect verification.  When remote maintenance is completed, the organization (or information system in certain cases) terminates all sessions and remote connections.  If password-based authentication is used during remote maintenance, the organization changes the passwords following each remote maintenance service.  For high-impact information systems, if remote diagnostic or maintenance services are required from a service or organization that does not implement for its own information system the same level of security as that implemented on the system being serviced, the system being serviced is sanitized and physically separated from other information systems before the connection of the remote access line.  If the information system cannot be sanitized (e.g., due to a system failure), remote maintenance is not allowed.','LOW','(1) The organization audits all remote maintenance sessions, and appropriate organizational personnel review the audit logs of the remote sessions (2) The organization addresses the installation and use of remote diagnostic links in the security plan for the information system. (3) Remote diagnostic or maintenance services are acceptable if performed by a service or organization that implements for its own information system the same level of security as that implemented on the information system being serviced.','High: (1) (2) (3)');
INSERT INTO `blscrs` VALUES ('MA-05','OPERATIONAL','MAINTENANCE PERSONNEL','Maintenance','The organization maintains a list of personnel authorized to perform maintenance on the information system.  Only authorized personnel perform maintenance on the information system. ','Maintenance personnel have appropriate access authorizations to the information system when maintenance activities allow access to organizational information.  When maintenance personnel do not have needed access authorizations, organizational personnel with appropriate access authorizations supervise maintenance personnel during the performance of maintenance activities on the information system.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('MA-06','OPERATIONAL','TIMELY MAINTENANCE','Maintenance','The organization obtains maintenance support and spare parts for [Assignment: organization-defined list of key information system components] within [Assignment: organization-defined time period] of failure.','None.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('MP-01','OPERATIONAL','MEDIA PROTECTION POLICY AND PROCEDURES','Media Protection','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, media protection policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the media protection policy and associated media protection controls.','The media protection policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The media protection policy can be included as part of the general information security policy for the organization.  Media protection procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('MP-02','OPERATIONAL','MEDIA ACCESS','Media Protection','The organization ensures that only authorized users have access to information in printed form or on digital media removed from the information system.','None.','LOW','(1) Unless guard stations control access to media storage areas, the organization employs automated mechanisms to ensure only authorized access to such storage areas and to audit access attempts and access granted.','High: (1)');
INSERT INTO `blscrs` VALUES ('MP-03','OPERATIONAL','MEDIA LABELING','Media Protection','The organization affixes external labels to removable information storage media and information system output indicating the distribution limitations and handling caveats of the information.  The organization exempts the following specific types of media or hardware components from labeling so long as they remain within a secure environment: [Assignment: organization-defined list of media types and hardware components].','The organization marks human-readable output appropriately in accordance with applicable policies and procedures.  At a minimum, the organization affixes printed output that is not otherwise appropriately marked, with cover sheets and labels digital media with the distribution limitations, handling caveats, and applicable security markings, if any, of the information.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('MP-04','OPERATIONAL','MEDIA STORAGE','Media Protection','The organization physically controls and securely stores information system media, both paper and digital, based on the highest FIPS 199 security category of the information recorded on the media.','The organization protects information system media until the media are destroyed or sanitized using approved equipment, techniques, and procedures.  The organization protects unmarked media at the highest FIPS 199 security category for the information system until the media are reviewed and appropriately labeled.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('MP-05','OPERATIONAL','MEDIA TRANSPORT','Media Protection','The organization controls information system media (paper and digital) and restricts the pickup, receipt, transfer, and delivery of such media to authorized personnel.  ','None.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('MP-06','OPERATIONAL','MEDIA SANITIZATION','Media Protection','The organization sanitizes information system digital media using approved equipment, techniques, and procedures.  The organization tracks, documents, and verifies media sanitization actions and periodically tests sanitization equipment/procedures to ensure correct performance.','Sanitization is the process used to remove information from digital media such that information recovery is not possible.  Sanitization includes removing all labels, markings, and activity logs.  Sanitization techniques, including degaussing and overwriting memory locations, ensure that organizational information is not disclosed to unauthorized individuals when such media is reused or disposed.  The National Security Agency maintains a listing of approved products at http://www.nsa.gov/ia/government/mdg.cfm with degaussing capability.  The product selected is appropriate for the type of media being degaussed.  NIST Special Publication 800-36 provides guidance on appropriate sanitization equipment, techniques and procedures.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('MP-07','OPERATIONAL','MEDIA DESTRUCTION AND DISPOSAL','Media Protection','The organization sanitizes or destroys information system digital media before its disposal or release for reuse outside the organization, to prevent unauthorized individuals from gaining access to and using the information contained on the media.','The organization: (i) sanitizes information system hardware and machine-readable media using approved methods before being released for reuse outside of the organization; or (ii) destroys the hardware/media.  Media destruction and disposal should be accomplished in an environmentally approved manner.  The National Security Agency provides media destruction guidance at http://www.nsa.gov/ia/government/mdg.cfm.  The organization destroys information storage media when no longer needed in accordance with organization-approved methods and organizational policy and procedures.  The organization tracks, documents, and verifies media destruction and disposal actions.  The organization physically destroys nonmagnetic (optical) media (e.g., compact disks, digital video disks) in a safe and effective manner.  NIST Special Publication 800-36 provides guidance on appropriate sanitization equipment, techniques and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PE-01','OPERATIONAL','PHYSICAL AND ENVIRONMENTAL PROTECTION POLICY AND PROCEDURES','Physical and Environmental Protection','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, physical and environmental protection policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the physical and environmental protection policy and associated physical and environmental protection controls.','The physical and environmental protection policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The physical and environmental protection policy can be included as part of the general information security policy for the organization.  Physical and environmental protection procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PE-02','OPERATIONAL','PHYSICAL ACCESS AUTHORIZATIONS','Physical and Environmental Protection','The organization develops and keeps current lists of personnel with authorized access to facilities containing information systems (except for those areas within the facilities officially designated as publicly accessible) and issues appropriate authorization credentials (e.g., badges, identification cards, smart cards).  Designated officials within the organization review and approve the access list and authorization credentials [Assignment: organization-defined frequency, at least annually].','The organization promptly removes personnel no longer requiring access from access lists.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PE-03','OPERATIONAL','PHYSICAL ACCESS CONTROL','Physical and Environmental Protection','The organization controls all physical access points (including designated entry/exit points) to facilities containing information systems (except for those areas within the facilities officially designated as publicly accessible) and verifies individual access authorizations before granting access to the facilities.  The organization also controls access to areas officially designated as publicly accessible, as appropriate, in accordance with the organizations assessment of risk.','The organization uses physical access devices (e.g., keys, locks, combinations, card readers) and/or guards to control entry to facilities containing information systems.  The organization secures keys, combinations, and other access devices and inventories those devices regularly.  The organization changes combinations and keys: (i) periodically; and (ii) when keys are lost, combinations are compromised, or individuals are transferred or terminated.  After an emergency-related event, the organization restricts reentry to facilities to authorized individuals only.  Workstations and associated peripherals connected to (and part of) an organizational information system may be located in areas designated as publicly accessible with access to such devices being appropriately controlled.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PE-04','OPERATIONAL','ACCESS CONTROL FOR TRANSMISSION MEDIUM','Physical and Environmental Protection','The organization controls physical access to information system transmission lines carrying unencrypted information to prevent eavesdropping, in-transit modification, disruption, or physical tampering.','None.','NONE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PE-05','OPERATIONAL','ACCESS CONTROL FOR DISPLAY MEDIUM','Physical and Environmental Protection','The organization controls physical access to information system devices that display information to prevent unauthorized individuals from observing the display output.','None.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PE-06','OPERATIONAL','MONITORING PHYSICAL ACCESS ','Physical and Environmental Protection','The organization monitors physical access to information systems to detect and respond to incidents.','The organization reviews physical access logs periodically, investigates apparent security violations or suspicious physical access activities, and takes remedial actions.','LOW','(1) The organization monitors real-time intrusion alarms and surveillance equipment. (2) The organization employs automated mechanisms to ensure potential intrusions are recognized and appropriate response actions initiated.','Medium: (1) High: (1) (2)');
INSERT INTO `blscrs` VALUES ('PE-07','OPERATIONAL','VISITOR CONTROL','Physical and Environmental Protection','The organization controls physical access to information systems by authenticating visitors before authorizing access to facilities or areas other than areas designated as publicly accessible.','Government contractors and others with permanent authorization credentials are not considered visitors.','LOW','(1) The organization escorts visitors and monitors visitor activity, when required.','Medium: (1) High: (1)');
INSERT INTO `blscrs` VALUES ('PE-08','OPERATIONAL','ACCESS LOGS','Physical and Environmental Protection',' The organization maintains a visitor access log to facilities (except for those areas within the facilities officially designated as publicly accessible) that includes: (i) name and organization of the person visiting; (ii) signature of the visitor; (iii) form of identification; (iv) date of access; (v) time of entry and departure; (vi) purpose of visit; and (vii) name and organization of person visited.  Designated officials within the organization review the access logs [Assignment: organization-defined frequency] after closeout.','None.','LOW','(1) The organization employs automated mechanisms to facilitate the maintenance and review of access logs.','Medium: (1) High: (1)');
INSERT INTO `blscrs` VALUES ('PE-09','OPERATIONAL','POWER EQUIPMENT AND POWER CABLING','Physical and Environmental Protection','The organization protects power equipment and power cabling for the information system from damage and destruction.','None.','MODERATE','(1) The organization employs redundant and parallel power cabling paths.','N/A');
INSERT INTO `blscrs` VALUES ('PE-10','OPERATIONAL','EMERGENCY SHUTOFF','Physical and Environmental Protection','For specific locations within a facility containing concentrations of information system resources (e.g., data centers, server rooms, mainframe rooms), the organization provides the capability of shutting off power to any information technology component that may be malfunctioning (e.g., due to an electrical fire) or threatened (e.g., due to a water leak) without endangering personnel by requiring them to approach the equipment.','None.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PE-11','OPERATIONAL','EMERGENCY POWER','Physical and Environmental Protection','The organization provides a short-term uninterruptible power supply to facilitate an orderly shutdown of the information system in the event of a primary power source loss.','None.','MODERATE','(1) The organization provides a long-term alternate power supply for the information system that is capable of maintaining minimally required operational capability in the event of an extended loss of the primary power source. (2) The organization provides a long-term alternate power supply for the information system that is self-contained and not reliant on external power generation.','High: (1)');
INSERT INTO `blscrs` VALUES ('PE-12','OPERATIONAL','EMERGENCY LIGHTING','Physical and Environmental Protection','The organization employs and maintains automatic emergency lighting systems that activate in the event of a power outage or disruption and that cover emergency exits and evacuation routes.','None.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PE-13','OPERATIONAL','FIRE PROTECTION','Physical and Environmental Protection','The organization employs and maintains fire suppression and detection devices/systems that can be activated in the event of a fire.','Fire suppression and detection devices/systems include, but are not limited to, sprinkler systems, handheld fire extinguishers, fixed fire hoses, and smoke detectors.','LOW','(1) Fire suppression and detection devices/systems activate automatically in the event of a fire. (2) Fire suppression and detection devices/systems provide automatic notification of any activation to the organization and emergency responders.','Medium: (1) High: (1) (2)');
INSERT INTO `blscrs` VALUES ('PE-14','OPERATIONAL','TEMPERATURE AND HUMIDITY CONTROLS','Physical and Environmental Protection','The organization regularly maintains within acceptable levels and monitors the temperature and humidity within facilities containing information systems.','None.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PE-15','OPERATIONAL','WATER DAMAGE PROTECTION','Physical and Environmental Protection','The organization protects the information system from water damage resulting from broken plumbing lines or other sources of water leakage by ensuring that master shutoff valves are accessible, working properly, and known to key personnel.','None.','LOW','(1) The organization employs automated mechanisms to automatically close shutoff valves in the event of a significant water leak.','High: (1)');
INSERT INTO `blscrs` VALUES ('PE-16','OPERATIONAL','DELIVERY AND REMOVAL','Physical and Environmental Protection','The organization controls information system-related items (i.e., hardware, firmware, software) entering and exiting the facility and maintains appropriate records of those items.','The organization controls delivery areas and, if possible, isolates the areas from the information system and media libraries to avoid unauthorized access.  Appropriate organizational officials authorize the delivery or removal of information system-related items belonging to the organization.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PE-17','OPERATIONAL','ALTERNATE WORK SITE','Physical and Environmental Protection','Individuals within the organization employ appropriate information system security controls at alternate work sites.','NIST Special Publication 800-46 provides guidance on security in telecommuting and broadband communications.  The organization provides a means for employees to communicate with information system security staff in case of security problems.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PL-01','MANAGEMENT','SECURITY PLANNING POLICY AND PROCEDURES','Planning','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, security planning policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the security planning policy and associated security planning controls.','The security planning policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The security planning policy can be included as part of the general information security policy for the organization.  Security planning procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-18 provides guidance on security planning.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PL-02','MANAGEMENT','SYSTEM SECURITY PLAN','Planning','The organization develops and implements a security plan for the information system that provides an overview of the security requirements for the system and a description of the security controls in place or planned for meeting those requirements.  Designated officials within the organization review and approve the plan.','NIST Special Publication 800-18 provides guidance on security planning.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PL-03','MANAGEMENT','SYSTEM SECURITY PLAN UPDATE','Planning','The organization reviews the security plan for the information system [Assignment: organization-defined frequency] and revises the plan to address system/organizational changes or problems identified during plan implementation or security control assessments.','Significant changes are defined in advance by the organization and identified in the configuration management process.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PL-04','MANAGEMENT','RULES OF BEHAVIOR','Planning','The organization establishes and makes readily available to all information system users a set of rules that describes their responsibilities and expected behavior with regard to information system usage.  The organization receives signed acknowledgement from users indicating that they have read, understand, and agree to abide by the rules of behavior, before authorizing access to the information system.','Electronic signatures are acceptable for use in acknowledging rules of behavior.  NIST Special Publication 800-18 provides guidance on preparing rules of behavior.  ','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PL-05','MANAGEMENT','PRIVACY IMPACT ASSESSMENT','Planning','The organization conducts a privacy impact assessment on the information system.','OMB Memorandum 03-22 provides guidance for implementing the privacy provisions of the E-Government Act of 2002.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PS-01','OPERATIONAL','PERSONNEL SECURITY POLICY AND PROCEDURES','Personnel Security','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, personnel security policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the personnel security policy and associated personnel security controls.','The personnel security policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The personnel security policy can be included as part of the general information security policy for the organization.  Personnel security procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PS-02','OPERATIONAL','POSITION CATEGORIZATION','Personnel Security','The organization assigns a risk designation to all positions and establishes screening criteria for individuals filling those positions.  The organization reviews and revises position risk designations [Assignment: organization-defined frequency].','Position risk designations are consistent with 5 CFR 731.106(a) and Office of Personnel Management policy and guidance.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PS-03','OPERATIONAL','PERSONNEL SCREENING','Personnel Security','The organization screens individuals requiring access to organizational information and information systems before authorizing access.','Screening is consistent with: (i) 5 CFR 731.106(a); (ii) Office of Personnel Management policy, regulations, and guidance; (iii) organizational policy, regulations, and guidance; (iv) FIPS 201 and Special Publications 800-73 and 800-76; and (v) the criteria established for the risk designation of the assigned position.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PS-04','OPERATIONAL','PERSONNEL TERMINATION','Personnel Security','When employment is terminated, the organization terminates information system access, conducts exit interviews, ensures the return of all organizational information system-related property (e.g., keys, identification cards, building passes), and ensures that appropriate personnel have access to official records created by the terminated employee that are stored on organizational information systems.','None.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PS-05','OPERATIONAL','PERSONNEL TRANSFER','Personnel Security','The organization reviews information systems/facilities access authorizations when individuals are reassigned or transferred to other positions within the organization and initiates appropriate actions (e.g., reissuing keys, identification cards, building passes; closing old accounts and establishing new accounts; and changing system access authorizations).','None.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PS-06','OPERATIONAL','ACCESS AGREEMENTS','Personnel Security','The organization completes appropriate access agreements (e.g., nondisclosure agreements, acceptable use agreements, rules of behavior, conflict-of-interest agreements) for individuals requiring access to organizational information and information systems before authorizing access.','None.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PS-07','OPERATIONAL','THIRD-PARTY PERSONNEL SECURITY','Personnel Security','The organization establishes personnel security requirements for third-party providers (e.g., service bureaus, contractors, and other organizations providing information system development, information technology services, outsourced applications, network and security management) and monitors provider compliance to ensure adequate security.','The organization explicitly includes personnel security requirements in acquisition-related documents.  NIST Special Publication 800-35 provides guidance on information technology security services.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('PS-08','OPERATIONAL','PERSONNEL SANCTIONS','Personnel Security','The organization employs a formal sanctions process for personnel failing to comply with established information security policies and procedures.','The sanctions process is consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The sanctions process can be included as part of the general personnel policies and procedures for the organization.  ','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('RA-01','MANAGEMENT','RISK ASSESSMENT POLICY AND PROCEDURES','Risk Assessment','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented risk assessment policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the risk assessment policy and associated risk assessment controls.','The risk assessment policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The risk assessment policy can be included as part of the general information security policy for the organization.  Risk assessment procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publications 800-30 provides guidance on the assessment of risk.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('RA-02','MANAGEMENT','SECURITY CATEGORIZATION','Risk Assessment','The organization categorizes the information system and the information processed, stored, or transmitted by the system in accordance with FIPS 199 and documents the results (including supporting rationale) in the system security plan.  Designated senior-level officials within the organization review and approve the security categorizations.','NIST Special Publication 800-60 provides guidance on determining the security categories of the information types resident on the information system.  The organization conducts security categorizations as an organization-wide activity with the involvement of the chief information officer, senior agency information security officer, information system owners, and information owners.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('RA-03','MANAGEMENT','RISK ASSESSMENT','Risk Assessment','The organization conducts assessments of the risk and magnitude of harm that could result from the unauthorized access, use, disclosure, disruption, modification, or destruction of information and information systems that support the operations and assets of the agency. ','Risk assessments take into account vulnerabilities, threat sources, and security controls planned or in place to determine the resulting level of residual risk posed to organizational operations, organizational assets, or individuals based on the operation of the information system.  NIST Special Publication 800-30 provides guidance on conducting risk assessments including threat, vulnerability, and impact assessments.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('RA-04','MANAGEMENT','RISK ASSESSMENT UPDATE','Risk Assessment','The organization updates the risk assessment [Assignment: organization-defined frequency] or whenever there are significant changes to the information system, the facilities where the system resides, or other conditions that may impact the security or accreditation status of the system.','The organization develops and documents specific criteria for what is considered significant change to the information system.  NIST Special Publication 800-30 provides guidance on conducting risk assessment updates.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('RA-05','MANAGEMENT','VULNERABILITY SCANNING','Risk Assessment','Using appropriate vulnerability scanning tools and techniques, the organization scans for vulnerabilities in the information system [Assignment: organization-defined frequency] or when significant new vulnerabilities affecting the system are identified and reported.','The organization trains selected personnel in the use and maintenance of vulnerability scanning tools and techniques.  The information obtained from the vulnerability scanning process is freely shared with appropriate personnel throughout the organization to help eliminate similar vulnerabilities in other information systems.  Vulnerability analysis for custom software and applications may require additional, more specialized approaches (e.g., vulnerability scanning tools for applications, source code reviews, static analysis of source code).  NIST Special Publication 800-42 provides guidance on network security testing.  NIST Special Publication 800-40 provides guidance on handling security patches.','MODERATE','(1) Vulnerability scanning tools include the capability to readily update the list of vulnerabilities scanned. (2) The organization updates the list of information system vulnerabilities [Assignment: organization-defined frequency] or when significant new vulnerabilities are identified and reported. (3) Vulnerability scanning procedures include means to ensure adequate scan coverage, both vulnerabilities checked and information system components scanned.','High: (1) (2)');
INSERT INTO `blscrs` VALUES ('SA-01','MANAGEMENT','SYSTEM AND SERVICES ACQUISITION POLICY AND PROCEDURES','System and Services Acquisition','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, system and services acquisition policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the system and services acquisition policy and associated system and services acquisition controls.','The system and services acquisition policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The system and services acquisition policy can be included as part of the general information security policy for the organization.  System and services acquisition procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SA-02','MANAGEMENT','ALLOCATION OF RESOURCES','System and Services Acquisition','The organization determines, documents, and allocates as part of its capital planning and investment control process the resources required to adequately protect the information system.','The organization includes the determination of security requirements for the information system in mission/business case planning and establishes a discrete line item for information system security in the organizations programming and budgeting documentation. NIST Special Publication 800-65 provides guidance on integrating security into the capital planning and investment control process.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SA-03','MANAGEMENT','LIFE CYCLE SUPPORT','System and Services Acquisition','The organization manages the information system using a system development life cycle methodology that includes information security considerations.','NIST Special Publication 800-64 provides guidance on security considerations in the system development life cycle.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SA-04','MANAGEMENT','ACQUISITIONS','System and Services Acquisition','The organization includes security requirements and/or security specifications, either explicitly or by reference, in information system acquisition contracts based on an assessment of risk.','Solicitation DocumentsThe solicitation documents (e.g., Requests for Proposals) for information systems and services include, either explicitly or by reference, security requirements that describe: (i) required security capabilities; (ii) required design and development processes; (iii) required test and evaluation procedures; and (iv) required documentation.  The requirements in the solicitation documents permit updating security controls as new threats/vulnerabilities are identified and as new technologies are implemented.  NIST Special Publication 800-53 provides guidance on recommended security controls for federal information systems to meet minimum security requirements for information systems categorized in accordance with FIPS 199.  NIST Special Publication 800-36 provides guidance on the selection of information security products.  NIST Special Publication 800-35 provides guidance on information technology security services.  NIST Special Publication 800-64 provides guidance on security considerations in the system development life cycle.Use of Tested, Evaluated, and Validated ProductsNIST Special Publication 800-23 provides guidance on the acquisition and use of tested/evaluated information technology products.Configuration Settings and Implementation GuidanceThe information system required documentation includes security configuration settings and security implementation guidance.  NIST Special Publication 800-70 provides guidance on configuration settings for information technology products.  ','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SA-05','MANAGEMENT','INFORMATION SYSTEM DOCUMENTATION','System and Services Acquisition','The organization ensures that adequate documentation for the information system and its constituent components is available, protected when required, and distributed to authorized personnel.','Administrator and user guides include information on: (i) configuring, installing, and operating the information system; and (ii) optimizing the systems security features.  NIST Special Publication 800-70 provides guidance on configuration settings for information technology products.','LOW','(1) The organization includes documentation describing the functional properties of the security controls employed within the information system with sufficient detail to permit analysis and testing of the controls. (2) The organization includes documentation describing the design and implementation details of the security controls employed within the information system with sufficient detail to permit analysis and testing of the controls (including functional interfaces among control components).','Medium: (1) High: (1) (2)');
INSERT INTO `blscrs` VALUES ('SA-06','MANAGEMENT','SOFTWARE USAGE RESTRICTIONS','System and Services Acquisition','The organization complies with software usage restrictions.','Software and associated documentation is used in accordance with contract agreements and copyright laws.  For software and associated documentation protected by quantity licenses, the organization employs tracking systems to control copying and distribution.  The organization controls and documents the use of publicly accessible peer-to-peer file sharing technology to ensure that this capability is not used for the unauthorized distribution, display, performance, or reproduction of copyrighted work.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SA-07','MANAGEMENT','USER INSTALLED SOFTWARE','System and Services Acquisition','The organization enforces explicit rules governing the downloading and installation of software by users.','If provided the necessary privileges, users have the ability to download and install software.  The organization identifies what types of software downloads and installations are permitted (e.g., updates and security patches to existing software) and what types of downloads and installations are prohibited (e.g., software that is free only for personal, not government, use).  The organization also restricts the use of install-on-demand software.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SA-08','MANAGEMENT','SECURITY DESIGN PRINCIPLES','System and Services Acquisition','The organization designs and implements the information system using security engineering principles.','NIST Special Publication 800-27 provides guidance on engineering principles for information system security.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SA-09','MANAGEMENT','OUTSOURCED INFORMATION SYSTEM SERVICES','System and Services Acquisition','The organization ensures that third-party providers of information system services employ adequate security controls in accordance with applicable federal laws, directives, policies, regulations, standards, guidance, and established service level agreements.  The organization monitors security control compliance.','Third-party providers are subject to the same information system security policies and procedures of the supported organization, and must conform to the same security control and documentation requirements as would apply to the organizations internal systems.   Appropriate organizational officials approve outsourcing of information system services to third-party providers (e.g., service bureaus, contractors, and other external organizations).  The outsourced information system services documentation includes government, service provider, and end user security roles and responsibilities, and any service level agreements.  Service level agreements define the expectations of performance for each required security control, describe measurable outcomes, and identify remedies and response requirements for any identified instance of non-compliance.  NIST Special Publication 800-35 provides guidance on information technology security services.  NIST Special Publication 800-64 provides guidance on the security considerations in the system development life cycle.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SA-10','MANAGEMENT','DEVELOPER CONFIGURATION MANAGEMENT','System and Services Acquisition','The information system developer creates and implements a configuration management plan that controls changes to the system during development, tracks security flaws, requires authorization of changes, and provides documentation of the plan and its implementation.','None.','HIGH','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SA-11','MANAGEMENT','DEVELOPER SECURITY TESTING','System and Services Acquisition','The information system developer creates a security test and evaluation plan, implements the plan, and documents the results.  Developmental security test results may be used in support of the security certification and accreditation process for the delivered information system.','Developmental security test results should only be used when no security relevant modifications of the information system have been made subsequent to developer testing and after selective verification of developer test results.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-01','TECHNICAL','SYSTEM AND COMMUNICATIONS PROTECTION POLICY AND PROCEDURES','System and Communications Protection','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, system and communications protection policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the system and communications protection policy and associated system and communications protection controls.','The system and communications protection policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The system and communications protection policy can be included as part of the general information security policy for the organization.  System and communications protection procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-02','TECHNICAL','APPLICATION PARTITIONING','System and Communications Protection','The information system separates user functionality (including user interface services) from information system management functionality.','The information system physically or logically separates user interface services (e.g., public web pages) from information storage and management services (e.g., database management).  Separation may be accomplished through the use of different computers, different central processing units, different instances of the operating system, different network addresses, combinations of these methods, or other methods as appropriate.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-03','TECHNICAL','SECURITY FUNCTION ISOLATION','System and Communications Protection','The information system isolates security functions from nonsecurity functions.','The information system isolates security functions from nonsecurity functions by means of partitions, domains, etc., including control of access to and integrity of, the hardware, software, and firmware that perform those security functions.  The information system maintains a separate execution domain (e.g., address space) for each executing process.','HIGH','(1) The information system employs underlying hardware separation mechanisms to facilitate security function isolation. (2) The information system further divides the security functions with the functions enforcing access and information flow control isolated and protected from both nonsecurity functions and from other security functions. (3) The information system minimizes the amount of nonsecurity functions included within the isolation boundary containing security functions. (4) The information system security maintains its security functions in largely independent modules that avoid unnecessary interactions between modules. (5) The information system security maintains its security functions in a layered structure minimizing interactions between layers of the design.','N/A');
INSERT INTO `blscrs` VALUES ('SC-04','TECHNICAL','INFORMATION REMNANTS','System and Communications Protection','The information system prevents unauthorized and unintended information transfer via shared system resources.','Control of information system remnants, sometimes referred to as object reuse, prevents information, including encrypted representations of information, produced by the actions of a prior user/role (or the actions of a process acting on behalf of a prior user/role) from being available to any current user/role (or current process) that obtains access to a shared system resource (e.g., registers, main memory, secondary storage) after that resource has been released back to the information system.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-05','TECHNICAL','DENIAL OF SERVICE PROTECTION','System and Communications Protection','The information system protects against or limits the effects of the following types of denial of service attacks: [Assignment: organization-defined list of types of denial of service attacks or reference to source for current list].','A variety of technologies exist to limit, or in some cases, eliminate the effects of denial of service attacks.  For example, network perimeter devices can filter certain types of packets to protect devices on an organizations internal network from being directly affected by denial of service attacks.  Information systems that are publicly accessible can be protected by employing increased capacity and bandwidth combined with service redundancy.','LOW','(1) The information system restricts the ability of users to launch denial of service attacks against other information systems or networks. (2) The information system manages excess capacity, bandwidth, or other redundancy to limit the effects of information flooding types of denial of service attacks.','N/A');
INSERT INTO `blscrs` VALUES ('SC-06','TECHNICAL','RESOURCE PRIORITY','System and Communications Protection','The information system limits the use of resources by priority.','The information system limits the use of resources by priority.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-07','TECHNICAL','BOUNDARY PROTECTION','System and Communications Protection','The information system monitors and controls communications at the external boundary of the information system and at key internal boundaries within the system. ','Any connections to the Internet, or other external networks or information systems, occur through controlled interfaces (e.g., proxies, gateways, routers, firewalls, encrypted tunnels).  The operational failure of the boundary protection mechanisms does not result in any unauthorized release of information outside of the information system boundary.  Information system boundary protections at any designated alternate processing sites provide the same levels of protection as that of the primary site.','LOW','(1) The organization physically allocates publicly accessible information system components (e.g., public web servers) to separate subnetworks with separate, physical network interfaces.  The organization prevents public access into the organizations internal networks except as appropriately mediated.','Medium: (1) High: (1)');
INSERT INTO `blscrs` VALUES ('SC-08','TECHNICAL','TRANSMISSION INTEGRITY','System and Communications Protection','The information system protects the integrity of transmitted information.','The FIPS 199 security category (for integrity) of the information being transmitted should guide the decision on the use of cryptographic mechanisms.  NSTISSI No. 7003 contains guidance on the use of Protective Distribution Systems.','MODERATE','(1) The organization employs cryptographic mechanisms to ensure recognition of changes to information during transmission unless otherwise protected by alternative physical measures (e.g., protective distribution systems).','High: (1)');
INSERT INTO `blscrs` VALUES ('SC-09','TECHNICAL','TRANSMISSION CONFIDENTIALITY','System and Communications Protection','The information system protects the confidentiality of transmitted information.','The FIPS 199 security category (for confidentiality) of the information being transmitted should guide the decision on the use of cryptographic mechanisms.  NSTISSI No. 7003 contains guidance on the use of Protective Distribution Systems.','MODERATE','(1) The organization employs cryptographic mechanisms to prevent unauthorized disclosure of information during transmission unless protected by alternative physical measures (e.g., protective distribution systems).','High: (1)');
INSERT INTO `blscrs` VALUES ('SC-10','TECHNICAL','NETWORK DISCONNECT','System and Communications Protection','The information system terminates a network connection at the end of a session or after [Assignment: organization-defined time period] of inactivity.','None.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-11','TECHNICAL','TRUSTED PATH','System and Communications Protection','The information system establishes a trusted communications path between the user and the security functionality of the system. ','None.','NONE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-12','TECHNICAL','CRYPTOGRAPHIC KEY ESTABLISHMENT AND MANAGEMENT','System and Communications Protection','The information system employs automated mechanisms with supporting procedures or manual procedures for cryptographic key establishment and key management.','NIST Special Publication 800-56 provides guidance on cryptographic key establishment.  NIST Special Publication 800-57 provides guidance on cryptographic key management.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-13','TECHNICAL','USE OF VALIDATED CRYPTOGRAPHY','System and Communications Protection','When cryptography is employed within the information system, the system performs all cryptographic operations (including key generation) using FIPS 140-2 validated cryptographic modules operating in approved modes of operation. ','NIST Special Publication 800-56 provides guidance on cryptographic key establishment.  NIST Special Publication 800-57 provides guidance on cryptographic key management.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-14','TECHNICAL','PUBLIC ACCESS PROTECTIONS','System and Communications Protection','For publicly available systems, the information system protects the integrity of the information and applications.','None.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-15','TECHNICAL','COLLABORATIVE COMPUTING','System and Communications Protection','The information system prohibits remote activation of collaborative computing mechanisms (e.g., video and audio conferencing) and provides an explicit indication of use to the local users (e.g., use of camera or microphone).','None.','MODERATE','(1) The information system provides physical disconnect of camera and microphone in a manner that supports ease of use. ','N/A');
INSERT INTO `blscrs` VALUES ('SC-16','TECHNICAL','TRANSMISSION OF SECURITY PARAMETERS','System and Communications Protection','The information system reliably associates security parameters (e.g., security labels and markings) with information exchanged between information systems.','Security parameters may be explicitly or implicitly associated with the information contained within the information system.','NONE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-17','TECHNICAL','PUBLIC KEY INFRASTRUCTURE CERTIFICATES','System and Communications Protection','The organization develops and implements a certificate policy and certification practice statement for the issuance of public key certificates used in the information system.','Registration to receive a public key certificate includes authorization by a supervisor or a responsible official, and is done by a secure process that verifies the identity of the certificate holder and ensures that the certificate is issued to the intended party.  NIST Special Publication 800-63 provides guidance on remote electronic authentication. ','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-18','TECHNICAL','MOBILE CODE','System and Communications Protection','The organization: (i) establishes usage restrictions and implementation guidance for mobile code technologies based on the potential to cause damage to the information system if used maliciously; and (ii) documents, monitors, and controls the use of mobile code within the information system.  Appropriate organizational officials authorize the use of mobile code.','Mobile code technologies include, for example, Java, JavaScript, ActiveX, PDF, Postscript, Shockwave movies, Flash animations, and VBScript.  Usage restrictions and implementation guidance apply to both the selection and use of mobile code installed on organizational servers and mobile code downloaded and executed on individual workstations.  Control procedures prevent the development, acquisition, or introduction of unacceptable mobile code within the information system.  NIST Special Publication 800-28 provides guidance on active content and mobile code.  Additional information on risk-based approaches for the implementation of mobile code technologies can be found at: http://iase.disa.mil/mcp/index.html.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SC-19','TECHNICAL','VOICE OVER INTERNET PROTOCOL','System and Communications Protection','The organization: (i) establishes usage restrictions and implementation guidance for Voice Over Internet Protocol (VOIP) technologies based on the potential to cause damage to the information system if used maliciously; and (ii) documents, monitors, and controls the use of VOIP within the information system.  Appropriate organizational officials authorize the use of VOIP.','NIST Special Publication 800-58 provides guidance on security considerations for VOIP technologies employed in information systems.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SI-01','OPERATIONAL','SYSTEM AND INFORMATION INTEGRITY POLICY AND PROCEDURES','System and Information Integrity','The organization develops, disseminates, and periodically reviews/updates: (i) a formal, documented, system and information integrity policy that addresses purpose, scope, roles, responsibilities, and compliance; and (ii) formal, documented procedures to facilitate the implementation of the system and information integrity policy and associated system and information integrity controls.','The system and information integrity policy and procedures are consistent with applicable federal laws, directives, policies, regulations, standards, and guidance.  The system and information integrity policy can be included as part of the general information security policy for the organization.  System and information integrity procedures can be developed for the security program in general, and for a particular information system, when required.  NIST Special Publication 800-12 provides guidance on security policies and procedures.','LOW','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SI-02','OPERATIONAL','FLAW REMEDIATION','System and Information Integrity','The organization identifies, reports, and corrects information system flaws.','The organization identifies information systems containing proprietary or open source software affected by recently announced software flaws (and potential vulnerabilities resulting from those flaws).  Proprietary software can be found in either commercial/government off-the-shelf information technology component products or in custom-developed applications.  The organization (or the software developer/vendor in the case of software developed and maintained by a vendor/contractor) promptly installs newly released security relevant patches, service packs, and hot fixes, and tests patches, service packs, and hot fixes for effectiveness and potential side effects on the organization information systems before installation.  Flaws discovered during security assessments, continuous monitoring (see security controls CA-2, CA-4, or CA-7), or incident response activities (see security control IR-4) should also be addressed expeditiously.  NIST Special Publication 800-40 provides guidance on security patch installation.','LOW','(1) The organization centrally manages the flaw remediation process and installs updates automatically without individual user intervention. (2) The organization employs automated mechanisms to periodically and upon command determine the state of information system components with regard to flaw remediation.','N/A');
INSERT INTO `blscrs` VALUES ('SI-03','OPERATIONAL','MALICIOUS CODE PROTECTION','System and Information Integrity','The information system implements malicious code protection that includes a capability for automatic updates.','The organization employs virus protection mechanisms at critical information system entry and exit points (e.g., firewalls, electronic mail servers, remote-access servers) and at workstations, servers, or mobile computing devices on the network.  The organization uses the virus protection mechanisms to detect and eradicate malicious code (e.g., viruses, worms, Trojan horses) transported: (i) by electronic mail, electronic mail attachments, Internet accesses, removable media (e.g., diskettes or compact disks), or other common means; or (ii) by exploiting information system vulnerabilities.  The organization updates virus protection mechanisms (including the latest virus definitions) whenever new releases are available in accordance with organizational configuration management policy and procedures.  Consideration is given to using virus protection software products from multiple vendors (e.g., using one vendor for boundary devices and servers and another vendor for workstations).','LOW','(1) The organization centrally manages virus protection mechanisms. (2) The information system automatically updates virus protection mechanisms.','Medium: (1) High: (1) (2)');
INSERT INTO `blscrs` VALUES ('SI-04','OPERATIONAL','INTRUSION DETECTION TOOLS AND TECHNIQUES','System and Information Integrity','The organization employs tools and techniques to monitor events on the information system, detect attacks, and provide identification of unauthorized use of the system.','Intrusion detection and information system monitoring capability can be achieved through a variety of tools and techniques (e.g., intrusion detection systems, virus protection software, log monitoring software, network forensic analysis tools).','MODERATE','(1) The organization networks individual intrusion detection tools into a systemwide intrusion detection system using common protocols. (2) The organization employs automated tools to support near-real-time analysis of events in support of detecting system-level attacks. (3) The organization employs automated tools to integrate intrusion detection tools into access control and flow control mechanisms for rapid response to attacks by enabling reconfiguration of these mechanisms in support of attack isolation and elimination. (4) The information system monitors outbound communications for unusual or unauthorized activities indicating the presence of malware (e.g., malicious code, spyware, adware).','N/A');
INSERT INTO `blscrs` VALUES ('SI-05','OPERATIONAL','SECURITY ALERTS AND ADVISORIES','System and Information Integrity','The organization receives information system security alerts/advisories on a regular basis, issues alerts/advisories to appropriate personnel, and takes appropriate actions in response.','The organization documents the types of actions to be taken in response to security alerts/advisories.','LOW','(1) The organization employs automated mechanisms to make security alert and advisory information available throughout the organization as needed.','N/A');
INSERT INTO `blscrs` VALUES ('SI-06','OPERATIONAL','SECURITY FUNCTIONALITY VERIFICATION','System and Information Integrity','The information system verifies the correct operation of security functions [Selection (one or more): upon system startup and restart, upon command by user with appropriate privilege, periodically every [Assignment: organization-defined time-period]] and [Selection (one or more): notifies system administrator, shuts the system down, restarts the system] when anomalies are discovered.','None.','MODERATE','(1) The organization employs automated mechanisms to provide notification of failed security tests. (2) The organization employs automated mechanisms to support management of distributed security testing.','High: (1)');
INSERT INTO `blscrs` VALUES ('SI-07','OPERATIONAL','SOFTWARE AND INFORMATION INTEGRITY','System and Information Integrity','The information system detects and protects against unauthorized changes to software and information.','The organization employs integrity verification applications on the information system to look for evidence of information tampering, errors, and omissions.  The organization employs good software engineering practices with regard to commercial off-the-shelf integrity mechanisms (e.g., parity checks, cyclical redundancy checks, cryptographic hashes) and uses tools to automatically monitor the integrity of the information system and the applications it hosts.  ','HIGH','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SI-08','OPERATIONAL','SPAM AND SPYWARE PROTECTION','System and Information Integrity','The information system implements spam and spyware protection.','The organization employs spam and spyware protection mechanisms at critical information system entry points (e.g., firewalls, electronic mail servers, remote-access servers) and at workstations, servers, or mobile computing devices on the network.  The organization uses the spam and spyware protection mechanisms to detect and take appropriate action on unsolicited messages and spyware/adware, respectively, transported by electronic mail, electronic mail attachments, Internet accesses, removable media (e.g., diskettes or compact disks), or other common means.  Consideration is given to using spam and spyware protection software products from multiple vendors (e.g., using one vendor for boundary devices and servers and another vendor for workstations).','MODERATE','(1) The organization centrally manages spam and spyware protection mechanisms. (2) The information system automatically updates spam and spyware protection mechanisms.','High: (1)');
INSERT INTO `blscrs` VALUES ('SI-09','OPERATIONAL','INFORMATION INPUT RESTRICTIONS','System and Information Integrity','The organization restricts the information input to the information system to authorized personnel only.','Restrictions on personnel authorized to input information to the information system may extend beyond the typical access controls employed by the system and include limitations based on specific operational/project responsibilities.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SI-10','OPERATIONAL','INFORMATION INPUT ACCURACY, COMPLETENESS, AND VALIDITY','System and Information Integrity','The information system checks information inputs for accuracy, completeness, and validity.','Checks for accuracy, completeness, and validity of information should be accomplished as close to the point of origin as possible.  Rules for checking the valid syntax of information system inputs (e.g., character set, length, numerical range, acceptable values) are in place to ensure that inputs match specified definitions for format and content.  Inputs passed to interpreters should be prescreened to ensure the content is not unintentionally interpreted as commands.  The extent to which the information system is able to check the accuracy, completeness, and validity of information inputs should be guided by organizational policy and operational requirements.  ','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SI-11','OPERATIONAL','ERROR HANDLING','System and Information Integrity','The information system identifies and handles error conditions in an expeditious manner.','The structure and content of error messages should be carefully considered by the organization.  User error messages generated by the information system should provide timely and useful information to users without revealing information that could be exploited by adversaries.  System error messages should be revealed only to authorized personnel (e.g., systems administrators, maintenance personnel).  Sensitive information (e.g., account numbers, social security numbers, and credit card numbers) should not be listed in error logs or associated administrative messages.  The extent to which the information system is able to identify and handle error conditions should be guided by organizational policy and operational requirements.','MODERATE','N/A','N/A');
INSERT INTO `blscrs` VALUES ('SI-12','OPERATIONAL','INFORMATION OUTPUT HANDLING AND RETENTION','System and Information Integrity','The organization handles and retains output from the information system in accordance with organizational policy and operational requirements.','None.','MODERATE','N/A','N/A');
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `poam_evaluation_id` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `topic` varchar(64) NOT NULL default '',
  `content` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `configurations` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `key` varchar(64) NOT NULL,
  `value` varchar(64) NOT NULL,
  `description` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
INSERT INTO `configurations` VALUES (1,'max_absent_time','90','Maximum Days An Account Can Be Inactive');
INSERT INTO `configurations` VALUES (2,'failure_threshold','3','Maximum Login Attempts Before Server Locks Account');
INSERT INTO `configurations` VALUES (3,'expiring_seconds','1800','Session Timeout (In Seconds)');
INSERT INTO `configurations` VALUES (4,'auth_type','database','authentication type');
INSERT INTO `configurations` VALUES (6,'subject','','Email Subject');
INSERT INTO `configurations` VALUES (7,'smtp_host','','Smtp server name');
INSERT INTO `configurations` VALUES (8,'smtp_username','','Username for smtp Authenticate');
INSERT INTO `configurations` VALUES (9,'smtp_password','','Password for smtp Authenticate');
INSERT INTO `configurations` VALUES (10,'send_type','sendmail','Notification email send type');
INSERT INTO `configurations` VALUES (11,'smtp_port','25','Smtp server port');
INSERT INTO `configurations` VALUES (12,'unlock_enabled','1','Enable Automated Account Unlock');
INSERT INTO `configurations` VALUES (13,'unlock_duration','300','Automated Account Unlock Duration (In Seconds)');
INSERT INTO `configurations` VALUES (14,'contact_name','','Technical support Contact name');
INSERT INTO `configurations` VALUES (15,'contact_phone','','Technical support Contact phone number');
INSERT INTO `configurations` VALUES (16,'contact_email','','Technical support Contact Email Address');
INSERT INTO `configurations` VALUES (17,'contact_subject','','Technical Support Email Subject Text');
INSERT INTO `configurations` VALUES (18,'use_notification','System use notification','This is a United States Government Computer system. We encourage its use by authorized staff, auditors, and contractors. Activity on this system is subject to monitoring in the course of systems administration and to protect the system from unauthorized use. Users are further advised that they have no expectation of privacy while using this system or in any material on this system. Unauthorized use of this system is a violation of Federal Law and will be punished with fines or imprisonment (P.L. 99-474) Anyone using this system expressly consents to such monitoring and acknowledges that unauthorized use may be reported to the proper authorities.');
INSERT INTO `configurations` VALUES (19,'behavior_rule','Rules Of Behavior','SENSITIVE BUT UNCLASSIFIED INFORMATION PROPERTY OF THE UNITED STATES\r\nGOVERNMENT\r\n\r\nDISCLOSURE, COPYING, DISSEMINATION, OR DISTRIBUTION OF SENSITIVE BUT\r\nUNCLASSIFIED INFORMATION TO UNAUTHORIZED USERS IS PROHIBITED.\r\n\r\nPlease dispose of sensitive but unclassified information when no longer\r\nneeded.\r\n\r\nI. Usage Agreement\r\n\r\nThis is a Federal computer system and is the property of the United States\r\nGovernment. It is for authorized use only. Users (authorized or\r\nunauthorized) have no explicit or implicit expectation of privacy in\r\nanything viewed, created, downloaded, or stored on this system, including\r\ne-mail, Internet, and Intranet use. Any or all uses of this system\r\n(including all peripheral devices and output media) and all files on this\r\nsystem may be intercepted, monitored, read, captured, recorded, disclosed,\r\ncopied, audited, and/or inspected by authorized Agency personnel, the\r\nOffice of Inspector General (OIG),and/or other law enforcement personnel,\r\nas well as authorized officials of other agencies. Access or use of this\r\ncomputer by any person, whether authorized or unauthorized, constitutes\r\nconsent to such interception, monitoring, reading, capturing, recording,\r\ndisclosure, copying, auditing, and/or inspection at the discretion of\r\nauthorized Agency personnel, law enforcement personnel (including the\r\nOIG),and/or authorized officials other agencies. Unauthorized use of this\r\nsystem is prohibited and may constitute a violation of 18 U.S.C. 1030 or\r\nother Federal laws and regulations and may result in criminal, civil,\r\nand/or administrative action. By continuing to use this system, you\r\nindicate your awareness of, and consent to, these terms and conditions and\r\nacknowledge that there is no reasonable expectation of privacy in the\r\naccess or use of this computer system.');
INSERT INTO `configurations` VALUES (5,'sender','','Send Email Address');
INSERT INTO `configurations` VALUES (20,'privacy_policy','Privacy Policy','* This is a U.S. Federal government computer system that is FOR OFFICIAL USE ONLY.\n* This system is subject to monitoring. No expectation of privacy is to be assumed.\n* Individuals found performing unauthorized activities are subject to disciplinary action including criminal prosecution.');
INSERT INTO `configurations` VALUES (21,'system_name','Openfisma','System name');
INSERT INTO `configurations` VALUES (22,'rob_duration','15','the duration between which the user has to accept the ROB.(Day)');
INSERT INTO `configurations` VALUES (26,'pass_special','0','Require Special Characters');
INSERT INTO `configurations` VALUES (25,'pass_numerical','0','Require Numerical Characters');
INSERT INTO `configurations` VALUES (24,'pass_lowercase','1','Require Lower Case Characters');
INSERT INTO `configurations` VALUES (23,'pass_uppercase','1','Require Upper Case Characters');
INSERT INTO `configurations` VALUES (27,'pass_min','8','Minimum Password Length');
INSERT INTO `configurations` VALUES (28,'pass_max','64','Maximum Password Length');
INSERT INTO `configurations` VALUES (29,'pass_expire','90','Password Expiration days');
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `evaluations` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `nickname` varchar(32) NOT NULL,
  `precedence_id` int(10) NOT NULL default '0',
  `function_id` int(10) NOT NULL,
  `event_id` int(10) NOT NULL,
  `group` enum('EVIDENCE','ACTION') NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
INSERT INTO `evaluations` VALUES (1,'Mitigation Strategy Provided to SSO','MP_SSO',0,24,15,'ACTION');
INSERT INTO `evaluations` VALUES (2,'Mitigation Strategy Provided to IVV','MP_IVV',1,92,93,'ACTION');
INSERT INTO `evaluations` VALUES (3,'Evidence Provided to SSO','EP_SSO',0,25,18,'EVIDENCE');
INSERT INTO `evaluations` VALUES (4,'Evidence Provided to SP','EP_SP',1,26,19,'EVIDENCE');
INSERT INTO `evaluations` VALUES (5,'Evidence Provided to IVV','EP_IVV',2,27,20,'EVIDENCE');
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `events` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(64) NOT NULL,
  `function_id` int(10) NOT NULL,
  PRIMARY KEY  (`id`,`name`)
) ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
INSERT INTO `events` VALUES (1,'Finding Created',91);
INSERT INTO `events` VALUES (2,'Finding Import',91);
INSERT INTO `events` VALUES (3,'Finding Inject',91);
INSERT INTO `events` VALUES (4,'Asset Modified',90);
INSERT INTO `events` VALUES (5,'Asset Created',90);
INSERT INTO `events` VALUES (6,'Asset Deleted',90);
INSERT INTO `events` VALUES (7,'Update Course of Action',91);
INSERT INTO `events` VALUES (8,'Update Finding Assignment',91);
INSERT INTO `events` VALUES (9,'Update Control Assignment',91);
INSERT INTO `events` VALUES (10,'Update Countermeasures',91);
INSERT INTO `events` VALUES (11,'Update Threat',91);
INSERT INTO `events` VALUES (12,'Update Finding Recommendation',91);
INSERT INTO `events` VALUES (13,'Update Finding Resources',91);
INSERT INTO `events` VALUES (14,'Update Est Completion Date',91);
INSERT INTO `events` VALUES (15,'Mitigation Strategy Approved TO SSO',91);
INSERT INTO `events` VALUES (16,'POA&M Closed',91);
INSERT INTO `events` VALUES (17,'Evidence Upload',91);
INSERT INTO `events` VALUES (18,'Evidence Submitted for 1st Approval',91);
INSERT INTO `events` VALUES (19,'Evidence Submitted for 2nd Approval',91);
INSERT INTO `events` VALUES (20,'Evidence Submitted for 3rd Approval',91);
INSERT INTO `events` VALUES (21,'Account Modified',89);
INSERT INTO `events` VALUES (22,'Account Deleted',89);
INSERT INTO `events` VALUES (23,'Account Created',89);
INSERT INTO `events` VALUES (24,'System Groups Deleted',89);
INSERT INTO `events` VALUES (25,'System Groups Modified ',89);
INSERT INTO `events` VALUES (26,'System Groups Created',89);
INSERT INTO `events` VALUES (27,'System Deleted',89);
INSERT INTO `events` VALUES (28,'System Modified',89);
INSERT INTO `events` VALUES (29,'System Created',89);
INSERT INTO `events` VALUES (30,'Product Created',89);
INSERT INTO `events` VALUES (31,'Product Modified',89);
INSERT INTO `events` VALUES (32,'Product Deleted',89);
INSERT INTO `events` VALUES (33,'Role Created',89);
INSERT INTO `events` VALUES (34,'Role Deleted',89);
INSERT INTO `events` VALUES (35,'Role Modified',89);
INSERT INTO `events` VALUES (36,'Finding Source Created',89);
INSERT INTO `events` VALUES (37,'Finding Source Modified',89);
INSERT INTO `events` VALUES (38,'Finding Source Deleted',89);
INSERT INTO `events` VALUES (39,'Network Modified',89);
INSERT INTO `events` VALUES (40,'Network Created',89);
INSERT INTO `events` VALUES (41,'Network Deleted',89);
INSERT INTO `events` VALUES (42,'System Configuration Modified',89);
INSERT INTO `events` VALUES (43,'Account Login Success',89);
INSERT INTO `events` VALUES (44,'Account Login Failure',89);
INSERT INTO `events` VALUES (45,'Account Logout',89);
INSERT INTO `events` VALUES (49,'ECD Expires in 21 days',91);
INSERT INTO `events` VALUES (48,'ECD Expires in 14 days',91);
INSERT INTO `events` VALUES (47,'ECD Expires in 7 Days',91);
INSERT INTO `events` VALUES (46,'ECD Expires Today',91);
INSERT INTO `events` VALUES (51,'Evidence Denied',91);
INSERT INTO `events` VALUES (52,'Account Locked',89);
INSERT INTO `events` VALUES (53,'Mitigation Strategy Approved to IVV',91);
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `evidences` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `poam_id` int(10) unsigned NOT NULL default '0',
  `submission` varchar(128) NOT NULL default '',
  `submitted_by` int(10) unsigned NOT NULL default '0',
  `submit_ts` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`),
  KEY `poam_id` (`poam_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `functions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `screen` varchar(64) NOT NULL default '',
  `action` varchar(64) NOT NULL default '',
  `desc` text NOT NULL,
  `open` char(1) default '1',
  PRIMARY KEY  (`id`),
  KEY `function_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
INSERT INTO `functions` VALUES (1,'View Dashboard','dashboard','read','','1');
INSERT INTO `functions` VALUES (2,'View Findings','finding','read','','1');
INSERT INTO `functions` VALUES (3,'Edit Findings','finding','update','','1');
INSERT INTO `functions` VALUES (4,'Create Findings','finding','create','','1');
INSERT INTO `functions` VALUES (5,'Delete Findings','finding','delete','','1');
INSERT INTO `functions` VALUES (6,'View Assets','asset','read','','1');
INSERT INTO `functions` VALUES (7,'Edit Assets','asset','update','','1');
INSERT INTO `functions` VALUES (8,'Create Assets','asset','create','','1');
INSERT INTO `functions` VALUES (9,'Delete Assets','asset','delete','','1');
INSERT INTO `functions` VALUES (10,'View Remediation','remediation','read','','1');
INSERT INTO `functions` VALUES (11,'Inject Findings','remediation','create_injection','','1');
INSERT INTO `functions` VALUES (12,'Edit Findings','remediation','update_finding','','1');
INSERT INTO `functions` VALUES (13,'Delete Remediation','remediation','delete','','1');
INSERT INTO `functions` VALUES (14,'Edit Course of Action','remediation','update_course_of_action','','1');
INSERT INTO `functions` VALUES (15,'Edit Responsible System','remediation','update_finding_assignment','','1');
INSERT INTO `functions` VALUES (16,'Edit 800-53 Control Assignment','remediation','update_control_assignment','','1');
INSERT INTO `functions` VALUES (17,'Edit Countermeasures','remediation','update_countermeasures','','1');
INSERT INTO `functions` VALUES (18,'Edit Threat Sources','remediation','update_threat','','1');
INSERT INTO `functions` VALUES (19,'Edit Finding Recommendation','remediation','update_finding_recommendation','','1');
INSERT INTO `functions` VALUES (20,'Edit Finding Resources','remediation','update_finding_resources','','1');
INSERT INTO `functions` VALUES (21,'Edit Completion Date','remediation','update_est_completion_date','','1');
INSERT INTO `functions` VALUES (22,'View Evidence','remediation','read_evidence','','1');
INSERT INTO `functions` VALUES (23,'Update Evidence','remediation','update_evidence','','1');
INSERT INTO `functions` VALUES (24,'Mitigation Strategy Provided to SSO','remediation','update_mitigation_strategy_approval_1','','1');
INSERT INTO `functions` VALUES (25,'Approve Evidence Level 1','remediation','update_evidence_approval_first','','1');
INSERT INTO `functions` VALUES (26,'Approve Evidence Level 2','remediation','update_evidence_approval_second','','1');
INSERT INTO `functions` VALUES (27,'Approve Evidence Level 3','remediation','update_evidence_approval_third','','1');
INSERT INTO `functions` VALUES (28,'Accept Risk Level 1','remediation','update_risk_first','','1');
INSERT INTO `functions` VALUES (29,'Accept Risk Level 2','remediation','update_risk_second','','1');
INSERT INTO `functions` VALUES (30,'Accept Risk Level 3','remediation','update_risk_third','','1');
INSERT INTO `functions` VALUES (31,'View Reports','report','read','','1');
INSERT INTO `functions` VALUES (32,'Generate POA&M Reports','report','generate_poam_report','','1');
INSERT INTO `functions` VALUES (33,'Generate FISMA Reports','report','generate_fisma_report','','1');
INSERT INTO `functions` VALUES (34,'Generate General Reports','report','generate_general_report','','1');
INSERT INTO `functions` VALUES (35,'View Vulnerabilities','vulnerability','read','','1');
INSERT INTO `functions` VALUES (36,'Edit Vulnerabilities','vulnerability','update','','1');
INSERT INTO `functions` VALUES (37,'Create Vulnerabilities','vulnerability','create','','1');
INSERT INTO `functions` VALUES (38,'Delete Vulnerabilities','vulnerability','delete','','1');
INSERT INTO `functions` VALUES (39,'Generate System RAFS','report','generate_system_rafs','','1');
INSERT INTO `functions` VALUES (40,'Generate Overdue Reports','report','generate_overdue_report','','1');
INSERT INTO `functions` VALUES (41,'Edit Course Of Action','remediation','update_finding_course_of_action','','1');
INSERT INTO `functions` VALUES (42,'View Users','admin_users','read','','1');
INSERT INTO `functions` VALUES (43,'Edit Users','admin_users','update','','1');
INSERT INTO `functions` VALUES (44,'Delete Users','admin_users','delete','','1');
INSERT INTO `functions` VALUES (45,'Create Users','admin_users','create','','1');
INSERT INTO `functions` VALUES (58,'View Organizations','admin_organizations','read','','1');
INSERT INTO `functions` VALUES (59,'Delete Organizations','admin_organizations','delete','','1');
INSERT INTO `functions` VALUES (60,'Edit Organizations','admin_organizations','update','','1');
INSERT INTO `functions` VALUES (61,'Create Organizations','admin_organizations','create','','1');
INSERT INTO `functions` VALUES (66,'View Systems','admin_systems','read','','1');
INSERT INTO `functions` VALUES (67,'Delete Systems','admin_systems','delete','','1');
INSERT INTO `functions` VALUES (68,'Edit Systems','admin_systems','update','','1');
INSERT INTO `functions` VALUES (69,'Create Systems','admin_systems','create','','1');
INSERT INTO `functions` VALUES (70,'View Administration Menu','admin','read','','1');
INSERT INTO `functions` VALUES (71,'View Products','admin_products','read','','1');
INSERT INTO `functions` VALUES (72,'Create Products','admin_products','create','','1');
INSERT INTO `functions` VALUES (73,'Edit Products','admin_products','update','','1');
INSERT INTO `functions` VALUES (74,'Delete Products','admin_products','delete','','1');
INSERT INTO `functions` VALUES (75,'Edit Finding Sources','admin_sources','update','','1');
INSERT INTO `functions` VALUES (76,'Delete Finding Sources','admin_sources','delete','','1');
INSERT INTO `functions` VALUES (77,'View Finding Sources','admin_sources','read','','1');
INSERT INTO `functions` VALUES (78,'Create Finding Sources','admin_sources','create','','1');
INSERT INTO `functions` VALUES (79,'App Configuration','app_configuration','update','','1');
INSERT INTO `functions` VALUES (87,'Create Networks','admin_networks','create','','1');
INSERT INTO `functions` VALUES (86,'Edit Networks','admin_networks','update','','1');
INSERT INTO `functions` VALUES (85,'View Networks','admin_networks','read','','1');
INSERT INTO `functions` VALUES (84,'Define Roles','admin_roles','definition','','1');
INSERT INTO `functions` VALUES (83,'Delete Roles','admin_roles','delete','','1');
INSERT INTO `functions` VALUES (82,'Create Roles','admin_roles','create','','1');
INSERT INTO `functions` VALUES (81,'Edit Roles','admin_roles','update','','1');
INSERT INTO `functions` VALUES (80,'View Roles','admin_roles','read','','1');
INSERT INTO `functions` VALUES (88,'Delete Networks','admin_networks','delete','','1');
INSERT INTO `functions` VALUES (91,'Remediation Notifications','notification','remediation','','1');
INSERT INTO `functions` VALUES (90,'Asset Notifications','notification','asset','','1');
INSERT INTO `functions` VALUES (89,'Admin Notifications','notification','admin','','1');
INSERT INTO `functions` VALUES (92,'Mitigation Strategy Provided to IVV','remediation','update_mitigation_strategy_approval_2','','1');
INSERT INTO `functions` VALUES (93,'Mitigation Strategy submit','remediation','mitigation_strategy_submit','','1');
INSERT INTO `functions` VALUES (94,'Mitigation Strategy revise','remediation','mitigation_strategy_revise','','1');
INSERT INTO `functions` VALUES (95,'Approve Injected Findings','finding','approve','','1');
INSERT INTO `functions` VALUES (96,'Inject Findings','finding','inject','','1');
INSERT INTO `functions` VALUES (97,'Edit Finding Description','remediation','update_finding_description','','1');
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `ldap_config` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `host` varchar(64) NOT NULL,
  `port` int(16) default NULL,
  `domain_name` varchar(64) NOT NULL,
  `domain_short` varchar(64) default NULL,
  `username` varchar(64) default NULL,
  `password` varchar(64) default NULL,
  `basedn` varchar(64) default NULL,
  `account_filter` varchar(64) default NULL,
  `account_canonical` varchar(64) default NULL,
  `bind_requires_dn` varchar(64) default NULL,
  `use_ssl` tinyint(1) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `networks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `nickname` varchar(8) NOT NULL default '',
  `desc` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `nickname` (`nickname`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `notifications` (
  `id` int(10) NOT NULL auto_increment,
  `event_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `event_text` text NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `organizations` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `nickname` varchar(8) NOT NULL default '',
  `father` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `plugins` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `class` varchar(256) NOT NULL,
  `desc` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
INSERT INTO `plugins` VALUES (1,'AppDetective Security Scanner','Inject_AppDetective','AppDetective application security assessment tool plugin');
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `poam_evaluations` (
  `id` int(10) NOT NULL auto_increment,
  `group_id` int(10) NOT NULL,
  `eval_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `decision` enum('APPROVED','DENIED','EST_CHANGED') default NULL,
  `date` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `poam_vulns` (
  `poam_id` int(10) unsigned NOT NULL default '0',
  `vuln_seq` int(10) unsigned NOT NULL default '0',
  `vuln_type` char(3) NOT NULL default '',
  PRIMARY KEY  (`poam_id`,`vuln_seq`,`vuln_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `poams` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `legacy_finding_id` int(10) unsigned NOT NULL default '0',
  `asset_id` int(10) unsigned NOT NULL default '0',
  `source_id` int(10) unsigned NOT NULL default '0',
  `system_id` int(10) unsigned NOT NULL default '0',
  `blscr_id` varchar(5) default NULL,
  `create_ts` date NOT NULL,
  `discover_ts` date NOT NULL,
  `modify_ts` date NOT NULL,
  `mss_ts` datetime NOT NULL default '0000-00-00 00:00:00',
  `close_ts` date NOT NULL,
  `type` enum('NONE','CAP','FP','AR') NOT NULL default 'NONE',
  `status` enum('PEND','NEW','DRAFT','MSA','EN','EA','CLOSED','DELETED') NOT NULL default 'NEW',
  `is_repeat` tinyint(1) default NULL,
  `finding_data` text NOT NULL,
  `previous_audits` text,
  `created_by` int(10) unsigned NOT NULL default '0',
  `modified_by` int(10) unsigned default '0',
  `closed_by` int(10) unsigned default '0',
  `action_suggested` text,
  `action_planned` text,
  `action_status` enum('NONE','APPROVED','DENIED') NOT NULL default 'NONE',
  `action_approved_by` int(10) unsigned default NULL,
  `action_resources` text,
  `action_est_date` date default NULL,
  `action_current_date` date default NULL,
  `ecd_justification` text,
  `action_actual_date` date default NULL,
  `cmeasure` text,
  `cmeasure_effectiveness` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `cmeasure_justification` text,
  `threat_source` text,
  `threat_level` enum('NONE','LOW','MODERATE','HIGH') NOT NULL default 'NONE',
  `threat_justification` text,
  `duplicate_poam_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `nvd_defined` tinyint(1) NOT NULL default '0',
  `meta` text,
  `vendor` varchar(64) NOT NULL default '',
  `name` varchar(64) NOT NULL default '',
  `version` varchar(32) NOT NULL default '',
  `desc` text,
  `cpe_name` varchar(256) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `cpe_name` (`cpe_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `role_functions` (
  `role_id` int(10) unsigned NOT NULL default '0',
  `function_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`role_id`,`function_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
INSERT INTO `role_functions` VALUES (3,1);
INSERT INTO `role_functions` VALUES (3,2);
INSERT INTO `role_functions` VALUES (3,3);
INSERT INTO `role_functions` VALUES (3,4);
INSERT INTO `role_functions` VALUES (3,6);
INSERT INTO `role_functions` VALUES (3,7);
INSERT INTO `role_functions` VALUES (3,8);
INSERT INTO `role_functions` VALUES (3,10);
INSERT INTO `role_functions` VALUES (3,11);
INSERT INTO `role_functions` VALUES (3,12);
INSERT INTO `role_functions` VALUES (3,14);
INSERT INTO `role_functions` VALUES (3,16);
INSERT INTO `role_functions` VALUES (3,17);
INSERT INTO `role_functions` VALUES (3,18);
INSERT INTO `role_functions` VALUES (3,20);
INSERT INTO `role_functions` VALUES (3,21);
INSERT INTO `role_functions` VALUES (3,22);
INSERT INTO `role_functions` VALUES (3,23);
INSERT INTO `role_functions` VALUES (3,24);
INSERT INTO `role_functions` VALUES (3,25);
INSERT INTO `role_functions` VALUES (3,28);
INSERT INTO `role_functions` VALUES (3,31);
INSERT INTO `role_functions` VALUES (3,32);
INSERT INTO `role_functions` VALUES (3,34);
INSERT INTO `role_functions` VALUES (3,35);
INSERT INTO `role_functions` VALUES (3,36);
INSERT INTO `role_functions` VALUES (3,37);
INSERT INTO `role_functions` VALUES (3,39);
INSERT INTO `role_functions` VALUES (3,40);
INSERT INTO `role_functions` VALUES (3,41);
INSERT INTO `role_functions` VALUES (3,70);
INSERT INTO `role_functions` VALUES (3,71);
INSERT INTO `role_functions` VALUES (3,72);
INSERT INTO `role_functions` VALUES (3,73);
INSERT INTO `role_functions` VALUES (3,77);
INSERT INTO `role_functions` VALUES (5,1);
INSERT INTO `role_functions` VALUES (5,2);
INSERT INTO `role_functions` VALUES (5,6);
INSERT INTO `role_functions` VALUES (5,10);
INSERT INTO `role_functions` VALUES (5,22);
INSERT INTO `role_functions` VALUES (5,31);
INSERT INTO `role_functions` VALUES (5,32);
INSERT INTO `role_functions` VALUES (5,35);
INSERT INTO `role_functions` VALUES (5,39);
INSERT INTO `role_functions` VALUES (5,40);
INSERT INTO `role_functions` VALUES (5,70);
INSERT INTO `role_functions` VALUES (5,71);
INSERT INTO `role_functions` VALUES (5,77);
INSERT INTO `role_functions` VALUES (7,1);
INSERT INTO `role_functions` VALUES (7,2);
INSERT INTO `role_functions` VALUES (7,6);
INSERT INTO `role_functions` VALUES (7,10);
INSERT INTO `role_functions` VALUES (7,22);
INSERT INTO `role_functions` VALUES (7,26);
INSERT INTO `role_functions` VALUES (7,29);
INSERT INTO `role_functions` VALUES (7,31);
INSERT INTO `role_functions` VALUES (7,32);
INSERT INTO `role_functions` VALUES (7,34);
INSERT INTO `role_functions` VALUES (7,35);
INSERT INTO `role_functions` VALUES (7,39);
INSERT INTO `role_functions` VALUES (7,40);
INSERT INTO `role_functions` VALUES (7,58);
INSERT INTO `role_functions` VALUES (7,66);
INSERT INTO `role_functions` VALUES (7,70);
INSERT INTO `role_functions` VALUES (7,71);
INSERT INTO `role_functions` VALUES (7,77);
INSERT INTO `role_functions` VALUES (7,95);
INSERT INTO `role_functions` VALUES (8,1);
INSERT INTO `role_functions` VALUES (8,2);
INSERT INTO `role_functions` VALUES (8,6);
INSERT INTO `role_functions` VALUES (8,10);
INSERT INTO `role_functions` VALUES (8,22);
INSERT INTO `role_functions` VALUES (8,27);
INSERT INTO `role_functions` VALUES (8,31);
INSERT INTO `role_functions` VALUES (8,32);
INSERT INTO `role_functions` VALUES (8,33);
INSERT INTO `role_functions` VALUES (8,34);
INSERT INTO `role_functions` VALUES (8,35);
INSERT INTO `role_functions` VALUES (8,39);
INSERT INTO `role_functions` VALUES (8,40);
INSERT INTO `role_functions` VALUES (8,95);
INSERT INTO `role_functions` VALUES (10,1);
INSERT INTO `role_functions` VALUES (10,2);
INSERT INTO `role_functions` VALUES (10,3);
INSERT INTO `role_functions` VALUES (10,4);
INSERT INTO `role_functions` VALUES (10,5);
INSERT INTO `role_functions` VALUES (10,6);
INSERT INTO `role_functions` VALUES (10,7);
INSERT INTO `role_functions` VALUES (10,8);
INSERT INTO `role_functions` VALUES (10,9);
INSERT INTO `role_functions` VALUES (10,10);
INSERT INTO `role_functions` VALUES (10,11);
INSERT INTO `role_functions` VALUES (10,12);
INSERT INTO `role_functions` VALUES (10,13);
INSERT INTO `role_functions` VALUES (10,22);
INSERT INTO `role_functions` VALUES (10,31);
INSERT INTO `role_functions` VALUES (10,32);
INSERT INTO `role_functions` VALUES (10,33);
INSERT INTO `role_functions` VALUES (10,34);
INSERT INTO `role_functions` VALUES (10,35);
INSERT INTO `role_functions` VALUES (10,36);
INSERT INTO `role_functions` VALUES (10,37);
INSERT INTO `role_functions` VALUES (10,38);
INSERT INTO `role_functions` VALUES (10,39);
INSERT INTO `role_functions` VALUES (10,40);
INSERT INTO `role_functions` VALUES (10,42);
INSERT INTO `role_functions` VALUES (10,43);
INSERT INTO `role_functions` VALUES (10,44);
INSERT INTO `role_functions` VALUES (10,45);
INSERT INTO `role_functions` VALUES (10,58);
INSERT INTO `role_functions` VALUES (10,59);
INSERT INTO `role_functions` VALUES (10,60);
INSERT INTO `role_functions` VALUES (10,61);
INSERT INTO `role_functions` VALUES (10,66);
INSERT INTO `role_functions` VALUES (10,67);
INSERT INTO `role_functions` VALUES (10,68);
INSERT INTO `role_functions` VALUES (10,69);
INSERT INTO `role_functions` VALUES (10,70);
INSERT INTO `role_functions` VALUES (10,71);
INSERT INTO `role_functions` VALUES (10,72);
INSERT INTO `role_functions` VALUES (10,73);
INSERT INTO `role_functions` VALUES (10,74);
INSERT INTO `role_functions` VALUES (10,75);
INSERT INTO `role_functions` VALUES (10,76);
INSERT INTO `role_functions` VALUES (10,77);
INSERT INTO `role_functions` VALUES (10,78);
INSERT INTO `role_functions` VALUES (10,79);
INSERT INTO `role_functions` VALUES (10,80);
INSERT INTO `role_functions` VALUES (10,81);
INSERT INTO `role_functions` VALUES (10,82);
INSERT INTO `role_functions` VALUES (10,83);
INSERT INTO `role_functions` VALUES (10,84);
INSERT INTO `role_functions` VALUES (10,85);
INSERT INTO `role_functions` VALUES (10,86);
INSERT INTO `role_functions` VALUES (10,87);
INSERT INTO `role_functions` VALUES (10,88);
INSERT INTO `role_functions` VALUES (10,95);
INSERT INTO `role_functions` VALUES (11,1);
INSERT INTO `role_functions` VALUES (11,2);
INSERT INTO `role_functions` VALUES (11,3);
INSERT INTO `role_functions` VALUES (11,4);
INSERT INTO `role_functions` VALUES (11,6);
INSERT INTO `role_functions` VALUES (11,7);
INSERT INTO `role_functions` VALUES (11,8);
INSERT INTO `role_functions` VALUES (11,10);
INSERT INTO `role_functions` VALUES (11,11);
INSERT INTO `role_functions` VALUES (11,12);
INSERT INTO `role_functions` VALUES (11,15);
INSERT INTO `role_functions` VALUES (11,16);
INSERT INTO `role_functions` VALUES (11,17);
INSERT INTO `role_functions` VALUES (11,18);
INSERT INTO `role_functions` VALUES (11,19);
INSERT INTO `role_functions` VALUES (11,22);
INSERT INTO `role_functions` VALUES (11,31);
INSERT INTO `role_functions` VALUES (11,32);
INSERT INTO `role_functions` VALUES (11,35);
INSERT INTO `role_functions` VALUES (11,36);
INSERT INTO `role_functions` VALUES (11,37);
INSERT INTO `role_functions` VALUES (11,39);
INSERT INTO `role_functions` VALUES (11,40);
INSERT INTO `role_functions` VALUES (11,70);
INSERT INTO `role_functions` VALUES (11,71);
INSERT INTO `role_functions` VALUES (11,72);
INSERT INTO `role_functions` VALUES (11,73);
INSERT INTO `role_functions` VALUES (13,1);
INSERT INTO `role_functions` VALUES (13,2);
INSERT INTO `role_functions` VALUES (13,3);
INSERT INTO `role_functions` VALUES (13,4);
INSERT INTO `role_functions` VALUES (13,6);
INSERT INTO `role_functions` VALUES (13,7);
INSERT INTO `role_functions` VALUES (13,8);
INSERT INTO `role_functions` VALUES (13,10);
INSERT INTO `role_functions` VALUES (13,12);
INSERT INTO `role_functions` VALUES (13,14);
INSERT INTO `role_functions` VALUES (13,16);
INSERT INTO `role_functions` VALUES (13,17);
INSERT INTO `role_functions` VALUES (13,18);
INSERT INTO `role_functions` VALUES (13,20);
INSERT INTO `role_functions` VALUES (13,21);
INSERT INTO `role_functions` VALUES (13,22);
INSERT INTO `role_functions` VALUES (13,30);
INSERT INTO `role_functions` VALUES (13,31);
INSERT INTO `role_functions` VALUES (13,32);
INSERT INTO `role_functions` VALUES (13,34);
INSERT INTO `role_functions` VALUES (13,35);
INSERT INTO `role_functions` VALUES (13,36);
INSERT INTO `role_functions` VALUES (13,37);
INSERT INTO `role_functions` VALUES (13,39);
INSERT INTO `role_functions` VALUES (13,40);
INSERT INTO `role_functions` VALUES (13,41);
INSERT INTO `role_functions` VALUES (13,70);
INSERT INTO `role_functions` VALUES (13,71);
INSERT INTO `role_functions` VALUES (13,72);
INSERT INTO `role_functions` VALUES (13,73);
INSERT INTO `role_functions` VALUES (13,77);
INSERT INTO `role_functions` VALUES (14,1);
INSERT INTO `role_functions` VALUES (14,6);
INSERT INTO `role_functions` VALUES (14,10);
INSERT INTO `role_functions` VALUES (14,22);
INSERT INTO `role_functions` VALUES (14,31);
INSERT INTO `role_functions` VALUES (14,32);
INSERT INTO `role_functions` VALUES (14,34);
INSERT INTO `role_functions` VALUES (14,35);
INSERT INTO `role_functions` VALUES (14,39);
INSERT INTO `role_functions` VALUES (14,40);
INSERT INTO `role_functions` VALUES (15,1);
INSERT INTO `role_functions` VALUES (15,2);
INSERT INTO `role_functions` VALUES (15,3);
INSERT INTO `role_functions` VALUES (15,6);
INSERT INTO `role_functions` VALUES (15,7);
INSERT INTO `role_functions` VALUES (15,8);
INSERT INTO `role_functions` VALUES (15,10);
INSERT INTO `role_functions` VALUES (15,12);
INSERT INTO `role_functions` VALUES (15,14);
INSERT INTO `role_functions` VALUES (15,16);
INSERT INTO `role_functions` VALUES (15,17);
INSERT INTO `role_functions` VALUES (15,18);
INSERT INTO `role_functions` VALUES (15,19);
INSERT INTO `role_functions` VALUES (15,20);
INSERT INTO `role_functions` VALUES (15,21);
INSERT INTO `role_functions` VALUES (15,22);
INSERT INTO `role_functions` VALUES (15,23);
INSERT INTO `role_functions` VALUES (15,31);
INSERT INTO `role_functions` VALUES (15,32);
INSERT INTO `role_functions` VALUES (15,35);
INSERT INTO `role_functions` VALUES (15,36);
INSERT INTO `role_functions` VALUES (15,37);
INSERT INTO `role_functions` VALUES (15,39);
INSERT INTO `role_functions` VALUES (15,40);
INSERT INTO `role_functions` VALUES (15,41);
INSERT INTO `role_functions` VALUES (15,70);
INSERT INTO `role_functions` VALUES (15,71);
INSERT INTO `role_functions` VALUES (15,72);
INSERT INTO `role_functions` VALUES (15,73);
INSERT INTO `role_functions` VALUES (15,77);
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `nickname` varchar(16) default NULL,
  `desc` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
INSERT INTO `roles` VALUES (3,'Information System Security Officer','ISSO','[NIST Special Publication 800-37 Definition] \nThe information system security officer is the individual responsible to the authorizing official, information system owner, or the senior agency information security officer for ensuring the appropriate operational security posture is maintained for an information system or program. The information system security officer also serves as the principal advisor to the authorizing official, information system owner, or senior agency information security officer on all matters (technical and otherwise) involving the security of the information system. The information system security officer typically has the detailed knowledge and expertise required to manage the security aspects of the information system and, in many agencies, is assigned responsibility for the day-to-day security operations of the system. This responsibility may also include, but is not limited to, physical security, personnel security, incident handling, and security training and awareness. The information system security officer may be called upon to assist in the development of the system security policy and to ensure compliance with that policy on a routine basis. In close coordination with the information system owner, the information system security officer often plays an active role in developing and updating the system security plan as well as in managing and controlling changes to the system and assessing the security impact of those changes. \n\n[OpenFISMA definition] \nThe Information Systems Security Officer Group is designed to allow Information System Security Officers the ability to identify, assess, prioritize, and monitor the progress of corrective efforts for security weaknesses found in thier system.');
INSERT INTO `roles` VALUES (5,'Reviewer','REVIEWER','[OpenFISMA Definition] \nThe Reviewer Group gives users the ability to view all information on the Plan of Actions and Milestones report for the information system. They do not have the ability to create, edit, or delete any information.');
INSERT INTO `roles` VALUES (7,'Independent Verification and Validation','IV&V','[OpenFISMA Definition] \nThe Independent Verification and Validation group serves as a third party independent of the correction process who validates whether or not the security weakness has been successfully closed. They have the ability to view all agency Plan of Actions and Milestone information and the ability to close an open item.');
INSERT INTO `roles` VALUES (8,'Senior Agency Information Security Officer','SAISO','[NIST 800-37 Definition] \nThe senior agency information security officer is the agency official responsible for: \n\n(i) carrying out the Chief Information Officer responsibilities under FISMA; \n(ii) possessing professional qualifications, including training and experience, required to administer the information security program functions; \n(iii) having information security duties as that official\'s primary duty; and \n(iv) heading an office with the mission and resources to assist in ensuring agency compliance with FISMA. The senior agency information security officer (or supporting staff member) may also serve as the authorizing officials designated representative. The senior agency information security officer serves as the Chief Information Officer\'s primary liaison to the agency\'s authorizing officials, information system owners, and information system security officers. \n\n[OpenFISMA Definition] \nThe SAISO has the ability to view all agency wide Plan of Actions and Milestone information as well as generate reports and provide comments and guidance to the Information System Security Officers through the remediation page.');
INSERT INTO `roles` VALUES (10,'Application Administrator','ADMIN','[OpenFISMA Definition] \nThe Application Administrators group provides administrative privileges to the OpenFISMA application. Users will have the ability to access all security controls and functions.');
INSERT INTO `roles` VALUES (11,'Certification Agent','CERTAGENT','[NIST 800-37 Definition] \nThe certification agent is an individual, group, or organization responsible for conducting a security certification, or comprehensive assessment of the management, operational, and technical security controls in an information system to determine the extent to which the controls are implemented correctly, operating as intended, and producing the desired outcome with respect to meeting the security requirements for the system. The certification agent also provides recommended corrective actions to reduce or eliminate vulnerabilities in the information system. Prior to initiating the security assessment activities that are a part of the certification process, the certification agent provides an independent assessment of the system security plan to ensure the plan provides a set of security controls for the information system that is adequate to meet all applicable security requirements. \n\n[OpenFISMA Definition] \nThis group is used by independent auditors to view finding information, create findings, set initial risk levels, and provide recommended corrective actions.');
INSERT INTO `roles` VALUES (13,'Information System Owner','ISO','[NIST 800-37 Definition] \nThe information system owner is an agency official responsible for the overall procurement, development, integration, modification, or operation and maintenance of an information system. The information system owner is responsible for the development and maintenance of the system security plan and ensures the system is deployed and operated according to the agreed-upon security requirements. The information system owner is also responsible for deciding who has access to the information system (and with what types of privileges or access rights) and ensures that system users and support personnel receive the requisite security training (e.g., instruction in rules of behavior). The information system owner informs key agency officials of the need to conduct a security certification and accreditation of the information system, ensures that appropriate resources are available for the effort, and provides the necessary system-related documentation to the certification agent. The information system owner receives the security assessment results from the certification agent. After taking appropriate steps to reduce or eliminate vulnerabilities, the information system owner assembles the security accreditation package and submits the package to the authorizing official or the authorizing officials designated representative for adjudication. \n\nThe role of information system owner can be interpreted in a variety of ways depending on the particular agency and the system development life cycle phase of the information system. Some agencies may refer to information system owners as program managers or business/asset/mission owners. In some situations, the notification of the need to conduct a security certification and accreditation may come from the senior agency information security officer or authorizing official as they endeavor to ensure compliance with federal or agency policy. The responsibility for ensuring appropriate resources are allocated to the security certification and accreditation effort depends on whether the agency uses a centralized or decentralized funding mechanism. Depending on how the agency has organized and structured its security certification and accreditation activities, the authorizing official may choose to designate an individual other than the information system owner to compile and assemble the information for the accreditation package. In this situation, the designated individual must coordinate the compilation and assembly activities with the information system owner.');
INSERT INTO `roles` VALUES (14,'Authorizing Official','AO','The authorizing official (or designated approving/accrediting authority as referred to by some agencies) is a senior management official or executive with the authority to formally assume responsibility for operating an information system at an acceptable level of risk to agency operations, agency assets, or individuals. \n\nThe authorizing official should have the authority to oversee the budget and business operations of the information system within the agency and is often called upon to approve system security requirements, system security plans, and memorandums of agreement and/or memorandums of understanding. \n\nThe AO issues a formal approval to operate and information system, an interim authorization to operate the information system under specific terms and conditions; or deny authorization to operate the information system (or if the system is already operational, halt operations) if unacceptable security risks exist.');
INSERT INTO `roles` VALUES (15,'Operational Support Staff','OSS','[OpenFISMA Definition] \nOperational Support StaffOperational support staff has the ability to view POA&M information and participates in the POA&M process in any or all of the following ways: \n\n* Submitting course of action, mitigation strategy and/or expected completion date for the approval of the SSO. \n* Submitting threats and countermeasures information for the approval of the SSO. \n* Submitting evidence packages for the approval of the SSO.');
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `schema_version` (
  `schema_version` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`schema_version`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
INSERT INTO `schema_version` VALUES (45);
CREATE TABLE `sources` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL,
  `nickname` varchar(16) NOT NULL,
  `desc` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `nickname` (`nickname`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `systems` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `nickname` varchar(8) NOT NULL default '',
  `organization_id` int(10) NOT NULL,
  `desc` text,
  `type` enum('GENERAL SUPPORT SYSTEM','MINOR APPLICATION','MAJOR APPLICATION') default NULL,
  `confidentiality` enum('NA','LOW','MODERATE','HIGH') default NULL,
  `integrity` enum('LOW','MODERATE','HIGH') default NULL,
  `availability` enum('LOW','MODERATE','HIGH') default NULL,
  `tier` int(10) unsigned NOT NULL default '0',
  `confidentiality_justification` text NOT NULL,
  `integrity_justification` text NOT NULL,
  `availability_justification` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `nickname` (`nickname`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_events` (
  `user_id` int(10) NOT NULL,
  `event_id` int(10) NOT NULL,
  PRIMARY KEY  (`user_id`,`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_roles` (
  `user_id` int(10) NOT NULL,
  `role_id` int(10) NOT NULL,
  PRIMARY KEY  (`user_id`,`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `user_systems` (
  `user_id` int(10) NOT NULL,
  `system_id` int(10) NOT NULL,
  PRIMARY KEY  (`user_id`,`system_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `account` varchar(32) NOT NULL,
  `password` varchar(256) NOT NULL default '',
  `hash` enum('md5','sha1','sha256') NOT NULL default 'sha1',
  `title` varchar(64) default NULL,
  `name_last` varchar(32) NOT NULL default '',
  `name_middle` char(1) default NULL,
  `name_first` varchar(32) NOT NULL default '',
  `created_ts` datetime NOT NULL default '0000-00-00 00:00:00',
  `password_ts` datetime NOT NULL default '0000-00-00 00:00:00',
  `history_password` text,
  `last_login_ts` datetime NOT NULL default '0000-00-00 00:00:00',
  `last_login_ip` varchar(32) NOT NULL,
  `termination_ts` datetime default NULL,
  `is_active` tinyint(1) NOT NULL default '0',
  `failure_count` int(2) unsigned default '0',
  `phone_office` varchar(12) NOT NULL,
  `phone_mobile` varchar(12) default NULL,
  `email` varchar(64) NOT NULL default '',
  `email_validate` tinyint(1) NOT NULL default '1',
  `auto_role` varchar(20) NOT NULL,
  `notify_frequency` float NOT NULL default '12',
  `most_recent_notify_ts` datetime NOT NULL,
  `ldap_dn` varchar(64) NOT NULL,
  `notify_email` varchar(64) NOT NULL,
  `last_rob` datetime NOT NULL,
  `column_habit` varchar(17) NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `account` (`account`)
) ENGINE=MyISAM AUTO_INCREMENT=10000 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
INSERT INTO `users` VALUES (1,'root','4a95bac3e19b28ee0acf3cc1137b4d1e66720a49','sha1','admin','Application',NULL,'Admin','2008-11-06 16:19:55','2008-11-06 16:19:55','','0000-00-00 00:00:00','','0000-00-00 00:00:00',1,0,'',NULL,'',0,'root_r',720,'0000-00-00 00:00:00','','','0000-00-00 00:00:00','11101111000000001');
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `validate_emails` (
  `id` int(10) NOT NULL auto_increment,
  `user_id` int(10) NOT NULL,
  `email` varchar(64) NOT NULL,
  `validate_code` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `vuln_products` (
  `vuln_seq` int(10) unsigned NOT NULL default '0',
  `vuln_type` char(3) NOT NULL default '',
  `prod_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`vuln_seq`,`vuln_type`,`prod_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
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
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;
TRUNCATE TABLE schema_version;
INSERT INTO schema_version (schema_version) VALUES (46);
