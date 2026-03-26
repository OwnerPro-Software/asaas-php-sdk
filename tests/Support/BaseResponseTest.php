<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\BaseResponse;

mutates(BaseResponse::class);

// Concrete test response for testing purposes
final class BaseDtoTestResponse extends BaseResponse
{
    public string $id;

    public string $name;

    public ?string $email;
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
