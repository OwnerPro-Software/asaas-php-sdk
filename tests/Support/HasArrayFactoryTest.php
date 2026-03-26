<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\HasArrayFactory;

mutates(HasArrayFactory::class);

final class FactoryTestDTO
{
    use HasArrayFactory;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['name', 'email'];
    }
}

final class FactoryTestMidDefaultDTO
{
    use HasArrayFactory;

    public function __construct(
        public readonly string $name,
        public readonly ?string $middle = null,
        public readonly string $email = 'default@test.com',
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['name'];
    }
}

final class FactoryTestNoConstructorDTO
{
    use HasArrayFactory;

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}

it('creates from array with required fields and applies defaults', function (): void {
    $dto = FactoryTestDTO::fromArray(['name' => 'John', 'email' => 'j@t.com']);

    expect($dto->name)->toBe('John');
    expect($dto->email)->toBe('j@t.com');
    expect($dto->phone)->toBeNull();
    expect(isset($dto->phone))->toBeFalse();
});

it('creates from array with optional fields', function (): void {
    $dto = FactoryTestDTO::fromArray(['name' => 'John', 'email' => 'j@t.com', 'phone' => '123']);

    expect($dto->phone)->toBe('123');
});

it('ignores extra keys not in constructor', function (): void {
    $dto = FactoryTestDTO::fromArray(['name' => 'John', 'email' => 'j@t.com', 'unknown' => 'x']);

    expect($dto->name)->toBe('John');
});

it('throws when required field is missing', function (): void {
    FactoryTestDTO::fromArray(['name' => 'John']);
})->throws(InvalidArgumentException::class, "Field 'email' is required.");

it('converts to array without null values', function (): void {
    $dto = FactoryTestDTO::fromArray(['name' => 'John', 'email' => 'j@t.com']);

    expect($dto->toArray())->toBe(['name' => 'John', 'email' => 'j@t.com']);
});

it('keeps falsy values in toArray', function (): void {
    $dto = FactoryTestDTO::fromArray(['name' => '', 'email' => 'j@t.com', 'phone' => '']);

    expect($dto->toArray())->toBe(['name' => '', 'email' => 'j@t.com', 'phone' => '']);
});

it('throws when class has no constructor', function (): void {
    FactoryTestNoConstructorDTO::fromArray(['name' => 'John']);
})->throws(InvalidArgumentException::class, 'must have a constructor');

it('includes default values for skipped mid-constructor params', function (): void {
    // Only provide 'name' and 'email' but not 'middle'
    // The elseif branch must include the default for 'middle' so positional args are correct
    $dto = FactoryTestMidDefaultDTO::fromArray(['name' => 'John', 'email' => 'j@t.com']);

    expect($dto->name)->toBe('John');
    expect($dto->middle)->toBeNull();
    expect($dto->email)->toBe('j@t.com');
});
