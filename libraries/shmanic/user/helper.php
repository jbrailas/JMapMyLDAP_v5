<?php
/**
 * PHP Version 8.1
 *
 * @package     Shmanic.Libraries
 * @subpackage  User
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
/**
 * A user helper class.
 *
 * @package     Shmanic.Libraries
 * @subpackage  User
 * @since       2.0
 */
abstract class SHUserHelper
{
	const PARAM_AUTH_TYPE = 'auth_type';

	const PARAM_AUTH_DOMAIN = 'auth_domain';

	/**
	 * This method returns a user object. If options['autoregister'] is true,
	 * and if the user doesn't exist, then it'll be created.
	 *
	 * @param   array  $user      Holds the user data.
	 * @param   array  &$options  Array holding options (remember, autoregister, group).
	 *
	 * @return  User  A User object containing the user.
	 *
	 * @since   1.0
	 * @throws  Exception
	 */
	public static function getUser(array $user, &$options = array())
	{
		$instance = User::getInstance();

		if (isset($options['adapter']))
		{
			// Tell the getUser to store the auth_type and auth_config based on whats inside the adapter
			$options['type'] = isset($options['type']) ? $options['type'] : $options['adapter']::getType();
			$options['domain'] = isset($options['domain']) ? $options['domain'] : $options['adapter']->getDomain();
		}

		// Check if the user already exists in the database
		if ($id = intval(UserHelper::getUserId($user['username'])))
		{
			$instance->load($id);

			// Inject the type and domain into this object if they are set
			if (isset($options['type']))
			{
				if ($instance->getParam(self::PARAM_AUTH_TYPE) != $options['type'])
				{
					$options['change'] = true;
					$instance->setParam(self::PARAM_AUTH_TYPE, $options['type']);
				}
			}

			if (isset($options['domain']))
			{
				if ($instance->getParam(self::PARAM_AUTH_DOMAIN) != $options['domain'])
				{
					$options['change'] = true;
					$instance->setParam(self::PARAM_AUTH_DOMAIN, $options['domain']);
				}
			}

			return $instance;
		}

		// ** The remainder of this method is for new users only **

		$config = SHFactory::getConfig();

		// Deal with auto registration flags
		$autoRegister = (int) $config->get('user.autoregister', 1);

		if ($autoRegister === 0 || $autoRegister === 1)
		{
			// Inherited Auto-registration
			$options['autoregister'] = isset($options['autoregister']) ? $options['autoregister'] : $autoRegister;
		}
		else
		{
			// Override Auto-registration
			$options['autoregister'] = ($autoRegister === 2) ? 1 : 0;
		}

		// Deal with the default group
		//jimport('joomla.application.component.helper');
		$comUsers = ComponentHelper::getParams('com_users', true);

		if ($comUsers === false)
		{
			// Check if there is a default set in the SHConfig
			$defaultUserGroup = $config->get('user.defaultgroup', 2);
		}
		else
		{
			// Respect Joomla's default user group
			$defaultUserGroup = $comUsers->get('new_usertype', 2);
		}

		// Setup the user fields for this new user
		$instance->set('id', 0);
		$instance->set('name', $user['fullname']);
		$instance->set('username', $user['username']);
		$instance->set('password_clear', $user['password_clear']);
		$instance->set('email', $user['email']);
		$instance->set('block', $user['block']);
		$instance->set('usertype', 'depreciated');
		$instance->set('groups', array($defaultUserGroup));

		// Set the User Adapter parameters
		if (isset($options['type']))
		{
			$instance->setParam(self::PARAM_AUTH_TYPE, $options['type']);
		}

		if (isset($options['domain']))
		{
			$instance->setParam(self::PARAM_AUTH_DOMAIN, $options['domain']);
		}

		// If autoregister is set, register the user
		if ($options['autoregister'])
		{
			if (!self::save($instance))
			{
				// Failed to save the user to the database
				throw new Exception(Text::sprintf('LIB_SHUSERHELPER_ERR_10501', $user['username'], $instance->getError()), 10501);
			}
		}
		else
		{
			// We don't want to proceed if autoregister is not enabled
			throw new Exception(Text::sprintf('LIB_SHUSERHELPER_ERR_10502', $user['username']), 10502);
		}

		return $instance;
	}

	/**
	 * Saves a User object to the database. This method has been adapted from
	 * the User::save() method to bypass ACL checks for super users. It still
	 * calls the onUserBeforeSave and onUserAfterSave events.
	 *
	 * @param   User    &$user     Object to save.
	 * @param   Boolean  $dispatch  True to call the listening plugins or False to skip dispatching
	 *
	 * @return  boolean  True on success or False on failure.
	 *
	 * @since   2.0
	 * @throws  Exception
	 */
	public static function save(User &$user, $dispatch = true)
	{
		// Create the user table object
		$table = $user->getTable();
		//$user->params = (string) $user->getParameters();
		//NEW changed for Joomla 5
		//if (property_exists($user, 'params')) {
			$registry = new Registry;
			$registry->loadString($user->params);
			$user->params = $registry->toArray();
		//}		
		
		$table->bind($user->getProperties());

		$username = $user->username;

		// Check and store the object.
		if (!$table->check())
		{
			throw new Exception(Text::sprintf('LIB_SHUSERHELPER_ERR_10511', $username, $table->getError()), 10511);
		}

		$my = Factory::getUser();

		if ($dispatch)
		{
			// Check if we are creating a new user
			$isNew = empty($user->id);

			// Get the old user
			$oldUser = new User($user->id);

			// Fire the onUserBeforeSave event.
			PluginHelper::importPlugin('user');
			//$dispatcher = JDispatcher::getInstance();
			//$dispatcher = Factory::getApplication()->getDispatcher();
			$result = Factory::getApplication()->triggerEvent('onUserBeforeSave', array($oldUser->getProperties(), $isNew, $user->getProperties()));
			
			if (in_array(false, $result, true))
			{
				// Plugin will have to raise its own error or throw an exception.
				return false;
			}
		}

		// Store the user data in the database
		if (!($result = $table->store()))
		{
			throw new Exception(Text::sprintf('LIB_SHUSERHELPER_ERR_10512', $username, $table->getError()), 10512);
		}

		// Set the id for the User object in case we created a new user.
		if (empty($user->id))
		{
			$user->id = $table->get('id');
		}

		if ($my->id == $table->id)
		{
			$registry = new Registry;
			$registry->loadString($table->params);
			$my->setParameters($registry);
		}

		if ($dispatch)
		{
			// Fire the onUserAfterSave event
			Factory::getApplication()->triggerEvent('onUserAfterSave', array($user->getProperties(), $isNew, $result, $user->getError()));
			//new joomla 5 way
			//$dispatcher = Factory::getApplication()->getDispatcher();
			//$onUserAfterSaveEvent = new Event('onUserAfterSave', array($user->getProperties(), $isNew, $result, $user->getError()));
			//$dispatcher->dispatch('onUserAfterSave', $onUserAfterSaveEvent);	
		}

		return $result;
	}

	/**
	 * Gets the User Adapter Type Parameter from the user.
	 *
	 * @param   string|integer|User|array  $user  User to inspect.
	 *
	 * @return  string  The type for the user if specified.
	 *
	 * @since   2.0
	 */
	public static function getTypeParam($user = null)
	{
		if (is_null($user) || is_numeric($user))
		{
			// The input variable indicates we must load the user object
			$type = Factory::getUser($user)->getParam(self::PARAM_AUTH_TYPE);
		}
		elseif ($user instanceof User)
		{
			// Direct access of the object
			$type = $user->getParam(self::PARAM_AUTH_TYPE);
		}
		elseif (is_array($user))
		{
			if (isset($user['params']))
			{
				// Load the user parameters into a registry object for inspection
				$reg = new Registry;
				$reg->loadString($user['params']);

				$type = $reg->get(self::PARAM_AUTH_TYPE);
			}
			else
			{
				return null;
			}
		}
		elseif (is_string($user))
		{
			// Assume string
			//$id = JUserHelper::getUserId((string) $user);
			$id = UserHelper::getUserId($user);
			$type = Factory::getUser($id)->getParam(self::PARAM_AUTH_TYPE);
		}

		if (!empty($type))
		{
			return $type;
		}

		return null;
	}

	/**
	 * Gets the Domain Parameter from the user.
	 *
	 * @param   string|integer|User|array  $user  User to inspect.
	 *
	 * @return  string  The domain for the user if specified.
	 *
	 * @since   2.0
	 */
	public static function getDomainParam($user = null)
	{
		if (is_null($user) || is_numeric($user))
		{
			// The input variable indicates we must load the user object
			$domain = Factory::getUser($user)->getParam(self::PARAM_AUTH_DOMAIN);
		}
		elseif ($user instanceof User)
		{
			// Direct access of the object
			$domain = $user->getParam(self::PARAM_AUTH_DOMAIN);
		}
		elseif (is_array($user))
		{
			if (isset($user['params']))
			{
				// Load the user parameters into a registry object for inspection
				$reg = new Registry;
				$reg->loadString($user['params']);

				$domain = $reg->get(self::PARAM_AUTH_DOMAIN);
			}
			else
			{
				return null;
			}
		}
		elseif (is_string($user))
		{
			// Assume string
			//$id = JUserHelper::getUserId((string) $user);
			$id = UserHelper::getUserId($user);
			$domain = Factory::getUser($id)->getParam(self::PARAM_AUTH_DOMAIN);
		}

		if (!empty($domain))
		{
			return $domain;
		}

		return null;
	}

	/**
	 * Get all Joomla user groups from the database.
	 *
	 * @return  array  Joomla user groups
	 *
	 * @since   1.0
	 */
	public static function getJUserGroups()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Build SQL to return both group IDs and Names/Titles
		$query->select($db->quoteName('id'))
			->from($db->quoteName('#__usergroups'))
			->order($db->quoteName('id'));

		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Add a group to a Joomla user.
	 *
	 * @param   User    &$user    User for group addition.
	 * @param   integer  $groupId  Joomla group ID.
	 *
	 * @return  mixed  Exception on errror
	 *
	 * @since   1.0
	 */
	public static function addUserToGroup(User &$user, $groupId)
	{
		$groupId = (int) $groupId;

		// Add the user to the group if necessary.
		if (!in_array($groupId, $user->groups))
		{
			// Get the title of the group.
			$db	= Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->quoteName('title'))
				->from($db->quoteName('#__usergroups'))
				->where($db->quoteName('id') . ' = ' . $db->quote($groupId));

			$db->setQuery($query);

			$title = $db->loadResult();

			// If the group does not exist, throw an exception.
			if (!$title)
			{
				throw new Exception(Text::_('JLIB_USER_EXCEPTION_ACCESS_USERGROUP_INVALID'));
			}

			// Add the group data to the user object.
			$user->groups[$title] = $groupId;
		}

		return true;
	}

	/**
	 * Remove a group from a Joomla user.
	 *
	 * @param   User    &$user    The User for the group removal
	 * @param   integer  $groupId  The Joomla group ID to remove
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function removeUserFromGroup(User &$user, $groupId)
	{
		// Remove the user from the group if necessary.
		$key = array_search((int) $groupId, $user->groups);

		if ($key !== false)
		{
			// Remove the user from the group.
			unset($user->groups[$key]);
		}
	}
}
