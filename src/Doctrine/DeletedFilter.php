<?php
declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Class DeletedFilter
 * @package App\Doctrine
 */
class DeletedFilter extends SQLFilter
{
    /**
     * @param ClassMetadata $targetEntity
     * @param string $targetTableAlias
     * @return string
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $classes = [
            'App\Entity\User',
            'App\Entity\LogEntities\ArchiveEntryLog',
            'App\Entity\LogEntities\FactoryLog',
            'App\Entity\LogEntities\FileLog',
            'App\Entity\LogEntities\FolderLog',
            'App\Entity\LogEntities\SettingLog',
            'App\Entity\Mappings\FileCheckSumError',
            'App\Entity\Mappings\LogMappings\FileCheckSumErrorLog',
            'App\Entity\FactoryEntity',
            'App\Entity\SettingEntity'
        ];
        if (in_array($targetEntity->getReflectionClass()->name, $classes)) {

            return '';

        } else if (
            $targetEntity->getReflectionClass()->name == 'App\Entity\ArchiveEntryEntity' ||
            $targetEntity->getReflectionClass()->name == 'App\Entity\FolderEntity'
        ) {
            if ($this->getParameter('deleted') == '\'1\'') {

                return sprintf(
                    '%s.deleted = %s or %s.deleted_children > 0',
                    $targetTableAlias,
                    $this->getParameter('deleted'),
                    $targetTableAlias
                );
            }
        }

        return sprintf(
            '%s.deleted = %s',
            $targetTableAlias,
            $this->getParameter('deleted')
        );
    }
}
