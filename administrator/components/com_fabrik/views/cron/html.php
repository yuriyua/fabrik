<?php
/**
 * View to edit a cron.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\Cron;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \FabrikHelperHTML as FabrikHelperHTML;
use \JFactory as JFactory;
use Fabrik\Admin\Helpers\Fabrik;
use \FText as FText;
use \JToolBarHelper as JToolBarHelper;
use \stdClass as stdClass;

/**
 * View to edit a cron.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends \Fabrik\Admin\Views\Html
{
	/**
	 * Form
	 *
	 * @var JForm
	 */
	protected $form;

	/**
	 * Cron item
	 *
	 * @var JTable
	 */
	protected $item;

	/**
	 * View state
	 *
	 * @var object
	 */
	protected $state;

	/**
	 * Plugin HTML
	 *
	 * @var string
	 */
	protected $pluginFields;

	/**
	 * Render the view
	 *
	 * @return  string
	 */

	public function render()
	{
		$this->form         = $this->model->getForm();
		$this->item         = $this->model->getItem();
		$this->state        = $this->model->getState();
		$this->pluginFields = $this->model->getPluginHTML();

		$this->addToolbar();

		$srcs   = FabrikHelperHTML::framework();
		$srcs[] = 'media/com_fabrik/js/fabrik.js';
		$srcs[] = 'administrator/components/com_fabrik/views/namespace.js';
		$srcs[] = 'administrator/components/com_fabrik/views/pluginmanager.js';
		$srcs[] = 'administrator/components/com_fabrik/views/cron/admincron.js';

		$shim                         = array();
		$dep                          = new stdClass;
		$dep->deps                    = array('admin/pluginmanager');
		$shim['admin/cron/admincron'] = $dep;

		$opts         = new stdClass;
		$opts->plugin = $this->item->plugin;

		$js   = array();
		$js[] = "\twindow.addEvent('domready', function () {";
		$js[] = "\t\tFabrik.controller = new CronAdmin(" . json_encode($opts) . ");";
		$js[] = "\t})";
		FabrikHelperHTML::iniRequireJS($shim);
		FabrikHelperHTML::script($srcs, implode("\n", $js));

		return parent::render();
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */

	protected function addToolbar()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;
		$input->set('hidemainmenu', true);
		$user       = JFactory::getUser();
		$userId     = $user->get('id');
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo      = Fabrik::getActions($this->state->get('filter.category_id'));
		$title      = $isNew ? FText::_('COM_FABRIK_MANAGER_CRON_NEW') : FText::_('COM_FABRIK_MANAGER_CRON_EDIT') . ' "' . $this->item->label . '"';
		JToolBarHelper::title($title, 'cron.png');

		if ($isNew)
		{
			// For new records, check the create permission.
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::apply('cron.apply', 'JTOOLBAR_APPLY');
				JToolBarHelper::save('cron.save', 'JTOOLBAR_SAVE');
				JToolBarHelper::addNew('cron.save2new', 'JTOOLBAR_SAVE_AND_NEW');
			}

			JToolBarHelper::cancel('cron.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolBarHelper::apply('cron.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save('cron.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolBarHelper::addNew('cron.save2new', 'JTOOLBAR_SAVE_AND_NEW');
					}
				}
			}

			if ($canDo->get('core.create'))
			{
				JToolBarHelper::custom('cron.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}

			JToolBarHelper::cancel('cron.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('JHELP_COMPONENTS_FABRIK_CRONS_EDIT', false, FText::_('JHELP_COMPONENTS_FABRIK_CRONS_EDIT'));
	}
}
