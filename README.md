# Instantiate

Instantiate is a PHP library for creating objects from various data sources with proper type handling and validation. It supports creating objects from JSON strings, stdClass objects, and associative arrays, with automatic type conversion and nested object creation.

## Installation

```bash
composer require graywings/instantiate
```

## Features

- Create objects from JSON strings, stdClass objects, or associative arrays
- Automatic type conversion based on constructor parameter types
- Support for nested objects and complex data structures
- Proper handling of default parameter values
- Type validation to ensure data integrity
- Support for nullable parameters
- Support for PHP 8.1 enum types (both backed and pure enums)
- Detailed exception messages for easier debugging

## Usage

### Basic Usage

```php
use Graywings\Instantiate\Instantiate;

// From JSON string
$json = '{"id": 1, "name": "John Doe", "email": "john@example.com"}';
$user = Instantiate::json(User::class, $json);

// From associative array
$array = ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'];
$user = Instantiate::array(User::class, $array);

// From stdClass object
$object = new stdClass();
$object->id = 1;
$object->name = 'John Doe';
$object->email = 'john@example.com';
$user = Instantiate::stdClass(User::class, $object);
```

### Nested Objects

Instantiate automatically handles nested objects:

```php
$json = '
{
    "id": 1, 
    "name": "John Doe",
    "address": {
        "street": "123 Main St",
        "city": "New York",
        "zipCode": "10001"
    }
}';

$user = Instantiate::json(User::class, $json);
// $user->getAddress() will be an Address object with the specified properties
```

### Type Conversion

Instantiate automatically converts values to the expected types:

```php
$data = [
    'id' => '42',    // String will be converted to int
    'name' => 'John',
    'active' => 1    // Integer will be converted to boolean
];

$user = Instantiate::array(User::class, $data);
// $user->getId() will be an int (42)
// $user->isActive() will be a boolean (true)
```

### Enum Types

Instantiate has full support for PHP 8.1 enums:

```php
enum UserStatus: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

class User {
    public function __construct(
        private int $id,
        private string $name,
        private UserStatus $status
    ) { }
    
    // Getters...
}

// From string value matching the enum's backing value
$data1 = [
    'id' => 1,
    'name' => 'John',
    'status' => 'active' // Will be converted to UserStatus::ACTIVE
];

// From actual enum instance
$data2 = [
    'id' => 2,
    'name' => 'Jane',
    'status' => UserStatus::PENDING // Direct enum instance
];

$user1 = Instantiate::array(User::class, $data1);
$user2 = Instantiate::array(User::class, $data2);
```

### Default Values

Constructor default values are respected when the data doesn't include the parameter:

```php
class User {
    public function __construct(
        private int $id,
        private string $name,
        private ?string $email = null,
        private int $age = 30,
        private bool $active = true
    ) { }
    
    // Getters...
}

$data = [
    'id' => 1,
    'name' => 'John'
    // email, age, and active are not specified
];

$user = Instantiate::array(User::class, $data);
// $user->getEmail() will be null
// $user->getAge() will be 30
// $user->isActive() will be true
```

## Exception Handling

Instantiate throws detailed exceptions when there are issues with the data:

- `InstantiateArgumentsException`: Thrown when there are issues with the provided data, such as missing required parameters or invalid types.
- `InstantiateException`: Base exception class for all Instantiate-related exceptions.

```php
use Graywings\Instantiate\Exception\InstantiateArgumentsException;
use Graywings\Instantiate\Exception\InstantiateException;

try {
    $user = Instantiate::json(User::class, $json);
} catch (InstantiateArgumentsException $e) {
    // Handle data validation issues
    echo "Data validation error: " . $e->getMessage();
} catch (InstantiateException $e) {
    // Handle other Instantiate-related errors
    echo "Instantiation error: " . $e->getMessage();
}
```

## Requirements

- PHP 8.0 or higher
- Reflection extension enabled

## License

This library is released under the MIT License.