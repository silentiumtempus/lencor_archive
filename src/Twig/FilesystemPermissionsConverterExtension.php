<?php
declare(strict_types=1);

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
    public function getFilters(): array
    {
        return array(
            new \Twig_SimpleFilter('perms_convert', array($this, 'permsConvert')),
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'perms_convert';
    }

    /**
     * @param string $perms
     * @return string
     */
    public function permsConvert(string $perms)
    {
        return substr(sprintf('%o', $perms), -4);
    }
}
