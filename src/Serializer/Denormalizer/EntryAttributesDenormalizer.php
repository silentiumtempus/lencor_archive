<?php

namespace App\Serializer\Denormalizer;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FolderEntity;
use App\Serializer\Denormalizer\Service\AttributesDenormalizerService;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class EntryAttributesDenormalizer implements DenormalizerInterface
{
    protected $attributesDenormalizerService;

    public function __construct(AttributesDenormalizerService $attributesDenormalizerService)
    {
        $this->attributesDenormalizerService = $attributesDenormalizerService;
    }

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (isset($data['factory']) && is_string($data['factory'])) {
            $data['factory'] = $this->attributesDenormalizerService->denormalizeAttribute('factory', $data['factory']);
        }
        if (isset($data['setting']) && is_string($data['setting'])) {
            $data['setting'] = $this->attributesDenormalizerService->denormalizeAttribute('setting', $data['setting']);
        }
        $userAttributes = ['markedByUser' => $data['markedByUser'], 'modifiedByUser' => $data['modifiedByUser']];
        foreach ($userAttributes as $key => $attribute) {
            $data[$key] = $this->attributesDenormalizerService->denormalizeAttribute($key, $attribute);
        }
        $data['requestedByUsers'] = $this->attributesDenormalizerService->denormalizeRequestedByUsers($data);
        if (isset($data['lastModified']) && is_string($data['lastModified'])) {
            $data['lastModified'] = $this->attributesDenormalizerService->denormalizeAttribute('lastModified', $data['lastModified']);
        }
        if (isset($data['cataloguePath']) && is_array($data['cataloguePath'])) {
            $normalizer = new FolderAttributesDenormalizer($this->attributesDenormalizerService);
            $data['cataloguePath'] = $normalizer->denormalize($data['cataloguePath'], FolderEntity::class);
        }
        $normalizer = new ObjectNormalizer();

        return $normalizer->denormalize($data, $class, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && ($type == ArchiveEntryEntity::class);
    }
}