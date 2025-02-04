<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

\defined('JPATH_PLATFORM') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\SingleUploader;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\AjaxUploader;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\BatchUploader;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\FTPUploader;

/**
* Trait to implement UploaderServiceInterface
*
* @since  4.0.0
*/
trait UploaderServiceTrait
{
  /**
	 * Storage for the Uploader class.
	 *
	 * @var UploaderInterface
	 *
	 * @since  4.0.0
	 */
	private $uploader = null;

  /**
	 * Returns the Uploader helper class.
	 *
	 * @return  UploaderInterface
	 *
	 * @since  4.0.0
	 */
	public function getUploader(): UploaderInterface
	{
		return $this->uploader;
	}

    /**
	 * Creates the Uploader helper class based on the selected upload method
	 *
     * @param   string  $uploadMethod   Name of the upload method to be used
	 * @param   bool    $multiple       True, if it is a multiple upload (default: false)
	 *
     * @return  void
     *
	 * @since  4.0.0
	 */
	public function createUploader($uploadMethod, $multiple=false): void
	{
    switch ($uploadMethod)
    {
      case 'ajax':
        $this->uploader = new AjaxUploader($multiple);
        break;

      case 'batch':
        $this->uploader = new BatchUploader($multiple);
        break;

      case 'FTP':
      case 'ftp':
        $this->uploader = new FTPUploader($multiple);
        break;

      default:
        $this->uploader = new HTMLUploader($multiple);
        break;
    }

    return;
	}
}
