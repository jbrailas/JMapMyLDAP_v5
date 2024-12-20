<?php
/**
 * PHP Version 8.1
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @edited		2024 Giannis Brailas
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;

/**
 * RAW Host controller class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapControllerHost extends BaseController
{
	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   2.0
	 */
	public function getModel($name = 'Host', $prefix = 'ShldapModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Logic for LDAP host debug.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function debug()
	{
		if (!Factory::getUser()->authorise('core.manage', 'com_shldap'))
		{
			//JError::raiseError(500, Text::_('JERROR_ALERTNOAUTHOR'));
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 500);
			return;
		}

		$input = Factory::getApplication()->input;
		$model = $this->getModel();

		$data = $input->get('jform', array(), 'array');

		// Validate the posted data.
		// Sometimes the form needs some posted data, such as for plugins and modules.
		$form = $model->getForm($data, true);

		// As we only have one correct view and layout, we can force them for now
		$input->set('view', 'host');
		$input->set('layout', 'debug');

		// Display the view and layout
		parent::display();
	}
}
