<?php

declare(strict_types=1);

namespace Graywings\Instantiate\Tests\Unit\PropertyNode;

use Graywings\Instantiate\PropertyNode\PropertyNode;
use Graywings\Instantiate\Tests\Sample\PropertyNode\Address;
use Graywings\Instantiate\Tests\Sample\PropertyNode\User;
use Graywings\Instantiate\Tests\Sample\PropertyNode\ComplexTypes;
use Graywings\Instantiate\Type\BuiltinType;
use Graywings\Instantiate\Type\BuiltinTypeEnum;
use Graywings\Instantiate\Type\UserDefinedType;
use Graywings\Instantiate\Type\ArrayType;
use Graywings\Instantiate\Type\AlgebraicDataType;
use Graywings\Instantiate\Type\AlgebraicOperator;
use Graywings\Instantiate\PropertyNode\ArrayOf;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PropertyNode::class)]
#[CoversClass(BuiltinType::class)]
#[CoversClass(BuiltinTypeEnum::class)]
#[CoversClass(UserDefinedType::class)]
#[CoversClass(ArrayType::class)]
#[CoversClass(ArrayOf::class)]
#[CoversClass(AlgebraicDataType::class)]
#[CoversClass(AlgebraicOperator::class)]
class PropertyNodeTest extends TestCase
{
    public function testBuildFromClassThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Class 'NonExistentClass' does not exist");

        PropertyNode::buildFromClass('NonExistentClass');
    }

    public function testBuildFromClassWithPublicProperties(): void
    {
        $propertyNodes = PropertyNode::buildFromClass(Address::class, true); // Use public properties

        $this->assertCount(5, $propertyNodes);

        // Check individual properties
        $streetNode = $this->findNodeByName($propertyNodes, 'street');
        $this->assertNotNull($streetNode);
        $this->assertSame('street', $streetNode->getName());
        $this->assertInstanceOf(BuiltinType::class, $streetNode->getType());
        $this->assertSame('string', $streetNode->getType()->getName());
        $this->assertFalse($streetNode->isNullable());

        $stateNode = $this->findNodeByName($propertyNodes, 'state');
        $this->assertNotNull($stateNode);
        $this->assertTrue($stateNode->isNullable());
    }

    public function testBuildFromClassWithConstructorParameters(): void
    {
        $propertyNodes = PropertyNode::buildFromClass(Address::class); // Default: use constructor parameters

        $this->assertCount(5, $propertyNodes);

        // Check individual parameters
        $streetNode = $this->findNodeByName($propertyNodes, 'street');
        $this->assertNotNull($streetNode);
        $this->assertSame('street', $streetNode->getName());
        $this->assertInstanceOf(BuiltinType::class, $streetNode->getType());
        $this->assertSame('string', $streetNode->getType()->getName());
        $this->assertFalse($streetNode->isNullable());

        $stateNode = $this->findNodeByName($propertyNodes, 'state');
        $this->assertNotNull($stateNode);
        $this->assertTrue($stateNode->isNullable());

        $countryNode = $this->findNodeByName($propertyNodes, 'country');
        $this->assertNotNull($countryNode);
        $this->assertFalse($countryNode->isNullable()); // Not nullable in constructor
    }

    public function testBuildFromClassWithNestedPublicProperties(): void
    {
        $propertyNodes = PropertyNode::buildFromClass(User::class, true); // Use public properties

        $this->assertCount(8, $propertyNodes);

        // Check the address property which should have child nodes
        $addressNode = $this->findNodeByName($propertyNodes, 'address');
        $this->assertNotNull($addressNode);
        $this->assertInstanceOf(UserDefinedType::class, $addressNode->getType());
        $this->assertSame(Address::class, $addressNode->getType()->getName());

        // Check that address has child properties
        $children = $addressNode->getChildren();
        $this->assertCount(5, $children);

        // Check roles property (array of strings specified via ArrayOf attribute)
        $rolesNode = $this->findNodeByName($propertyNodes, 'roles');
        $this->assertNotNull($rolesNode);
        $this->assertInstanceOf(ArrayType::class, $rolesNode->getType());
        $elementType = $rolesNode->getType()->getElementType();
        $this->assertInstanceOf(BuiltinType::class, $elementType);
        $this->assertSame('string', $elementType->getName());

        // Check additional addresses property (array of Address objects specified via ArrayOf attribute)
        $additionalAddressesNode = $this->findNodeByName($propertyNodes, 'additionalAddresses');
        $this->assertNotNull($additionalAddressesNode);
        $this->assertInstanceOf(ArrayType::class, $additionalAddressesNode->getType());
        $elementType = $additionalAddressesNode->getType()->getElementType();
        $this->assertInstanceOf(UserDefinedType::class, $elementType);
        $this->assertSame(Address::class, $elementType->getName());

        // Check that additionalAddresses has child nodes (from Address class)
        $children = $additionalAddressesNode->getChildren();
        $this->assertCount(5, $children);
    }

    public function testToArray(): void
    {
        $type = new BuiltinType('string');
        $node = new PropertyNode('name', $type);

        $expected = [
            'name' => 'name',
            'type' => 'string',
            'nullable' => false,
        ];

        $this->assertSame($expected, $node->toArray());

        // Add a child node
        $childNode = new PropertyNode('child', new BuiltinType('int'));
        $node->addChild($childNode);

        $expected['children'] = [
            [
                'name' => 'child',
                'type' => 'int',
                'nullable' => false,
            ]
        ];

        $this->assertSame($expected, $node->toArray());
    }

    public function testBuildFromClassWithConstructorParametersOfUser(): void
    {
        $propertyNodes = PropertyNode::buildFromClass(User::class); // Default: use constructor parameters

        // User constructor has 8 parameters
        $this->assertCount(8, $propertyNodes);

        // Note that the parameter order is different from the property order
        // First parameter is "name" (not "id" as in the properties)
        $nameNode = $this->findNodeByName($propertyNodes, 'name');
        $this->assertNotNull($nameNode);
        $this->assertSame(0, array_search($nameNode, $propertyNodes)); // First parameter

        // Check array parameters with attributes
        $rolesNode = $this->findNodeByName($propertyNodes, 'roles');
        $this->assertNotNull($rolesNode);
        $this->assertInstanceOf(ArrayType::class, $rolesNode->getType());
        $elementType = $rolesNode->getType()->getElementType();
        $this->assertInstanceOf(BuiltinType::class, $elementType);
        $this->assertSame('string', $elementType->getName());

        // Check address parameter (complex type)
        $addressNode = $this->findNodeByName($propertyNodes, 'address');
        $this->assertNotNull($addressNode);
        $this->assertInstanceOf(UserDefinedType::class, $addressNode->getType());
        $this->assertSame(Address::class, $addressNode->getType()->getName());

        // Check that address has child nodes from constructor parameters
        $children = $addressNode->getChildren();
        $this->assertCount(5, $children);
    }

    public function testBuildFromClassWithComplexTypes(): void
    {
        $propertyNodes = PropertyNode::buildFromClass(ComplexTypes::class, true); // Use public properties

        // Check union type properties
        $stringOrIntNode = $this->findNodeByName($propertyNodes, 'stringOrInt');
        $this->assertNotNull($stringOrIntNode);
        $this->assertInstanceOf(AlgebraicDataType::class, $stringOrIntNode->getType());
        $this->assertSame(AlgebraicOperator::UNION, $stringOrIntNode->getType()->getOperator());
        $this->assertSame('string|int', $stringOrIntNode->getType()->getName());

        // Check nullable union type
        $stringOrFloatOrNullNode = $this->findNodeByName($propertyNodes, 'stringOrFloatOrNull');
        $this->assertNotNull($stringOrFloatOrNullNode);
        $this->assertTrue($stringOrFloatOrNullNode->isNullable());

        // Check union type with user-defined types
        $addressOrUserNode = $this->findNodeByName($propertyNodes, 'addressOrUser');
        $this->assertNotNull($addressOrUserNode);
        $this->assertInstanceOf(AlgebraicDataType::class, $addressOrUserNode->getType());

        // Check intersection type properties
        $countableAndIteratorNode = $this->findNodeByName($propertyNodes, 'countableAndIterator');
        $this->assertNotNull($countableAndIteratorNode);
        $this->assertInstanceOf(AlgebraicDataType::class, $countableAndIteratorNode->getType());
        $this->assertSame(AlgebraicOperator::INTERSECTION, $countableAndIteratorNode->getType()->getOperator());
        $this->assertSame('Countable&Iterator', $countableAndIteratorNode->getType()->getName());

        // Check array with complex element types (via ArrayOf attribute)
        $mixedArrayNode = $this->findNodeByName($propertyNodes, 'mixedArray');
        $this->assertNotNull($mixedArrayNode);
        $this->assertInstanceOf(ArrayType::class, $mixedArrayNode->getType());
        $elementType = $mixedArrayNode->getType()->getElementType();
        $this->assertInstanceOf(AlgebraicDataType::class, $elementType);
        $this->assertSame(AlgebraicOperator::UNION, $elementType->getOperator());

        // Check nested complex types in arrays
        $complexObjectsNode = $this->findNodeByName($propertyNodes, 'complexObjects');
        $this->assertNotNull($complexObjectsNode);
        $this->assertInstanceOf(ArrayType::class, $complexObjectsNode->getType());
        $elementType = $complexObjectsNode->getType()->getElementType();
        $this->assertInstanceOf(AlgebraicDataType::class, $elementType);
    }

    /**
     * Helper method to find a node by name
     *
     * @param  PropertyNode[] $nodes
     * @param  string         $name
     * @return PropertyNode|null
     */
    private function findNodeByName(array $nodes, string $name): ?PropertyNode
    {
        foreach ($nodes as $node) {
            if ($node->getName() === $name) {
                return $node;
            }
        }

        return null;
    }
}
