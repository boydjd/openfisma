--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

-- Convert table storage from MyISAM to InnoDB

ALTER TABLE account_logs ENGINE = InnoDB;
ALTER TABLE assets ENGINE = InnoDB;
ALTER TABLE audit_logs ENGINE = InnoDB;
ALTER TABLE blscrs ENGINE = InnoDB;
ALTER TABLE comments ENGINE = InnoDB;
ALTER TABLE configurations ENGINE = InnoDB;
ALTER TABLE evaluations ENGINE = InnoDB;
ALTER TABLE events ENGINE = InnoDB;
ALTER TABLE evidences ENGINE = InnoDB;
ALTER TABLE functions ENGINE = InnoDB;
ALTER TABLE ldap_config ENGINE = InnoDB;
ALTER TABLE networks ENGINE = InnoDB;
ALTER TABLE notifications ENGINE = InnoDB;
ALTER TABLE organizations ENGINE = InnoDB;
ALTER TABLE plugins ENGINE = InnoDB;
ALTER TABLE poam_evaluations ENGINE = InnoDB;
ALTER TABLE poam_vulns ENGINE = InnoDB;
ALTER TABLE poams ENGINE = InnoDB;
ALTER TABLE products ENGINE = InnoDB;
ALTER TABLE role_functions ENGINE = InnoDB;
ALTER TABLE roles ENGINE = InnoDB;
ALTER TABLE schema_version ENGINE = InnoDB;
ALTER TABLE sources ENGINE = InnoDB;
ALTER TABLE systems ENGINE = InnoDB;
ALTER TABLE user_events ENGINE = InnoDB;
ALTER TABLE user_roles ENGINE = InnoDB;
ALTER TABLE user_systems ENGINE = InnoDB;
ALTER TABLE users ENGINE = InnoDB;
ALTER TABLE validate_emails ENGINE = InnoDB;
ALTER TABLE vuln_products ENGINE = InnoDB;
ALTER TABLE vulnerabilities ENGINE = InnoDB;

