--------------------------------------------------------------------------------
-- Author:    Ryan Yang
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id:$
--------------------------------------------------------------------------------

-- Add functions for CRUD roles and CRUD networks, and assign those roles to
-- the application administrator *if* the default application administator
-- account exists.

INSERT INTO functions
     VALUES (80, 'View Roles', 'admin_roles', 'read', '', '1'),
            (81, 'Edit Roles', 'admin_roles', 'update', '', '1'),
            (82, 'Create Roles', 'admin_roles', 'create', '', '1'),
            (83, 'Delete Roles', 'admin_roles', 'delete', '', '1'),
            (84, 'Define Roles', 'admin_roles', 'definition', '', '1'),
            (85, 'View Networks', 'admin_networks', 'read', '', '1'),
            (86, 'Edit Networks', 'admin_networks', 'update', '', '1'),
            (87, 'Create Networks', 'admin_networks', 'create', '', '1'),
            (88, 'Delete Networks', 'admin_networks', 'delete', '', '1');

INSERT INTO role_functions
     SELECT id,
            80
       FROM roles
      WHERE name like 'Application Administrator';
      
INSERT INTO role_functions
     SELECT id,
            81
       FROM roles
      WHERE name like 'Application Administrator';

INSERT INTO role_functions
     SELECT id,
            82
       FROM roles
      WHERE name like 'Application Administrator';

INSERT INTO role_functions
     SELECT id,
            83
       FROM roles
      WHERE name like 'Application Administrator';

INSERT INTO role_functions
     SELECT id,
            84
       FROM roles
      WHERE name like 'Application Administrator';

INSERT INTO role_functions
     SELECT id,
            85
       FROM roles
      WHERE name like 'Application Administrator';

INSERT INTO role_functions
     SELECT id,
            86
       FROM roles
      WHERE name like 'Application Administrator';

INSERT INTO role_functions
     SELECT id,
            87
       FROM roles
      WHERE name like 'Application Administrator';

INSERT INTO role_functions
     SELECT id,
            88
       FROM roles
      WHERE name like 'Application Administrator';
