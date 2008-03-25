ALTER TABLE `USERS` ADD `extra_role` VARCHAR( 20 ) NOT NULL AFTER `role_id` ;
UPDATE USERS SET extra_role = concat(user_name,'_r');

CREATE TABLE `USER_ROLES` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) NOT NULL,
  `role_id` int(10) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `USER_ROLES` (`user_id`,`role_id`) SELECT user_id,role_id FROM `USERS`;
