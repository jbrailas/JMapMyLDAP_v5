<?php
/**
 * PHP Version 8.1
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @edited		Giannis Brailas 2025
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Language\Text;

/**
 * Settings view class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @since       2.0
 */
class ShconfigViewSettings extends HtmlView
{
	protected $form = null;

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since   2.0
	 */
	public function display($tpl = null)
	{
		$this->form = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			///JError::raiseError(500, implode("\n", $errors));
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$this->addToolbar($this->getLayout());
		$this->sidebar = Sidebar::render(); //Joomla 5
		parent::display($tpl);
	}

	/**
	 * Method to configure the toolbar for this view.
	 *
	 * @param   string  $lName  Layout name.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	protected function addToolbar($lName = 'base')
	{
		$user	= Factory::getUser();

		ToolBarHelper::title(Text::sprintf('COM_SHCONFIG_SETTINGS_MANAGER', ucfirst($lName)), '');

		if (Factory::getUser()->authorise('core.admin', 'com_shconfig'))
		{
			ToolBarHelper::apply('settings.apply');
			ToolBarHelper::divider();
			ToolBarHelper::preferences('com_shconfig');
		}
		ToolbarHelper::back();
	}
}
