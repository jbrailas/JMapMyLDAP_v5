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

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

//jimport('joomla.application.component.controlleradmin');

/**
 * Hosts controller class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapControllerHosts extends AdminController
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
	 * Removes an item.
	 *
	 * Overrides JControllerAdmin::delete to check the core.admin permission.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function delete()
	{
		if (!Factory::getUser()->authorise('core.admin', $this->option))
		{
			//JError::raiseError(500, Text::_('JERROR_ALERTNOAUTHOR'));
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 500);
			return;
		}

		return parent::delete();
	}

	/**
	 * Task for setting default LDAP configuraiton.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 */
	public function setDefault()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$input = Factory::getApplication()->input;

		$ids = $input->get('cid', null, 'array');

		if (count($ids) !== 1)
		{
			// Cannot have more than one default
			$message = Text::_('COM_SHLDAP_HOSTS_DEFAULT_SINGLE_ROW_ONLY');
			$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false), $message, 'error');

			return false;
		}

		$model = $this->getModel();
		$return = $model->setDefault($ids[0]);

		if ($return === false)
		{
			// Default failed.
			$message = Text::sprintf('COM_SHLDAP_HOSTS_DEFAULT_FAILED', $model->getError());
			$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false), $message, 'error');

			return false;
		}
		else
		{
			// Default succeeded.
			$message = Text::_('COM_SHLDAP_HOSTS_DEFAULT_SUCCESS');
			$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false), $message);

			return true;
		}
	}
}
