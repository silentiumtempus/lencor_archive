<?php

namespace App\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class DeletedFilter extends SQLFilter
{
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
            'App\Entity\Mappings\LogMappings\FileCheckSumErrorLog'
        ];
        if (in_array($targetEntity->getReflectionClass()->name, $classes)) {

            return '';
        } else {

            return sprintf('%s.deleted = false', $targetTableAlias);
        }
    }
}