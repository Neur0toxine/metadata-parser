<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser;

use Liip\MetadataParser\Builder;
use Liip\MetadataParser\Metadata\PropertyMetadata;
use Liip\MetadataParser\Metadata\PropertyType;
use Liip\MetadataParser\Metadata\PropertyTypeClass;
use Liip\MetadataParser\ModelParser\PhpDocParser;
use Liip\MetadataParser\ModelParser\ReflectionParser;
use Liip\MetadataParser\Parser;
use Liip\MetadataParser\RecursionChecker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tests\Liip\MetadataParser\ModelParser\Model\Nested;

/**
 * @small
 */
class BuilderTest extends TestCase
{
    /**
     * @var Builder
     */
    private $builder;

    protected function setUp(): void
    {
        $parser = new Parser(
            [
                new ReflectionParser(),
                new PhpDocParser(),
            ]
        );

        $this->builder = new Builder(
            $parser,
            new RecursionChecker($this->createMock(LoggerInterface::class))
        );
    }

    public function testBuild(): void
    {
        $c = new class() {
            /**
             * @var Nested
             */
            private $property;
        };

        $classMetadata = $this->builder->build(\get_class($c));

        $props = $classMetadata->getProperties();
        static::assertCount(1, $props, 'Number of properties should match');

        $this->assertProperty('property', 'property', false, false, $props[0]);
        $this->assertPropertyType($props[0]->getType(), PropertyTypeClass::class, Nested::class, false);

        $type = $props[0]->getType();
        static::assertInstanceOf(PropertyTypeClass::class, $type);
        $nestedMetadata = $type->getClassMetadata();
        $props = $nestedMetadata->getProperties();
        static::assertCount(1, $props, 'Number of properties should match');
        $this->assertProperty('nestedProperty', 'nested_property', false, false, $props[0]);
    }

    private function assertProperty(string $name, string $serializedName, bool $public, bool $readOnly, PropertyMetadata $property): void
    {
        static::assertSame($name, $property->getName(), 'Name of property should match');
        static::assertSame($serializedName, $property->getSerializedName(), "Serialized name of property {$name} should match");
        static::assertSame($public, $property->isPublic(), "Public flag of property {$name} should match");
        static::assertSame($readOnly, $property->isReadOnly(), "Read only flag of property {$name} should match");
    }

    private function assertPropertyType(PropertyType $type, string $propertyTypeClass, string $typeString, bool $nullable): void
    {
        static::assertInstanceOf($propertyTypeClass, $type);
        static::assertSame($nullable, $type->isNullable());
        static::assertSame($typeString, (string) $type);
    }
}
