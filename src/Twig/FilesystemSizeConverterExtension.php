<?php
declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;

/**
 * Class FileSizeConverterExtension
 * @package App\Twig
 */
class FilesystemSizeConverterExtension extends AbstractExtension
{
    /**
     * @return array|\Twig_Filter[]
     */
    public function getFilters(): array
    {
        return array(
            new \Twig_SimpleFilter('bytes_convert', array($this, 'bytesConvert')),
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'bytes_convert';
    }

    /**
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function bytesConvert(int $bytes, int $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
