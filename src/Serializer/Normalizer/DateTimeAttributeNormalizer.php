<?php

namespace App\Serializer\Normalizer;


use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class DateTimeAttributeNormalizer implements DenormalizerInterface
{

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (isset($data['lastLogin']) && is_string($data['lastLogin'])) {
            $data['lastLogin'] = new \DateTime($data['lastLogin']);
        }

        $normalizer = new ObjectNormalizer();

        return $normalizer->denormalize($data, $class, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && ($type == User::class);
    }

}