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

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomla\CMS\Filesystem\Path as JPath;
use \Joomla\CMS\Filter\InputFilter;
use \Joomla\CMS\Object\CMSObject;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;

/**
* Base class for the Uploader helper classes
*
* @since  4.0.0
*/
abstract class Uploader implements UploaderInterface
{
  use ServiceTrait;

  /**
   * Set to true if a error occured
   *
   * @var bool
   */
  public $error = false;

  /**
   * Holds the key of the user state variable to be used
   *
   * @var string
   */
  protected $userStateKey = 'com_joomgallery.image.upload';

  /**
   * Counter for the number of files alredy uploaded
   *
   * @var int
   */
  public $filecounter = 0;

  /**
   * The ID of the category in which
   * the images shall be uploaded
   *
   * @var int
   */
  public $catid = 0;

  /**
   * The title of the image if the original
   * file name shouldn't be used
   *
   * @var string
   */
  public $imgtitle = '';

  /**
   * Name of the used filesystem
   *
   * @var string
   */
  protected $filesystem_type = 'localhost';

  /**
   * Set to true if it is a multiple upload
   *
   * @var bool
   */
  protected $multiple = false;

  /**
   * Constructor
   * 
   * @param   bool   $multiple     True, if it is a multiple upload  (default: false)
   *
   * @return  void
   *
   * @since   1.0.0
   */
  public function __construct($multiple=false)
  {
    // Load application
    $this->getApp();
    
    // Load component
    $this->getComponent();

    $this->component->createConfig();

    $this->multiple    = $multiple;

    $this->error       = $this->app->getUserStateFromRequest($this->userStateKey.'.error', 'error', false, 'bool');
    $this->catid       = $this->app->getUserStateFromRequest($this->userStateKey.'.catid', 'catid', 0, 'int');
    $this->imgtitle    = $this->app->getUserStateFromRequest($this->userStateKey.'.imgtitle', 'imgtitle', '', 'string');
    $this->filecounter = $this->app->getUserStateFromRequest($this->userStateKey.'.filecounter', 'filecounter', 0, 'post', 'int');

    $this->component->addDebug($this->app->getUserStateFromRequest($this->userStateKey.'.debugoutput', 'debugoutput', '', 'string'));
    $this->component->addWarning($this->app->getUserStateFromRequest($this->userStateKey.'.warningoutput', 'warningoutput', '', 'string'));
  }

  /**
   * Override form data with image metadata
   * according to configuration. Step 2.
   *
   * @param   array   $data       The form data (as a reference)
   * 
   * @return  bool    True on success, false otherwise
   * 
   * @since   1.5.7
   */
  public function overrideData(&$data): bool
  {
    // Get image extension
    $tag = strtolower(JFile::getExt($this->src_file));

    if(!($tag == 'jpg' || $tag == 'jpeg' || $tag == 'jpe' || $tag == 'jfif'))
    {
      // Check for the right file-format, else throw warning
      $this->component->addWarning(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_READ_METADATA'));

      return true;
    }

    // Create the IMGtools service
    $this->component->createIMGtools($this->component->getConfig()->get('jg_imgprocessor'));

    // Get image metadata (source)
    $metadata = $this->component->getIMGtools()->readMetadata($this->src_file);

    // Add image metadata to data
    $data['imgmetadata'] = \json_encode($metadata);

    // Check if there is something to override
    if(!\property_exists($this->component->getConfig()->get('jg_replaceinfo'), 'jg_replaceinfo0'))
    {
      // Destroy the IMGtools service
      $this->component->delIMGtools();
      
      return true;
    }

    // Load dependencies
    $filter = InputFilter::getInstance();
    require_once JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/iptcarray.php';
    require_once JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/exifarray.php';

    $lang = Factory::getLanguage();
    $lang->load(_JOOM_OPTION.'.exif', JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION);
    $lang->load(_JOOM_OPTION.'.iptc', JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION);

    // Loop through all replacements defined in config
    foreach ($this->component->getConfig()->get('jg_replaceinfo') as $replaceinfo)
    {
      $source_array = \explode('-', $replaceinfo->source);

      // Get matadata value from image
      switch ($source_array[0])
      {
        case 'IFD0':
          // 'break' intentionally omitted
        case 'EXIF':
          // Get exif source attribute
          if(isset($exif_config_array[$source_array[0]]) && isset($exif_config_array[$source_array[0]][$source_array[1]]))
          {
            $source = $exif_config_array[$source_array[0]][$source_array[1]];
          }
          else
          {
            // Unknown source
            continue 2;
          }

          $source_attribute = $source['Attribute'];
          $source_name      = $source['Name'];

          // Get matadata value
          if(isset($metadata['exif'][$source_array[0]]) && isset($metadata['exif'][$source_array[0]][$source_attribute])
              && !empty($metadata['exif'][$source_array[0]][$source_attribute]))
          {
            $source_value = $metadata['exif'][$source_array[0]][$source_attribute];
          }
          else
          {
            // Matadata value not available in image
            if($this->component->getConfig()->get('jg_replaceshowwarning') > 0)
            {
              if($source_attribute == 'DateTimeOriginal')
              {
                $this->component->addWarning(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA_IMGDATE', Text::_($source_name)));
              }
              else
              {
                $this->component->addWarning(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA', Text::_($source_name)));
              }
            }           

            continue 2;
          }          
          break;

        case 'COMMENT':
          // Get metadata value
          if(isset($metadata['comment']) && !empty($metadata['comment']))
          {
            $source_value = $metadata['comment'];
          }
          else
          {
            // Matadata value not available in image
            if($this->component->getConfig()->get('jg_replaceshowwarning') > 0)
            {
              $this->component->addWarning(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA', Text::_('COM_JOOMGALLERY_COMMENT')));
            }

            continue 2;
          }
          break;

        case 'IPTC':
          // Get iptc source attribute
          if(isset($iptc_config_array[$source_array[0]]) && isset($iptc_config_array[$source_array[0]][$source_array[1]]))
          {
            $source = $iptc_config_array[$source_array[0]][$source_array[1]];
          }
          else
          {
            // Unknown source
            continue 2;
          }

          $source_attribute = $source['IMM'];
          $source_name      = $source['Name'];

          // Adjust iptc source attribute
          $source_attribute = \str_replace(':', '#', $source_attribute);

          // Get matadata value 
          if(isset($metadata['iptc'][$source_attribute]) && !empty($metadata['iptc'][$source_attribute]))
          {
            $source_value = $metadata['iptc'][$source_attribute];
          }
          else
          {
            // Matadata value not available in image
            if($this->component->getConfig()->get('jg_replaceshowwarning') > 0)
            {
              $this->component->addWarning(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA', Text::_($source_name)));
            }

            continue 2;
          }
          break;
        
        default:
          // Unknown metadata source
          continue 2;
          break;
      }


      if($this->component->getConfig()->get('jg_replaceshowwarning') == 2)
      {
        $this->component->addWarning(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_UPLOAD_REPLACE_METAHINT'));
      }

      // Replace target with metadata value
      if($replaceinfo->target == 'tags')
      {
        //TODO: Add tags based on metadata
      }
      else
      {
        $data[$replaceinfo->target] = $filter->clean($source_value, 'string');
        $this->component->addWarning(Text::_('COM_JOOMGALLERY_SERVICE_DEBUG_REPLACE_' . \strtoupper($replaceinfo->target)));
      }
    }

    // Destroy the IMGtools service
    $this->component->delIMGtools();

    return true;
  }

  /**
   * Rollback an erroneous upload
   * 
   * @param   CMSObject   $data_row     Image object containing at least catid and filename (default: false)
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function rollback($data_row=false)
  {
    if($data_row)
    {
      // Create file manager service
      $this->component->createFileManager();

      // Delete just created images
      $this->component->getFileManager()->deleteImages($data_row);
    }

    // Delete temp image
    if(isset($this->src_file) && !empty($this->src_file) && \file_exists($this->src_file))
    {
      JFile::delete($this->src_file);
    }

    $this->resetUserStates();
  }

  /**
   * Returns the number of images of the current user
   *
   * @param   $userid  Id of the current user
   *
   * @return  int      The number of images of the current user
   *
   * @since   1.5.5
   */
  protected function getImageNumber($userid)
  {
    $db = Factory::getDbo();

    $query = $db->getQuery(true)
          ->select('COUNT(id)')
          ->from(_JOOM_TABLE_IMAGES)
          ->where('created_by = '.$userid);

    $timespan = $this->component->getConfig()->get('jg_maxuserimage_timespan');
    if($timespan > 0)
    {
      $query->where('imgdate > (UTC_TIMESTAMP() - INTERVAL '. $timespan .' DAY)');
    }

    $db->setQuery($query);

    return $db->loadResult();
  }

  /**
   * Calculates the serial number for images file names and titles
   *
   * @return  int       New serial number
   *
   * @since   1.0.0
   */
  protected function getSerial()
  {
    // Check if the initial value is already calculated
    if(isset($this->filecounter))
    {
      $this->filecounter++;

      // Store the next value in the session
      $this->app->setUserState($this->userStateKey.'.filecounter', $this->filecounter + 1);

      return $this->filecounter;
    }

    // If there is no starting value set, disable numbering
    if(!$this->filecounter)
    {
      return null;
    }

    // No negative starting value
    if($this->filecounter < 0)
    {
      $picserial = 1;
    }
    else
    {
      $picserial = $this->filecounter;
    }

    return $picserial;
  }

  /**
   * Resets user states
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  protected function resetUserStates()
  {
    // Reset file counter, delete original and create special gif selection and debug information
    $this->app->setUserState($this->userStateKey.'.filecounter', 0);
    $this->app->setUserState($this->userStateKey.'.error', false);
    $this->app->setUserState($this->userStateKey.'.debugoutput', null);
    $this->app->setUserState($this->userStateKey.'.warningoutput', null);
  }

  /**
   * Creation of a temporary image object for the rollback
   * 
   * @param   array   $data      The form data
   * 
   * @return  CMSObject
   * 
   * @since   4.0.0
   */
  protected function tempImgObj($data)
  {
    if(!\key_exists('catid', $data) || !empty($data['catid']) || !\key_exists('filename', $data) || !empty($data['filename']))
    {
      throw new \Exception('Form data must have at least catid and filename');
    }

    $img = new CMSObject;

    $img->set('catid', $data['catid']);
    $img->set('filename', $data['filename']);

    return $img;
  }
}
