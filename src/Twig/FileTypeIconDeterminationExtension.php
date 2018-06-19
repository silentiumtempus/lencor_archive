<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;

/**
 * Class FileTypeIconDeterminationExtension
 * @package App\Twig
 */
class FileTypeIconDeterminationExtension extends AbstractExtension
{
    /**
     * @return array|\Twig_Filter[]
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('file_icon', array($this, 'fileIcon')),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'file_icon';
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function fileIcon(string $fileName)
    {
        switch (pathinfo($fileName, PATHINFO_EXTENSION)) {
            case 'txt' || 'rtf' :
                $icon = 'fa-file-text-o';
                break;
            case 'doc' || 'docx' :
                $icon = 'fa-file-word-o';
                break;
            case 'xls' || 'xlsx' :
                $icon = 'fa-file-excel-o';
                break;
            case 'pdf' :
                $icon = 'fa-file-pdf-o';
                break;
            case 'ppt' :
                $icon = 'fa-file-powerpoint-o';
                break;
            case 'rar' || 'zip' || 'tar' || 'gz' || 'bz' | '7z' :
                $icon = 'fa-file-archive-o';
                break;
            case 'jpg' || 'jpeg' || 'bmp' || 'tiff' || 'gif' :
                $icon = 'fa-file-image-o';
                break;
            case 'mp3' || 'wav' :
                $icon = 'fa-file-audio-o';
                break;
            case 'avi' || 'mkv' || 'mov' || 'bdmv' || 'mpg' || 'mpeg' :
                $icon = 'fa-video-o';
                break;
            default :
                $icon = 'fa-file-o';
                break;
        }

        return $icon;
    }
}