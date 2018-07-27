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
            case 'txt':
            case 'rtf' :
                $icon = 'fa-file-text-o';
                break;
            case 'doc' :
            case 'docx' :
                $icon = 'fa-file-word-o';
                break;
            case 'xls' :
            case 'xlsx' :
                $icon = 'fa-file-excel-o';
                break;
            case 'pdf' :
                $icon = 'fa-file-pdf-o';
                break;
            case 'ppt' :
                $icon = 'fa-file-powerpoint-o';
                break;
            case 'rar' :
            case 'zip' :
            case 'tar' :
            case 'gz' :
            case 'bz' :
            case '7z' :
                $icon = 'fa-file-archive-o';
                break;
            case 'jpg' :
            case 'jpeg' :
            case 'bmp' :
            case 'tiff' :
            case 'gif' :
                $icon = 'fa-file-image-o';
                break;
            case 'mp3' :
            case 'wav' :
                $icon = 'fa-file-audio-o';
                break;
            case 'avi' :
            case 'mkv' :
            case 'mov' :
            case 'bdmv' :
            case 'mpg' :
            case 'mpeg' :
                $icon = 'fa-video-o';
                break;
            default :
                $icon = 'fa-file-o';
                break;
        }

        return $icon;
    }
}