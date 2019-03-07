<?php
declare(strict_types=1);

namespace App\Serializer\Denormalizer;

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
     * @throws \Exception
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (isset($data['addTimestamp']) && is_string($data['addTimestamp'])) {
            $data['addTimestamp'] =
                $this->attributesDenormalizerService->denormalizeAttribute('addTimestamp', $data['addTimestamp']);
        }
        $userAttributes = ['addedByUser' => $data['addedByUser'], 'markedByUser' => $data['markedByUser']];
        foreach ($userAttributes as $key => $attribute) {
            $data[$key] =
                $this->attributesDenormalizerService->denormalizeAttribute($key, $attribute);
        }
        $data['requestedByUsers'] =
            $this->attributesDenormalizerService->denormalizeRequestedByUsers($data);
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
