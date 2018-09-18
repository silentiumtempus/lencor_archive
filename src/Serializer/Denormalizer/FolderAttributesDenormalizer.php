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
        set_include_path('/var/www/archive/public_html/public/');
        $testFile = 'test.txt';
        $wr = file_get_contents($testFile);
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
            $files = [];
            foreach ($data['files'] as $file) {
                $files[] = $normalizer->denormalize($file, FileEntity::class);
                //if (count($files) == 0) {$files = new ArrayCollection();}
            }
            //$wr = $wr . 'data: ' . json_encode($data['files'], JSON_PRETTY_PRINT) . "\n\n";
            //$wr = $wr . 'denormalized: ' . json_encode($files, JSON_PRETTY_PRINT). "\n\n";
            //$data['files'] = $files;
        }
        //$wr = $wr . 'files: ' . $files[0]->getFileName() . "\n\n";
        file_put_contents($testFile, $wr);
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