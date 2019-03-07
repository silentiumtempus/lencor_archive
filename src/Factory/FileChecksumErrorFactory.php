<?php
declare(strict_types=1);

namespace App\Factory;

use App\Entity\FileEntity;
use App\Entity\Mappings\FileChecksumError;

/**
 * Class FileChecksumErrorFactory
 * @package App\Factory
 */
class FileChecksumErrorFactory
{
    /**
     * @param FileEntity $fileEntity
     * @param int $userId
     * @return FileChecksumError
     * @throws \Exception
     */
    public function prepareChecksumError(FileEntity $fileEntity, int $userId)
    {
        $newFileError = new FileChecksumError();
        $newFileError
            ->setFileId($fileEntity)
            ->setParentFolderId($fileEntity->getParentFolder())
            ->setStatus(1)
            ->setLastCheckByUser($userId)
            ->setLastCheckOn(new \DateTime());

        return $newFileError;
    }

}
