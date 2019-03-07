<?php
declare(strict_types=1);

namespace App\Factory;

use App\Entity\FileEntity;

/**
 * Class FileFactory
 * @package App\Factory
 */
class FileFactory
{
    /**
     * @param FileEntity $fileArrayEntity
     * @param $file
     * @return FileEntity
     */
    public function createFileEntityFromArray(FileEntity $fileArrayEntity, $file)
    {
        $newFileEntity = clone $fileArrayEntity;
        $newFileEntity
            ->setFileName($file)
            ->setUploadedFiles(null);

        return $newFileEntity;
    }
}
