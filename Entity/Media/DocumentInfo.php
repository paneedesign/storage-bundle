<?php
/**
 * Created by PhpStorm.
 * User: Fabiano Roberto
 * Date: 25/10/17
 * Time: 12:34
 */

namespace PaneeDesign\StorageBundle\Entity\Media;

class DocumentInfo extends MediaInfo
{
    /**
     * @var integer page number
     */
    private $page;


    /**
     * DocumentInfo constructor.
     *
     * @param int $page
     * @param string $key
     * @param string $ext
     */
    public function __construct($page = 1, $key = '', $ext = '')
    {
        $this->page = (int) $page;

        parent::__construct($key, $ext);
    }

    public function setPage($page)
    {
        $this->page = (int) $page;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function toJSON()
    {
        return get_object_vars($this);
    }
}
