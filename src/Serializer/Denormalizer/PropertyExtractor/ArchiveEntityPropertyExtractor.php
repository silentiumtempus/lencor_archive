<?php

namespace App\Serializer\Denormalizer\PropertyExtractor;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FactoryEntity;
use App\Entity\FolderEntity;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

class ArchiveEntityPropertyExtractor implements PropertyTypeExtractorInterface
{
    private $reflectionExtractor;

    /**
     * FactoryEntityPropertyExtractor constructor.
     */

    public function __construct()
    {
        $this->reflectionExtractor = new ReflectionExtractor();
    }

    /**
     * @param string $class
     * @param string $property
     * @param array $context
     * @return array|null|Type[]
     */

    public function getTypes($class, $property, array $context = array())
    {
        if (is_a($class, ArchiveEntryEntity::class, true) && 'cataloguePath' === $property) {

            /*set_include_path('/var/www/archive/public_html/public/');
            $file = 'test.txt';
            $wr = file_get_contents($file);
            $wr = $wr . 'Factory property: ' . implode(',',$context). "!!!!!!!!!!!!!!" . "\n\n";
            //$wr = $wr . $newFolder>get('parentFolder')->getViewData() . "!!!!!!!!!!!!!!" . "\n\n";
            file_put_contents($file, $wr);
            */
            return [new Type(Type::BUILTIN_TYPE_OBJECT, true, FolderEntity::class)];
        }

        return $this->reflectionExtractor->getTypes($class, $property, $context);
    }

}