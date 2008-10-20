# A test script for developing the plugin reports feature

    SELECT CONCAT(u.name_last, ', ', u.name_first) "SSO Name",
           CONCAT(s.name, ' (', s.nickname, ')') "System Name"
      FROM users u
INNER JOIN user_systems us on u.id = us.user_id
INNER JOIN systems s on us.system_id = s.id
INNER JOIN user_roles ur on u.id = ur.user_id
INNER JOIN roles r on ur.role_id = r.id
     WHERE r.nickname like 'ISSO';