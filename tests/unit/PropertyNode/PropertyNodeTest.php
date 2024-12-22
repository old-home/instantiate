<?php

namespace Graywings\Instantiate\Tests\Unit\PropertyNode;

use Graywings\Instantiate\PropertyNode\PropertyNode;
use Graywings\Instantiate\Tests\Sample\Simple\BoolProperty;
use Graywings\Instantiate\Tests\Sample\Simple\BuiltinUnionProperty;
use Graywings\Instantiate\Tests\Sample\Simple\FloatProperty;
use Graywings\Instantiate\Tests\Sample\Simple\IntProperty;
use Graywings\Instantiate\Tests\Sample\Simple\NullProperty;
use Graywings\Instantiate\Tests\Sample\Simple\StringProperty;
use Graywings\Instantiate\Type\AlgebraicDataType;
use Graywings\Instantiate\Type\AlgebraicOperator;
use Graywings\Instantiate\Type\BuiltinType;
use Graywings\Instantiate\Type\UserDefinedType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PropertyNode::class)]
#[CoversClass(AlgebraicDataType::class)]
#[CoversClass(UserDefinedType::class)]
class PropertyNodeTest extends TestCase
{
    public function test_buildTreeNullProperty()
    {
        $propertyTree = PropertyNode::buildFromClassString(NullProperty::class);
        $this->assertSame('', $propertyTree->name);
        $this->assertSame(AlgebraicOperator::PRODUCT, $propertyTree->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->algebraicDataType->types);
        $this->assertSame(NullProperty::class, $propertyTree->algebraicDataType->types[0]->name());
        $this->assertCount(1, $propertyTree->nodes);
        $this->assertSame('nullProperty', $propertyTree->nodes[0]->name);
        $this->assertSame(AlgebraicOperator::NONE, $propertyTree->nodes[0]->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->nodes[0]->algebraicDataType->types);
        $this->assertSame(BuiltinType::NULL, $propertyTree->nodes[0]->algebraicDataType->types[0]);
    }

    public function test_buildTreeStringProperty()
    {
        $propertyTree = PropertyNode::buildFromClassString(StringProperty::class);
        $this->assertSame('', $propertyTree->name);
        $this->assertSame(AlgebraicOperator::PRODUCT, $propertyTree->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->algebraicDataType->types);
        $this->assertSame(StringProperty::class, $propertyTree->algebraicDataType->types[0]->name());
        $this->assertCount(1, $propertyTree->nodes);
        $this->assertSame('value', $propertyTree->nodes[0]->name);
        $this->assertSame(AlgebraicOperator::NONE, $propertyTree->nodes[0]->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->nodes[0]->algebraicDataType->types);
        $this->assertSame(BuiltinType::STRING, $propertyTree->nodes[0]->algebraicDataType->types[0]);
    }

    public function test_buildTreeBoolProperty()
    {
        $propertyTree = PropertyNode::buildFromClassString(BoolProperty::class);
        $this->assertSame('', $propertyTree->name);
        $this->assertSame(AlgebraicOperator::PRODUCT, $propertyTree->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->algebraicDataType->types);
        $this->assertSame(BoolProperty::class, $propertyTree->algebraicDataType->types[0]->name());
        $this->assertCount(1, $propertyTree->nodes);
        $this->assertSame('value', $propertyTree->nodes[0]->name);
        $this->assertSame(AlgebraicOperator::NONE, $propertyTree->nodes[0]->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->nodes[0]->algebraicDataType->types);
        $this->assertSame(BuiltinType::BOOL, $propertyTree->nodes[0]->algebraicDataType->types[0]);
    }

    public function test_buildTreeIntProperty()
    {
        $propertyTree = PropertyNode::buildFromClassString(IntProperty::class);
        $this->assertSame('', $propertyTree->name);
        $this->assertSame(AlgebraicOperator::PRODUCT, $propertyTree->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->algebraicDataType->types);
        $this->assertSame(IntProperty::class, $propertyTree->algebraicDataType->types[0]->name());
        $this->assertCount(1, $propertyTree->nodes);
        $this->assertSame('value', $propertyTree->nodes[0]->name);
        $this->assertSame(AlgebraicOperator::NONE, $propertyTree->nodes[0]->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->nodes[0]->algebraicDataType->types);
        $this->assertSame(BuiltinType::INT, $propertyTree->nodes[0]->algebraicDataType->types[0]);
    }

    public function test_buildTreeFloatProperty()
    {
        $propertyTree = PropertyNode::buildFromClassString(FloatProperty::class);
        $this->assertSame('', $propertyTree->name);
        $this->assertSame(AlgebraicOperator::PRODUCT, $propertyTree->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->algebraicDataType->types);
        $this->assertSame(FloatProperty::class, $propertyTree->algebraicDataType->types[0]->name());
        $this->assertCount(1, $propertyTree->nodes);
        $this->assertSame('value', $propertyTree->nodes[0]->name);
        $this->assertSame(AlgebraicOperator::NONE, $propertyTree->nodes[0]->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->nodes[0]->algebraicDataType->types);
        $this->assertSame(BuiltinType::FLOAT, $propertyTree->nodes[0]->algebraicDataType->types[0]);
    }

    public function test_buildTreeBuiltinUnionProperty()
    {
        $propertyTree = PropertyNode::buildFromClassString(BuiltinUnionProperty::class);
        $this->assertSame('', $propertyTree->name);
        $this->assertSame(AlgebraicOperator::PRODUCT, $propertyTree->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->algebraicDataType->types);
        $this->assertSame(BuiltinUnionProperty::class, $propertyTree->algebraicDataType->types[0]->name());
        $this->assertCount(1, $propertyTree->nodes);
        $this->assertSame('value', $propertyTree->nodes[0]->name);
        $this->assertSame(AlgebraicOperator::UNION, $propertyTree->nodes[0]->algebraicDataType->algebraicOperator);
        $this->assertCount(6, $propertyTree->nodes[0]->algebraicDataType->types);
        $this->assertSame(BuiltinType::ARRAY, $propertyTree->nodes[0]->algebraicDataType->types[0]);
        $this->assertSame(BuiltinType::STRING, $propertyTree->nodes[0]->algebraicDataType->types[1]);
        $this->assertSame(BuiltinType::INT, $propertyTree->nodes[0]->algebraicDataType->types[2]);
        $this->assertSame(BuiltinType::FLOAT, $propertyTree->nodes[0]->algebraicDataType->types[3]);
        $this->assertSame(BuiltinType::BOOL, $propertyTree->nodes[0]->algebraicDataType->types[4]);
        $this->assertSame(BuiltinType::NULL, $propertyTree->nodes[0]->algebraicDataType->types[5]);
        $this->assertSame(AlgebraicOperator::ARRAY, $propertyTree->nodes[0]->nodes[0]->algebraicDataType->algebraicOperator);
        $this->assertCount(1, $propertyTree->nodes[0]->nodes[0]->algebraicDataType->types);
        $this->assertSame(BuiltinType::ARRAY, $propertyTree->nodes[0]->nodes[0]->algebraicDataType->types[0]);
        $this->assertCount(1, $propertyTree->nodes[0]->nodes[0]->nodes);
        $this->assertSame(BuiltinType::MIXED, $propertyTree->nodes[0]->nodes[0]->nodes[0]->algebraicDataType->types[0]);
        $this->assertCount(1, $propertyTree->nodes[0]->nodes[1]->algebraicDataType->types);
        $this->assertSame(BuiltinType::STRING, $propertyTree->nodes[0]->nodes[1]->algebraicDataType->types[0]);
        $this->assertCount(1, $propertyTree->nodes[0]->nodes[2]->algebraicDataType->types);
        $this->assertSame(BuiltinType::INT, $propertyTree->nodes[0]->nodes[2]->algebraicDataType->types[0]);
        $this->assertCount(1, $propertyTree->nodes[0]->nodes[3]->algebraicDataType->types);
        $this->assertSame(BuiltinType::FLOAT, $propertyTree->nodes[0]->nodes[3]->algebraicDataType->types[0]);
        $this->assertCount(1, $propertyTree->nodes[0]->nodes[4]->algebraicDataType->types);
        $this->assertSame(BuiltinType::BOOL, $propertyTree->nodes[0]->nodes[4]->algebraicDataType->types[0]);
        $this->assertCount(1, $propertyTree->nodes[0]->nodes[5]->algebraicDataType->types);
        $this->assertSame(BuiltinType::NULL, $propertyTree->nodes[0]->nodes[5]->algebraicDataType->types[0]);
    }
}
