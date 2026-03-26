<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\HasArrayFactory;

mutates(HasArrayFactory::class);

final class FactoryTestRequest
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

final class FactoryTestMidDefaultRequest
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

final class FactoryTestNoConstructorRequest
{
    use HasArrayFactory;

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return [];
    }
}

it('creates from array with required fields and applies defaults', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => 'John', 'email' => 'j@t.com']);

    expect($request->name)->toBe('John');
    expect($request->email)->toBe('j@t.com');
    expect($request->phone)->toBeNull();
    expect(isset($request->phone))->toBeFalse();
});

it('creates from array with optional fields', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => 'John', 'email' => 'j@t.com', 'phone' => '123']);

    expect($request->phone)->toBe('123');
});

it('ignores extra keys not in constructor', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => 'John', 'email' => 'j@t.com', 'unknown' => 'x']);

    expect($request->name)->toBe('John');
});

it('throws when required field is missing', function (): void {
    FactoryTestRequest::fromArray(['name' => 'John']);
})->throws(InvalidArgumentException::class, "Field 'email' is required.");

it('converts to array without null values', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => 'John', 'email' => 'j@t.com']);

    expect($request->toArray())->toBe(['name' => 'John', 'email' => 'j@t.com']);
});

it('keeps falsy values in toArray', function (): void {
    $request = FactoryTestRequest::fromArray(['name' => '', 'email' => 'j@t.com', 'phone' => '']);

    expect($request->toArray())->toBe(['name' => '', 'email' => 'j@t.com', 'phone' => '']);
});

it('throws when class has no constructor', function (): void {
    FactoryTestNoConstructorRequest::fromArray(['name' => 'John']);
})->throws(InvalidArgumentException::class, 'must have a constructor');

it('includes default values for skipped mid-constructor params', function (): void {
    // Only provide 'name' and 'email' but not 'middle'
    // The elseif branch must include the default for 'middle' so positional args are correct
    $request = FactoryTestMidDefaultRequest::fromArray(['name' => 'John', 'email' => 'j@t.com']);

    expect($request->name)->toBe('John');
    expect($request->middle)->toBeNull();
    expect($request->email)->toBe('j@t.com');
});
