<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Tag;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a single Tag.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		// Check for errors.
		if(count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$toolbar = Toolbar::getInstance('toolbar');

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if(isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = JoomHelper::getActions();

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_TAGS').' :: '.Text::_('COM_JOOMGALLERY_TAG_EDIT'), "tag");

		// If not checked out, can save the item.
		if(!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			ToolbarHelper::apply('tag.apply', 'JTOOLBAR_APPLY');
		}

		if(!$checkedOut && ($canDo->get('core.create')))
		{
			$saveGroup = $toolbar->dropdownButton('save-group');

			$saveGroup->configure
			(
				function (Toolbar $childBar) use ($checkedOut, $canDo, $isNew)
				{
					$childBar->save('tag.save', 'JTOOLBAR_SAVE');

					if(!$checkedOut && ($canDo->get('core.create')))
					{
						$childBar->save2new('tag.save2new');
					}

					// If an existing item, can save to a copy.
					if(!$isNew && $canDo->get('core.create'))
					{
						$childBar->save2copy('tag.save2copy');
					}
				}
			);
		}

		if(empty($this->item->id))
		{
			ToolbarHelper::cancel('tag.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('tag.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
