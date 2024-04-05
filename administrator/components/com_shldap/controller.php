<?php
/**
 * PHP Version 8.1
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @edited		2024
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

//jimport('joomla.application.component.controlleradmin');

/**
 * Base controller class for Shldap.
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @since       2.0
 */
class ShldapController extends BaseController
{
	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $default_view = 'dashboard';

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return	JController  A JController object to support chaining.
	 *
	 * @since	2.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		// Get the document object.
		$document = Factory::getDocument();

		// Get the input class
		$input = Factory::getApplication()->input;

		// Set the default view name and format from the Request.
		$vName	 = $input->get('view', 'dashboard', 'cmd');
		$vFormat = $document->getType();
		$lName	 = $input->get('layout', 'default', 'cmd');
		$id		 = $input->get('id', null, 'cmd');

		// Check for edit form.
		if ($vName == 'host' && $lName == 'edit' && !$this->checkEditId('com_shldap.edit.host', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setRedirect(Route::_('index.php?option=com_shldap&view=hosts', false));

			return false;
		}

		// Add the submenu
		ComShldapHelper::addSubmenu($vName);

		parent::display($cachable, $urlparams);

		return $this;
	}
}
