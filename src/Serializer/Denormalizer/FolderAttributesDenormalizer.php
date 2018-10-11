<?php

namespace App\Serializer\Denormalizer;

use App\Entity\FileEntity;
use App\Entity\FolderEntity;
use App\Serializer\Denormalizer\Service\AttributesDenormalizerService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Class FolderAttributesDenormalizer
 * @package App\Serializer\Denormalizer
 */
class FolderAttributesDenormalizer implements DenormalizerInterface
{
    protected $attributesDenormalizerService;

    /**
     * FolderAttributesDenormalizer constructor.
     * @param AttributesDenormalizerService $attributesDenormalizerService
     */

    public function __construct(AttributesDenormalizerService $attributesDenormalizerService)
    {
        $this->attributesDenormalizerService = $attributesDenormalizerService;
    }

    /**
     * @param mixed $data
     * @param string $class
     * @param null $format
     * @param array $context
     * @return mixed|object
     */

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $userAttributes = ['addedByUser' => $data['addedByUser'], 'markedByUser' => $data['markedByUser']];

        foreach ($userAttributes as $key => $attribute) {
            $data[$key] = $this->attributesDenormalizerService->denormalizeAttribute($key, $attribute);
        }
        if (isset($data['addTimestamp']) && is_string($data['addTimestamp'])) {
            $data['addTimestamp'] = $this->attributesDenormalizerService->denormalizeAttribute('addTimestamp', $data['addTimestamp']);
        }
        if (isset($data['childFolders']) && is_array($data['childFolders'])) {
            $normalizer = new self($this->attributesDenormalizerService);
            $childFolders = [];
            foreach ($data['childFolders'] as $childFolder) {
                $childFolders[] = $normalizer->denormalize($childFolder, FolderEntity::class);
            }
            $data['childFolders'] = $childFolders;
        }
        if (isset($data['files']) && is_array($data['files'])) {
            $normalizer = new FileAttributesDenormalizer($this->attributesDenormalizerService);
            $files = new ArrayCollection();
            foreach ($data['files'] as $file) {
                $files->add($normalizer->denormalize($file, FileEntity::class));
            }
            $data['files'] = $files;
        }
        $data['requestedByUsers'] = $this->attributesDenormalizerService->denormalizeRequestedByUsers($data);
        $normalizer = new ObjectNormalizer();

        return $normalizer->denormalize($data, $class, $format, $context);
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param null $format
     * @return bool
     */

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && ($type == self::class);
    }
}