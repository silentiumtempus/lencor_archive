<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;

/**
 * Class FilesystemPermissionsConverterExtension
 * @package App\Twig
 */
class FilesystemPermissionsConverterExtension extends AbstractExtension
{
    /**
     * @return array|\Twig_Filter[]
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('perms_convert', array($this, 'permsConvert')),
        );
    }

    /**
     * @return string
     */
    public function getName()
    {

        return 'perms_convert';
    }

    /**
     * @param string $perms
     * @return string
     */
    function permsConvert(string $perms)
    {
        return substr(sprintf('%o', $perms), -4);
    }
}