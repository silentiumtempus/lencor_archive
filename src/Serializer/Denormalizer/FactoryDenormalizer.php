<?php

namespace App\Serializer\Denormalizer;

use App\Entity\FactoryEntity;
use App\Entity\SettingEntity;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class FactoryDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $factory = new FactoryEntity();
        $normalizer = new ObjectNormalizer();

        $settings = $normalizer->denormalize(
            $data->settings,
            SettingEntity::class,
            $format,
            $context
        );

        foreach ($settings as $setting) {
            $factory->addSettings($setting);
        }
        $factory->setDeleted($data->deleted);
        $factory->setFactoryName($data->factoryName);

    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && ($type == FactoryEntity::class);
    }

}