<?php
/**
 * PHP Version 8.1
 *
 * @package    Shmanic.Scripts
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @edited 		2024
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Installer script for com_shldap.
 *
 * @package  Shmanic.Scripts
 * @since    2.0
 */
class Com_ShldapInstallerScript
{
	/**
	 * Minimum PHP version to install this extension.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const MIN_PHP_VERSION = '8.1.0';

	/**
	 * Minimum Platform version to install this extension.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const MIN_PLATFORM_VERSION = '5.0';

	/**
	 * Method to run before an install/update/uninstall method.
	 *
	 * @param   string  $type    Type of change (install, update or discover_install).
	 * @param   object  $parent  Object of class calling this method.
	 *
	 * @return  boolean  False to abort installation.
	 *
	 * @since   2.0
	 */
	public function preflight($type, $parent)
	{
		// Check the PHP version is at least at 8.1.0
		if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<'))
		{
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_SHLDAP_PREFLIGHT_PHP_VERSION', PHP_VERSION, self::MIN_PHP_VERSION),
				'error'
			);

			return false;
		}

		if ($type == 'install' || $type == 'update')
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->quoteName('value'))
				->from($db->quoteName('#__sh_config'))
				->where($db->quoteName('name') . ' = ' . $db->quote('platform:version'));

			try
			{
				if ($version = $db->setQuery($query)->loadResult())
				{
					if (version_compare($version, self::MIN_PLATFORM_VERSION, '>='))
					{
						// Successfully meets the platform requirements
						return true;
					}

					Factory::getApplication()->enqueueMessage(
						Text::sprintf('COM_SHLDAP_PREFLIGHT_PLATFORM_VERSION', $version, self::MIN_PLATFORM_VERSION),
						'error'
					);

					return false;
				}
			}
			catch (Exception $e)
			{
			}

			// Platform is missing
			Factory::getApplication()->enqueueMessage(Text::_('COM_SHLDAP_PREFLIGHT_PLATFORM_NOT_INSTALLED'), 'error');

			return false;
		}
	}

	/**
	 * Method to run after an install/update/uninstall method.
	 *
	 * @param   string  $type     Type of change (install, update or discover_install).
	 * @param   object  $parent   Object of class calling this method.
	 * @param   array   $results  Array of extension results.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function postflight($type, $parent, $results = array())
	{
		if ($type == 'install' || $type == 'update')
		{
			$db = Factory::getDbo();

			// Update the LDAP version
			$db->setQuery(
				$db->getQuery(true)
					->update($db->quoteName('#__sh_config'))
					->set($db->quoteName('value') . ' = ' . $db->quote($parent->getManifest()->version))
					->where($db->quoteName('name') . ' = ' . $db->quote('ldap:version'))
			)
			->execute();
		}
	}
}
