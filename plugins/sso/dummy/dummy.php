<?php
/**
 * PHP Version 8.1
 *
 * @package     Shmanic.Plugin
 * @subpackage  SSO
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @edited		2024
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Attempts to match a user based on the supplied username.
 *
 * @package     Shmanic.Plugin
 * @subpackage  SSO
 * @since       1.0
 */
class PlgSSODummy extends CMSPlugin
{
	/**
	 * This method returns the specified username.
	 *
	 * @return  string  Username
	 *
	 * @since   1.0
	 */
	public function detectRemoteUser()
	{
		return $this->params->get('username');
	}
}
