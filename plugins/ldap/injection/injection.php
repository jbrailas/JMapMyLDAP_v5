<?php
/**
 * PHP Version 8.1
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Injection
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @edited		2024
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;

/**
 * LDAP Form Injection Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  Ldap.Injection
 * @since       2.0
 */
class PlgLdapInjection extends CMSPlugin
{
	protected $passwordForms = array();

	protected $domainForms = array();

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since  2.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();

		// Split and trim the permitted forms
		$this->passwordForms = explode(';', $this->params->get('ldap_password_forms'));
		array_walk($this->passwordForms, 'self::_trimValue');

		$this->domainForms = explode(';', $this->params->get('ldap_domain_forms'));
		array_walk($this->domainForms, 'self::_trimValue');
	}

	/**
	 * Trims an array's elements. Use with array_walk.
	 *
	 * @param   string  &$value  Value of element.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	private function _trimValue(&$value)
	{
		$value = trim($value);
	}

	/**
	 * Injects several fields into specific forms.
	 *
	 * @param   Form  $form  The form to be altered.
	 * @param   array  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   2.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		// Check we are manipulating a valid form
		if (!($form instanceof Form))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check if the password field needs injecting
		if (($this->params->get('use_ldap_password', false)) && (in_array($form->getName(), $this->passwordForms)))
		{
			// Check if this user should have a profile
			if (SHLdapHelper::isUserLdap(isset($data->id) ? $data->id : 0))
			{
				if ($this->params->get('ldap_password_layout_edit', true))
				{
					// Check if this is in the 'edit' layout or in the save state
					if ((strtolower(Factory::getApplication()->input->get('layout')) === 'edit')
						|| (strtolower(Factory::getApplication()->input->get('task')) === 'save'))
					{
						$form->loadFile(realpath(__DIR__) . '/forms/ldap_password.xml', false, false);
					}
				}
				else
				{
					$form->loadFile(realpath(__DIR__) . '/forms/ldap_password.xml', false, false);
				}
			}
		}

		// Check if the domain field needs injecting
		if (($this->params->get('use_ldap_domain', false)) && (in_array($form->getName(), $this->domainForms)))
		{
			$form->loadFile(realpath(__DIR__) . '/forms/ldap_domain.xml', false, false);
		}

		return true;
	}
}
