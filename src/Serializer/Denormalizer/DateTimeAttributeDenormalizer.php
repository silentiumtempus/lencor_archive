<?php

namespace App\Serializer\Denormalizer;

use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Class DateTimeAttributeDenormalizer
 * @package App\Serializer\Denormalizer
 */
class DateTimeAttributeDenormalizer implements DenormalizerInterface
{
    /**
     * @param mixed $data
     * @param string $class
     * @param null $format
     * @param array $context
     * @return object
     */

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (isset($data['lastLogin']) && is_string($data['lastLogin'])) {
            $data['lastLogin'] = new \DateTime($data['lastLogin']);
        }

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
        return is_array($data) && ($type == User::class);
    }

}