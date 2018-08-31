<?php

namespace App\Serializer\Denormalizer;


use App\Entity\FileEntity;
use App\Serializer\Denormalizer\Service\AttributesDenormalizerService;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Class FileAttributesDenormalizer
 * @package App\Serializer\Denormalizer
 */
class FileAttributesDenormalizer implements DenormalizerInterface
{
    protected $attributesDenormalizerService;

    /**
     * FileAttributesDenormalizer constructor.
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
     * @return object
     */

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (isset($data['addTimestamp']) && is_string($data['addTimestamp'])) {
            $data['addTimestamp'] = $this->attributesDenormalizerService->denormalizeAttribute('addTimestamp', $data['addTimestamp']);
        }
        $userAttributes = ['addedByUser' => $data['addedByUser'], 'markedByUser' => $data['markedByUser']];
        foreach ($userAttributes as $key => $attribute) {
            $data[$key] = $this->attributesDenormalizerService->denormalizeAttribute($key, $attribute);
        }
        $data['requestedByUsers'] = $this->attributesDenormalizerService->denormalizeRequestedByUsers($data);

        $normalizer = new ObjectNormalizer();

/*set_include_path('/var/www/archive/public_html/public/');
$file = 'test.txt';
$wr = file_get_contents($file);
$wr = $wr . 'array: ' . json_encode($data, JSON_PRETTY_PRINT);
//$wr = $wr . 'files: ' . json_encode($files, JSON_PRETTY_PRINT) . "\n\n";
file_put_contents($file, $wr);
*/
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
        return is_array($data) && ($type == FileEntity::class);
    }

}