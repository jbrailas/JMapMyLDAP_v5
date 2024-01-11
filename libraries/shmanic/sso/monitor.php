<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  SSO
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\Event\Event;
use Joomla\CMS\Input\Input;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

/**
 * A built in SSO event monitor.
 *
 * @package     Shmanic.Libraries
 * @subpackage  SSO
 * @since       2.0
 */
class SHSsoMonitor extends Event
{
	/**
	 * Proxy method for onAfterInitialise to fix potential race conditions.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onSHPlaformInitialise()
	{
		$this->onAfterInitialise();
	}

	/**
	 * Called on application initialisation.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onAfterInitialise()
	{
		$input = new Input;

		$config = SHFactory::getConfig();

		$bypass = $config->get('sso.bypasskey', 'nosso');

		// Check if the URL contains this key and the value assigned to it
		$value = $input->get($bypass, false);

		// Check whether the url has been set
		if ($value !== false)
		{
			$value = (int) $value;

			if ($value === SHSsoHelper::STATUS_ENABLE)
			{
				// Enable SSO
				SHSsoHelper::enable(true);
			}
			elseif ($value === SHSsoHelper::STATUS_LOGOUT_DISABLE)
			{
				// SSO user logout detected
				SHSsoHelper::disable(false);
			}
			elseif ($value === SHSsoHelper::STATUS_BYPASS_DISABLE)
			{
				// Disable SSO for bypass
				SHSsoHelper::disable(true);
			}
		}

		$this->_attemptSSO();
	}

	/**
	 * Method handles logout logic and reports back to the subject.
	 *
	 * @param   array  $user     Holds the user data.
	 * @param   array  $options  Array holding options such as client.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 */
	public function onUserLogout($user, $options = array())
	{
		// Check the required SSO libraries exist
		if (!(class_exists('SHSsoHelper') && class_exists('SHSso')))
		{
			// Error: classes missing
			SHLog::add(Text::_('LIB_SHSSOMONITOR_ERR_15001'), 15001, Log::ERROR, 'sso');

			return;
		}

		try
		{
			$config = SHFactory::getConfig();

			$sso = new SHSso(
				$config->get('sso.plugintype', 'sso')
			);

			// Attempt the logout
			return $sso->logout();
		}
		catch (Exception $e)
		{
			SHLog::add($e, 15009, Log::ERROR, 'sso');
		}
	}

	/**
	 * Method for attempting single sign on.
	 *
	 * @return  boolean  True on successful SSO or False on failure.
	 *
	 * @since   2.0
	 */
	protected function _attemptSSO()
	{
		// Check the required SSO libraries exist
		if (!(class_exists('SHSsoHelper') && class_exists('SHSso')))
		{
			// Error: classes missing
			SHLog::add(Text::_('LIB_SHSSOMONITOR_ERR_15001'), 15001, Log::ERROR, 'sso');

			return;
		}

		try
		{
			$config = SHFactory::getConfig();

			// Check if SSO is disabled via the session
			if (SHSsoHelper::status() !== SHSsoHelper::STATUS_ENABLE)
			{
				// It is disabled so do not continue
				return;
			}

			SHSsoHelper::enable();

			$forceLogin = false;

			$userId = Factory::getUser()->get('id');

			if ($config->get('sso.forcelogin', false))
			{
				if ($userId)
				{
					// Log out current user if detect user is not equal
					$forceLogin = true;
				}
			}
			else
			{
				if ($userId)
				{
					// User already logged in and no forcelogout
					return;
				}
			}

			/*
			 * Lets check the IP rule is valid before we continue -
			 * if the IP rule is false then SSO is not allowed here.
			 */
			////jimport('joomla.application.input');
			//$input = new Input($_SERVER);
			
			//if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] == 443))
			//	$input = new Input($_SERVER['HTTPS']);
			//else
			$input = new Input($_SERVER);

			// Get the IP address of this client
			$myIp = $input->get('REMOTE_ADDR', false, 'string');
			//error_log("ip of user is " . $myIp );
			
			// Get a list of the IP addresses specific to the specified rule
			$ipList = json_decode($config->get('sso.iplist') ?? '');

			// Get the rule value
			$ipRule = $config->get('sso.iprule', SHSsoHelper::RULE_ALLOW_ALL);

			if (!SHSsoHelper::doIPCheck($myIp, $ipList, $ipRule))
			{
				if (!$forceLogin)
				{
					// This IP isn't allowed
					SHLog::add(Text::_('LIB_SHSSO_DEBUG_15004'), 15004, Log::DEBUG, 'sso');
				}

				return;
			}

			/*
			 * We are going to check if we are in backend.
			 * If so then we need to check if sso is allowed
			 * to execute on the backend.
			 */
			if (Factory::getApplication()->isClient('admin'))
			{
				if (!$config->get('sso.backend', false))
				{
					if (!$forceLogin)
					{
						// Not allowed to SSO on backend
						SHLog::add(Text::_('LIB_SHSSO_DEBUG_15006'), 15006, Log::DEBUG, 'sso');
					}

					return;
				}
			}

			// Instantiate the main SSO library for detection & authentication
			$sso = new SHSso(
				$config->get('sso.plugintype', 'sso')
			);

			$detection = $sso->detect();

			if ($detection)
			{
				// Check the detected user is not blacklisted
				$blacklist = (array) json_decode($config->get('user.blacklist') ?? '');

				if (in_array($detection['username'], $blacklist))
				{
					SHLog::add(Text::sprintf('LIB_SHSSO_DEBUG_15007', $detection['username']), 15007, Log::DEBUG, 'sso');

					// Detected user is blacklisted
					return;
				}

				// Check if the current logged in user matches the detection
				if ($forceLogin && (strtolower($detection['username']) != strtolower(Factory::getUser()->get('username'))))
				{
					SHLog::add(Text::sprintf('LIB_SHSSO_DEBUG_15008', $detection['username']), 15008, Log::DEBUG, 'sso');

					// Need to logout the current user
					Factory::getApplication()->logout();
				}
			}

			// Attempt the login
			return $sso->login($detection);
		}
		catch (Exception $e)
		{
			SHLog::add($e, 15002, Log::ERROR, 'sso');
		}
	}
}
