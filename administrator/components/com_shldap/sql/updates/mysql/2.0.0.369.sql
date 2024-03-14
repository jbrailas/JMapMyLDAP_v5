ALTER TABLE  `#__sh_ldap_config` 
	ADD  `checked_out` INT UNSIGNED NOT NULL DEFAULT  '0',
	ADD  `checked_out_time` DATETIME NOT NULL DEFAULT  '1000-01-01 00:00:00';

