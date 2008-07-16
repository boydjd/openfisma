
-- upgrade this for ACL

   ALTER TABLE `ROLES` CHANGE `role_nickname` `role_nickname` VARCHAR( 64 ) CHARACTER
                               SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;  

   UPDATE `ROLES` SET role_nickname = role_name  WHERE role_nickname = 'none';
   UPDATE `ROLES` SET role_name = 'none' WHERE role_nickname like '%_r';
