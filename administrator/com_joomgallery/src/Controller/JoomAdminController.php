<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// No direct access
\defined('JPATH_PLATFORM') or die;

use \Joomla\CMS\MVC\Controller\AdminController as BaseAdminController;
use \Joomla\CMS\Application\CMSApplication;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use \Joomla\Input\Input;

/**
 * JoomGallery Base of Joomla Administrator Controller
 * 
 * Controller (controllers are where you put all the actual code) Provides basic
 * functionality, such as rendering views (aka displaying templates).
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class JoomAdminController extends BaseAdminController
{
  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  var $component;

  /**
	 * Constructor.
	 *
	 * @param   array                $config   An optional associative array of configuration settings.
	 *                                         Recognized key values include 'name', 'default_task', 'model_path', and
	 *                                         'view_path' (this list is not meant to be comprehensive).
	 * @param   MVCFactoryInterface  $factory  The factory.
	 * @param   CMSApplication       $app      The Application for the dispatcher
	 * @param   Input                $input    The Input object for the request
	 *
	 * @since   3.0
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

    $this->component = $this->app->bootComponent(_JOOM_OPTION);
  }

  /**
     * Execute a task by triggering a Method in the derived class.
     *
     * @param   string  $task    The task to perform. If no matching task is found, the '__default' task is executed, if
     *                           defined.
     *
     * @return  mixed   The value returned by the called Method.
     *
     * @throws  Exception
     * @since   4.2.0
     */
    public function execute($task)
    {
      // Switch for TUS server
      if($task === 'tusupload')
      {
        // Create server
        $this->component->createTusServer();
        $server = $this->component->getTusServer();

        // Run server
        $server->process(true);
      }

      // Before execution of the task
      if(!empty($task))
      {
        $this->component->msgUserStateKey = 'com_joomgallery.'.$task.'.messages';
      }
      $this->component->msgFromSession();

      // execute the task
      $res = parent::execute($task);

      // After execution of the task
      if(!$this->component->msgWithhold && $res->component->error)
      {
        $this->component->printError();
      }
      elseif(!$this->component->msgWithhold)
      {
        $this->component->printWarning();
        $this->component->printDebug();
      }

      return $res;
    }
}
