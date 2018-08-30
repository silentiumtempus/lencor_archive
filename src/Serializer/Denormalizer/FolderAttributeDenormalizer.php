<?php

namespace App\Serializer\Denormalizer;

use App\Entity\FolderEntity;
use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class FolderAttributeDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $userAttributes = ['addedByUser' => $data['addedByUser'], 'markedByUser' => $data['markedByUser']];

        foreach ($userAttributes as $key => $attribute) {
            if (isset($attribute) && is_string($attribute)) {
                $user = new User();
                $user->setUsername($attribute);
                $data[$attribute] = $user;
            }
        }
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && ($type == FolderEntity::class);
    }
}