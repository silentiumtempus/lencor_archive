<?php

namespace App\Serializer\Denormalizer\PropertyExtractor;

use App\Entity\FactoryEntity;
use App\Entity\SettingEntity;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Class FactoryEntityPropertyExtractor
 * @package App\Serializer\Denormalizer\PropertyExtractor
 */
class FactoryEntityPropertyExtractor implements PropertyTypeExtractorInterface
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
        if (is_a($class, FactoryEntity::class, true) && 'settings' === $property) {

            return [new Type(Type::BUILTIN_TYPE_OBJECT, true, SettingEntity::class . '[]')];
        }

        return $this->reflectionExtractor->getTypes($class, $property, $context);
    }
}