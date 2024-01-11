<?php
/**
 * PHP Version 7.3
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * edited by RNS-SYSTEMS for PHP 7.3
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ContentHelper;

/**
 * Helper class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
abstract class ComShldapHelper extends ContentHelper
{
	/**
	 * The extension name.
	 *
	 * @var		string
	 * @since	2.0
	 */
	const EXTENSION = 'com_shldap';
	
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	 
	public static function addSubmenu($vName)
	{
		////JSubMenuHelper::addEntry(
		\JHtmlSidebar::addEntry(
			JText::_('COM_SHLDAP_SUBMENU_DASHBOARD'),
			'index.php?option=com_shldap&view=dashboard',
			$vName == 'dashboard'
		);

		////JSubMenuHelper::addEntry(
		\JHtmlSidebar::addEntry(
			JText::_('COM_SHLDAP_SUBMENU_HOSTS'),
			'index.php?option=com_shldap&view=hosts',
			$vName == 'hosts'
		);
	}
}
