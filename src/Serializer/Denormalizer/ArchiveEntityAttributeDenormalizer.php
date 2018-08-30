<?php

namespace App\Serializer\Denormalizer;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FactoryEntity;
use App\Entity\FolderEntity;
use App\Entity\SettingEntity;
use App\Entity\User;
use App\Serializer\Denormalizer\PropertyExtractor\ArchiveEntityPropertyExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ArchiveEntityAttributeDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (isset($data['factory']) && is_string($data['factory'])) {
            $factory = new FactoryEntity();
            $factory->setFactoryName($data['factory']);
            $data['factory'] = $factory;
        }
        if (isset($data['setting']) && is_string($data['setting'])) {
            $factory = new SettingEntity();
            $factory->setSettingName($data['setting']);
            $data['setting'] = $factory;
        }

        set_include_path('/var/www/archive/public_html/public/');
        $file = 'test.txt';
        $wr = file_get_contents($file);
        $wr = $wr . 'CataloguePath: ' . $data['cataloguePath']['folderName'] . "!!!!!!!!!!!!!!" . "\n\n";
        //$wr = $wr . $newFolder>get('parentFolder')->getViewData() . "!!!!!!!!!!!!!!" . "\n\n";
        file_put_contents($file, $wr);

        //if (isset($data['cataloguePath']) && is_array($data['cataloguePath'])) {
            //$encoder = new JsonEncoder();
            //$normalizer = new FolderAttributeDenormalizer();
            //$normalizer->setSerializer(new Serializer($normalizer, [$encoder]));
            //$normalizer->setCircularReferenceHandler(function ($object) {

             //   return $object->__toString();
            // });
            //$folder = $normalizer->denormalize($data['cataloguePath'], FolderEntity::class ,'json');
            $serializer = new Serializer(
                array(new FolderAttributeDenormalizer(), new ObjectNormalizer(null, null, null, new ArchiveEntityPropertyExtractor()), new ArrayDenormalizer()),
                array(new JsonEncoder()));
            $data['cataloguePath'] = $serializer->deserialize(json_encode($data['cataloguePath']), FolderEntity::class, 'json');


            //$folder->set($data['cataloguePath']);
            //$data['cataloguePath'] = $folder;
            //return null;
        //}

        $userAttributes = ['markedByUser' => $data['markedByUser'], 'modifiedByUser' => $data['modifiedByUser']];

        foreach ($userAttributes as $key => $attribute) {
            if (isset($attribute) && is_string($attribute)) {
                $user = new User();
                $user->setUsername($attribute);
                $data[$key] = $user;
            }
        }

        if (isset($data['requestedByUsers']) && is_string($data['requestedByUsers'])) {
            $users = [];
            if ($data['requestedByUsers'] !== "") {
                if (strpos($data['requestedByUsers'], ',') !== false) {
                    $usernames = explode(',', $data['requestedByUsers']);
                } else {
                    $usernames[] = $data['requestedByUsers'];
                }
                foreach ($usernames as $requestedByUser) {
                    $user = new User();
                    $user->setUsername($requestedByUser);
                    $users[] = $user;
                }
                $data['requestedByUsers'] = $users;
            } else {
                $data['requestedByUsers'] = null;
            }
        }

        if (isset($data['lastModified']) && is_string($data['lastModified'])) {
            $data['lastModified'] = new \DateTime($data['lastModified']);
        }

        $normalizer = new ObjectNormalizer();

        return $normalizer->denormalize($data, $class, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && ($type == ArchiveEntryEntity::class);
    }
}