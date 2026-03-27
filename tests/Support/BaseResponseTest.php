<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\BaseResponse;
use OwnerPro\Asaas\Support\HasArrayFactory;

mutates(BaseResponse::class);

// Concrete test response for testing purposes
final class BaseDtoTestResponse extends BaseResponse
{
    public string $id;

    public string $name;

    public ?string $email;
}

final readonly class EmbeddedTestDto
{
    use HasArrayFactory;

    public function __construct(
        public string $foo,
        public ?string $bar = null,
    ) {}

    /** @return list<string> */
    protected static function requiredFields(): array
    {
        return ['foo'];
    }
}

final class ResponseWithEmbeddedDto extends BaseResponse
{
    public ?string $name = null;

    public ?EmbeddedTestDto $nested = null;
}

final class ResponseWithUntypedProperty extends BaseResponse
{
    /** @var mixed */
    public $untyped;

    public ?string $name = null;
}

it('assigns known properties from data', function () {
    $response = new BaseDtoTestResponse(['id' => 'abc', 'name' => 'John', 'email' => 'john@test.com']);

    expect($response->id)->toBe('abc');
    expect($response->name)->toBe('John');
    expect($response->email)->toBe('john@test.com');
});

it('returns unknown properties via __get without breaking', function () {
    $response = new BaseDtoTestResponse(['id' => 'abc', 'name' => 'John', 'email' => null, 'extraField' => 'surprise']);

    expect($response->extraField)->toBe('surprise');
});

it('returns null for completely unknown properties', function () {
    $response = new BaseDtoTestResponse(['id' => 'abc', 'name' => 'John', 'email' => null]);

    expect($response->nonExistent)->toBeNull();
});

it('prevents modification via __set', function () {
    $response = new BaseDtoTestResponse(['id' => 'abc', 'name' => 'John', 'email' => null]);

    $response->name = 'Jane';
})->throws(LogicException::class, "Cannot modify property 'name' on an immutable response.");

it('supports isset for existing attributes', function () {
    $response = new BaseDtoTestResponse(['id' => 'abc', 'name' => 'John', 'email' => null]);

    expect(isset($response->id))->toBeTrue();
    expect(isset($response->name))->toBeTrue();
    expect(isset($response->nonExistent))->toBeFalse();
});

it('converts to array with all attributes', function () {
    $response = new BaseDtoTestResponse(['id' => 'abc', 'name' => 'John', 'email' => null, 'extra' => 'value']);

    expect($response->toArray())->toBe([
        'id' => 'abc',
        'name' => 'John',
        'email' => null,
        'extra' => 'value',
    ]);
});

// --- DTO auto-hydration ---

it('auto-hydrates a DTO-typed property from array', function () {
    $response = new ResponseWithEmbeddedDto([
        'name' => 'test',
        'nested' => ['foo' => 'hello', 'bar' => 'world'],
    ]);

    expect($response->nested)->toBeInstanceOf(EmbeddedTestDto::class);
    expect($response->nested->foo)->toBe('hello');
    expect($response->nested->bar)->toBe('world');
});

it('returns null for absent DTO-typed property', function () {
    $response = new ResponseWithEmbeddedDto(['name' => 'test']);

    expect($response->nested)->toBeNull();
});

it('caches hydrated DTO across multiple accesses', function () {
    $response = new ResponseWithEmbeddedDto([
        'name' => 'test',
        'nested' => ['foo' => 'hello'],
    ]);

    $first = $response->nested;
    $second = $response->nested;

    expect($first)->toBe($second);
});

it('does not hydrate scalar-typed properties', function () {
    $response = new ResponseWithEmbeddedDto([
        'name' => 'test',
        'nested' => ['foo' => 'hello'],
    ]);

    expect($response->name)->toBe('test');
});

it('handles untyped properties without error', function () {
    $response = new ResponseWithUntypedProperty([
        'untyped' => ['foo' => 'bar'],
        'name' => 'test',
    ]);

    expect($response->untyped)->toBe(['foo' => 'bar']);
    expect($response->name)->toBe('test');
});

it('preserves raw data in toArray for DTO-typed properties', function () {
    $response = new ResponseWithEmbeddedDto([
        'name' => 'test',
        'nested' => ['foo' => 'hello'],
    ]);

    expect($response->toArray())->toBe([
        'name' => 'test',
        'nested' => ['foo' => 'hello'],
    ]);
});
