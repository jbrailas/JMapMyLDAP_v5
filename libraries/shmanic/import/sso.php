<?php
/**
 * Import for JSSOMySite.
 *
 * PHP Version 8
 *
 * @package    Shmanic.Libraries
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

if (!defined('SHPATH_PLATFORM'))
{
	// Load the platform
	require_once JPATH_PLATFORM . '/shmanic/import.php';
}

if (!defined('SHSSO_VERSION'))
{
	// Define the JSSOMySite version [TODO: move to platform]
	define('SHSSO_VERSION', '2.0.0');
}

// Load the global SSO language file
Factory::getLanguage()->load('shmanic_sso', JPATH_ROOT);

// Employ the event monitor for SSO
if (class_exists('SHSsoMonitor'))
{
	//$joomla_version = new Version(); 
	//if (version_compare($joomla_version->getShortVersion(), '4', '<'))
	if (Version::MAJOR_VERSION < 4)
		$dispatcher = JDispatcher::getInstance();
	else
		$dispatcher = Factory::getApplication()->getDispatcher();
	
	$instance = new SHSsoMonitor(
		$dispatcher
	);

	if (method_exists($instance, 'onAfterInitialise'))
	{
		// This is during initialisation
		$instance->onAfterInitialise();
	}
}
