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
            $data['factory'] = $this->attributesDenormalizerService->denormalizeAttribute('factory',  $data['factory']);
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


        //if (isset($data['cataloguePath']) && is_array($data['cataloguePath'])) {
        //$encoder = new JsonEncoder();
        $normalizer = new FolderAttributesDenormalizer($this->attributesDenormalizerService);
        //$normalizer->setSerializer(new Serializer($normalizer, [$encoder]));
        //$normalizer->setCircularReferenceHandler(function ($object) {

        //   return $object->__toString();
        // });
        $folder = $normalizer->denormalize($data['cataloguePath'], FolderEntity::class);
        //$serializer = new Serializer(
        //array(new FolderAttributesDenormalizer(), new ObjectNormalizer(null, null, null, new ArchiveEntityPropertyExtractor()), new ArrayDenormalizer()),
        //array(new JsonEncoder()));
        //$data['cataloguePath'] = $serializer->deserialize(json_encode($data['cataloguePath']), FolderEntity::class, 'json');
        //$folder->set($data['cataloguePath']);
        $data['cataloguePath'] = $folder;
        /*set_include_path('/var/www/archive/public_html/public/');
        $file = 'test.txt';
        $wr = file_get_contents($file);
        $wr = $wr . 'CataloguePath: ' . $folder->getAddedByUser() . "!!!!!!!!!!!!!!" . "\n\n";
        //$wr = $wr . $newFolder>get('parentFolder')->getViewData() . "!!!!!!!!!!!!!!" . "\n\n";
        file_put_contents($file, $wr); */
        //return null;
        //}
        $normalizer = new ObjectNormalizer();

        return $normalizer->denormalize($data, $class, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && ($type == ArchiveEntryEntity::class);
    }
}