<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Session\Session;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$user       = Factory::getUser();
$userId     = $user->get('id');
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$canCreate  = $user->authorise('core.create', 'com_joomgallery') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'categoryform.xml');
$canEdit    = $user->authorise('core.edit', 'com_joomgallery') && file_exists(JPATH_COMPONENT .  DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'categoryform.xml');
$canCheckin = $user->authorise('core.manage', 'com_joomgallery');
$canChange  = $user->authorise('core.edit.state', 'com_joomgallery');
$canDelete  = $user->authorise('core.delete', 'com_joomgallery');

// Import CSS
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_joomgallery.list');
?>

<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
	<div class="table-responsive">
		<table class="table table-striped" id="categoryList">
			<thead>
			<tr>
          <th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_COMMON_CATEGORY', 'a.title', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'JGLOBAL_SHOW_PARENT_CATEGORY_LABEL', 'a.parent_id', $listDirn, $listOrder); ?>
					</th>

          <?php if ($canEdit || $canDelete): ?>
            <th class="center">
              <?php echo Text::_('COM_JOOMGALLERY_COMMON_ACTION'); ?>
            </th>
					<?php endif; ?>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'JPUBLISHED', 'a.published', $listDirn, $listOrder); ?>
					</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
			</tfoot>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<?php $canEdit = $user->authorise('core.edit', 'com_joomgallery'); ?>
				<?php if (!$canEdit && $user->authorise('core.edit.own', 'com_joomgallery')): ?>
				<?php $canEdit = Factory::getUser()->id == $item->created_by; ?>
				<?php endif; ?>

				<tr class="row<?php echo $i % 2; ?>">
          <td>
						<?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
						<?php $canCheckin = Factory::getUser()->authorise('core.manage', 'com_joomgallery.' . $item->id) || $item->checked_out == Factory::getUser()->id; ?>
						<?php if($canCheckin && $item->checked_out > 0) : ?>
							<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.checkin&id=' . $item->id .'&'. Session::getFormToken() .'=1'); ?>">
							  <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'category.', false); ?>
              </a>
						<?php endif; ?>
						<a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $item->id); ?>">
							<?php echo $this->escape($item->title); ?>
            </a>
					</td>

					<td>
						<?php echo $item->parent_id; ?>
					</td>

					<?php if ($canEdit || $canDelete): ?>
						<td class="center">
							<?php $canCheckin = Factory::getUser()->authorise('core.manage', 'com_joomgallery.' . $item->id) || $this->item->checked_out == Factory::getUser()->id; ?>

							<?php if($canEdit && $item->checked_out == 0): ?>
								<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.edit&id=' . $item->id, false, 2); ?>" class="btn btn-mini" type="button"><i class="icon-edit" ></i></a>
							<?php endif; ?>
							<?php if ($canDelete): ?>
								<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=categoryform.remove&id=' . $item->id, false, 2); ?>" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></a>
							<?php endif; ?>
						</td>
					<?php endif; ?>

          <td>
						<?php echo $item->published; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php if ($canCreate) : ?>
		<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=categoryform.edit&id=0', false, 0); ?>" class="btn btn-success btn-small">
      <i class="icon-plus"></i> <?php echo Text::_('COM_JOOMGALLERY_COMMON_NEW_CATEGORY'); ?>
    </a>
	<?php endif; ?>

	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<input type="hidden" name="filter_order" value=""/>
	<input type="hidden" name="filter_order_Dir" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php
	if($canDelete) {
		$wa->addInlineScript("
			jQuery(document).ready(function () {
				jQuery('.delete-button').click(deleteItem);
			});

			function deleteItem() {

				if (!confirm(\"" . Text::_('COM_JOOMGALLERY_DELETE_MESSAGE') . "\")) {
					return false;
				}
			}
		", [], [], ["jquery"]);
	}
?>
