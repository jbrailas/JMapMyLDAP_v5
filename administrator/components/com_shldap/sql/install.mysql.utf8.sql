--
-- Table structure for table `#__sh_ldap_config`
--
CREATE TABLE IF NOT EXISTS `#__sh_ldap_config` (
  `id` int NOT NULL,
  `name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enabled` tinyint NOT NULL DEFAULT '1',
  `ordering` int DEFAULT '0',
  `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `checked_out` int NOT NULL DEFAULT '0',
  `checked_out_time` datetime DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO `#__sh_ldap_config` (`id`, `name`, `enabled`, `ordering`, `params`, `checked_out`, `checked_out_time`) VALUES
(1, 'sample', 1, 0, '{\"table\":\"#__sh_ldap_config\",\"use_v3\":\"1\",\"negotiate_tls\":\"0\",\"use_referrals\":\"0\",\"host\":\"\",\"port\":\"3268\",\"proxy_username\":\"\",\"proxy_password\":\"\",\"proxy_encryption\":\"0\",\"base_dn\":\"\",\"user_qry\":\"(sAMAccountName=[username])\",\"ldap_uid\":\"sAMAccountName\",\"ldap_fullname\":\"name\",\"ldap_email\":\"mail\",\"ldap_userAccountControl\":\"userAccountControl\",\"ldap_password\":\"unicodePwd\",\"password_hash\":\"unicode\",\"password_prefix\":\"0\",\"all_user_filter\":\"(objectClass=person)\"}', 0, NULL);


ALTER TABLE `#__sh_ldap_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_name` (`name`);

ALTER TABLE `#__sh_ldap_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

--
-- LDAP default table data for `#__sh_config`
--

REPLACE INTO `#__sh_config`
  SET `name` = 'ldap:version',
  `value` = '2.0.0.0';

REPLACE INTO `#__sh_config`
  SET `name` = 'ldap:source',
  `value` = '1';

REPLACE INTO `#__sh_config`
  SET `name` = 'ldap:plugin',
  `value` = 'ldap';

REPLACE INTO `#__sh_config` 
  SET `name` = 'user:type',
  `value` = 'ldap';

