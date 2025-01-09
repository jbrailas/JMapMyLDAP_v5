<?php
/**
 * PHP Version 8.1
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @edited		Giannis Brailas 2025
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$errorStyle = 'background-color:#FBB;display:block;padding:4px 0;font-weight:bold;';

echo ' :: PHP LDAP Debug ' . SHLDAP_VERSION . ' Script Started :: <br /><br />';
$this->debug = ($_POST['jform']); //NEW NEW
try
{
	//if (!$_POST['debug_username'][0] || !$_POST['debug_password'][0])
	if (!$this->debug['debug_username'] || !$this->debug['debug_password']) {
		//throw new Exception('Debug username or password is missing:' . print_r($this->debug, true));
		throw new Exception('Debug username or password is missing.');
	}
	
	//$client = $this->get('LdapObject');
	$config = $this->debug;
	$client = new SHLdap($config);

	if ($this->debug['debug_full'])
	{
		echo "Switching on full PHP LDAP debug (outputs to web server log).<br />";
		$client::fullDebug();
	}

	echo "Attempting LDAP connection with {$client->info}... <br />";

	$client->connect();

	print_r($this->debug);
	echo "<br><br><br>";
	print_r($client);
	echo "Attempting to find the distinguished name for user {$this->debug['debug_username']}...<br />";

	// Hacky way to detect whether proxy encryption could be the problem
	if (!$client->proxyBind() && isset($_REQUEST['jform']['proxy_encryption']) && $_REQUEST['jform']['proxy_encryption'])
	{
		echo '<br /><p style="background-color:#BBF;display:block;padding:4px 0;font-weight:bold;"><strong>NOTE: after changing proxy password, save before testing otherwise it will fail!</strong></p><br />';
	}

	$dn = $client->getUserDn($this->debug['debug_username'], $this->debug['debug_password'], true);

	echo "Successfully found distinguished name {$dn}.<br />";

	// Read the test users attributes
	$read = $client->read($dn);

	if (!$read->countEntries() > 0)
	{
		// No entries found
		throw new Exception('No attributes found for test user.');
	}

	/* *****************************************************
 	 * ************** Mandatory Attributes *****************
 	 * *****************************************************
 	 */
	echo 'Successfully found test user attributes. <br /><br />';

	if ($uid = $read->getValue(0, $client->keyUid, 0))
	{
		echo "User ID: {$uid} <br />";
	}
	else
	{
		echo "<p style=\"{$errorStyle}\">Invalid Map User ID.</p>";
	}

	if ($fullname = $read->getValue(0, $client->keyName, 0))
	{
		echo "Full Name: {$fullname} <br />";
	}
	else
	{
		echo "<p style=\"{$errorStyle}\">Invalid Map Full Name.</p>";
	}

	if ($email = $read->getValue(0, $client->keyEmail, 0))
	{
		echo "Email: {$email} <br />";
	}
	else
	{
		echo "<p style=\"{$errorStyle}\">Invalid Map Email. If your LDAP server does not use emails, then use a 'fake' email.</p>";
	}
	
	if ($enabled_disabled = $read->getValue(0, $client->keyUserAccountControl, 0))
	{
		if ($enabled_disabled == 514 || $enabled_disabled == 66050)
			echo "The user is disabled!<br />";
		else
			echo "The user is enabled, ok.<br />";
	}
	else
	{
		echo "<p style=\"{$errorStyle}\">Invalid Map userAccountControl.</p>";
	}

	/* *****************************************************
 	 * **************** Attributes Tables ******************
 	 * *****************************************************
 	 */

	echo '<div style="margin:10px 0; padding:2px; background-color:#EAEAEA;display:block;border:#AAA 1px solid;"><table>';

	echo '<tr style="background-color:#CCC;"><th>LDAP Attribute</th><th>Value(s)</th></tr>';

	/*
	 * This section loops around each attribute found in the Ldap
	 * query then prints it onto screen within a table.
	 */
	for ($i = 0; $i < $read->countAttributes(0); ++$i)
	{
		echo '<tr><td style="border-top:#CCC 1px solid;"><strong>';
		echo $read->getAttributeKeyAtIndex(0, $i);
		echo '</strong></td><td style="border-top:#CCC 1px solid;">';

		$values = $read->getAttributeAtIndex(0, $i);

		foreach ($values as $key => $value)
		{
			echo htmlspecialchars("[{$key}] {$value}") . '<br />';
		}

		echo '</td></tr>';
	}

	echo '</table></div>';

	echo '<em>This may not be an exhaustive list of attributes where some attributes only return when specifically requested.</em>';

	echo '<br><br>';

	echo 'Attempting to get users using the All User Filter...<br />';

	$client->proxyBind();
	$result = $client->search(null, $client->allUserFilter, array('dn'));

	echo 'Found ' . $result->countEntries() . ' users.';
}
catch (Exception $e)
{
	echo "<p style=\"{$errorStyle}\">" . '[' . $e->getCode() . '] ' . $e->getMessage() . '</p>';
}


echo '<br /><br /> :: PHP LDAP Debug ' . SHLDAP_VERSION . ' Script Finished :: ';
