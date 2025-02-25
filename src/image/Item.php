<?php

namespace luya\admin\image;

use Yii;
use luya\admin\storage\ItemAbstract;
use luya\Exception;

/**
 * Image Item Detail Object.
 *
 * Each image is represent as Item-Object class.
 *
 * @property string $caption Image Caption
 * @property integer $id The unique image identifier number.
 * @property integer $fileId The file id where this image depends on.
 * @property integer $filterId The applied filter id for this image
 * @property string $source The source of the image where you can access the image by the web.
 * @property string $sourceAbsolute The absolute source of the image where you can access the image by the web.
 * @property string $serverSource The source to the image internal used on the Server.
 * @property boolean $fileExists Return boolean whether the file server source exsits on the server or not.
 * @property integer $resolutionWidth Get the image resolution width.
 * @property integer $resolutionHeight Get the image resolution height.
 * @property \luya\admin\file\Item $file The file object where the image was created from.
 * @property string $systemFileName the system file name.
 * @property string $content The the image file content.
 *
 * @author Basil Suter <basil@nadar.io>
 * @since 1.0.0
 */
class Item extends ItemAbstract
{
    private $_file;
    
    private $_caption;
    
    /**
     * Set caption for image item, override existings values
     *
     * @param string $text The caption text for this image
     * @since 1.0.0
     */
    public function setCaption($text)
    {
        $this->_caption = trim($text);
    }
    
    /**
     * Return the caption text for this image, if not defined or none give its null.
     *
     * If no image caption is defined from bind it will try to retrieve the caption from the file (set by filemanager).
     *
     * @return string The caption text for this image
     * @since 1.0.0
     */
    public function getCaption()
    {
        if ($this->_caption === null) {
            if ($this->getKey('caption', false)) {
                $this->_caption = $this->getKey('caption');
            } else {
                $this->_caption = $this->file->caption;
            }
        }
        
        return $this->_caption;
    }
    
    /**
     * The unique image identifier number.
     *
     * @return integer
     */
    public function getId()
    {
        return (int) $this->getKey('id');
    }
    
    /**
     * The file id where this image depends on.
     *
     * @return integer
     */
    public function getFileId()
    {
        return (int) $this->getKey('file_id');
    }

    /**
     * The applied filter id for this image
     *
     * @return integer
     */
    public function getFilterId()
    {
        return (int) $this->getKey('filter_id');
    }
    
    /**
     * Get the source path to the image location on the webserver.
     *
     * @param string $scheme Whether the source path should be absolute or not.
     * @return string|boolean
     */
    public function getSource($scheme = false)
    {
        if (!$this->getFileExists()) {
            if (Yii::$app->storage->autoFixMissingImageSources === false) {
                return false;
            }
            
            // The image source does not exist, probably it has been deleted due to filter changes.
            // storage component is going go try to re-create this image now.
            $apply = Yii::$app->storage->createImage($this->getFileId(), $this->getFilterId());
        }
        
        $fileName = $this->getFilterId() . '_' . $this->getFile()->getSystemFileName();
        
        return $scheme ? Yii::$app->storage->fileAbsoluteHttpPath($fileName) : Yii::$app->storage->fileHttpPath($fileName);
    }
    
    /**
     * Absolute url to the image source.
     *
     * @return string|boolean
     */
    public function getSourceAbsolute()
    {
        return $this->getSource(true);
    }
    
    /**
     * The source to the image internal used on the Server.
     *
     * @return string|boolean
     */
    public function getServerSource()
    {
        return $this->getFile() ? Yii::$app->storage->fileServerPath($this->systemFileName) : false;
    }
    
    /**
     * Return boolean value whether the file server source exsits on the server or not.
     *
     * @return boolean Whether the file still exists in the storage folder or not.
     */
    public function getFileExists()
    {
        return Yii::$app->storage->fileSystemExists($this->systemFileName);
    }
    
    /**
     * Get the filename on the filesystem.
     *
     * @return string
     * @since 1.2.0
     */
    public function getSystemFileName()
    {
        return $this->getFilterId() . '_' . $this->getFile()->getSystemFileName();
    }
    
    /**
     * Get the content of the image
     *
     * @return string
     * @since 3.7.0
     */
    public function getContent()
    {
        return Yii::$app->storage->fileSystemContent($this->systemFileName);
    }

    /**
     * Get the image resolution width in Pixel.
     *
     * @return integer Get the width in Pixel.
     */
    public function getResolutionWidth()
    {
        return (int) $this->getKey('resolution_width');
    }
    
    /**
     * Get the image resolution height in Pixel.
     *
     * @return integer Get the height in Pixel.
     */
    public function getResolutionHeight()
    {
        return (int) $this->getKey('resolution_height');
    }
    
    /**
     * Get image depending file object where the image was create from, its like the original Source
     *
     * @return \luya\admin\file\Item
     */
    public function getFile()
    {
        if ($this->_file === null) {
            $this->_file = Yii::$app->storage->getFile($this->getFileId());

            if (!$this->_file) {
                throw new Exception("The file \"$this->getFileId()\" does not exists in the storage system.");
            }
        }
        
        return $this->_file;
    }
    
    /**
     * Apply a new filter for the original ussed file and return the new created image object.
     *
     * @param string $filterName The name of a filter like `tiny-thumbnail` or a custom filter you have defined in your filters list.
     * @return boolean|\luya\admin\image\Item Returns boolean or image item object if its found.
     */
    public function applyFilter($filterName)
    {
        return ($filterItem = Yii::$app->storage->getFiltersArrayItem($filterName)) ? Yii::$app->storage->addImage($this->getFileId(), $filterItem['id'], !YII_ENV_PROD) : false;
    }
    
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return ['id', 'fileId', 'filterId', 'source', 'serverSource', 'resolutionWidth', 'resolutionHeight', 'caption'];
    }
}
