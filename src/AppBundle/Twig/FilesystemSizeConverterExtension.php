<?php

namespace AppBundle\Twig;

use Twig\Extension\AbstractExtension;

/**
 * Class FileSizeConverterExtension
 * @package AppBundle\Twig
 */
class FilesystemSizeConverterExtension extends AbstractExtension
{
    /**
     * @return array|\Twig_Filter[]
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('bytes_convert', array($this, 'bytesConvert')),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {

        return 'bytes_convert';
    }

    /**
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    function bytesConvert(int $bytes, int $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}