<?php

declare(strict_types=1);

use OwnerPro\Asaas\Support\BaseDTO;

mutates(BaseDTO::class);

// Concrete test DTO for testing purposes
final class BaseDtoTestDTO extends BaseDTO
{
    public string $id;

    public string $name;

    public ?string $email;
}

it('assigns known properties from data', function () {
    $dto = new BaseDtoTestDTO(['id' => 'abc', 'name' => 'John', 'email' => 'john@test.com']);

    expect($dto->id)->toBe('abc');
    expect($dto->name)->toBe('John');
    expect($dto->email)->toBe('john@test.com');
});

it('returns unknown properties via __get without breaking', function () {
    $dto = new BaseDtoTestDTO(['id' => 'abc', 'name' => 'John', 'email' => null, 'extraField' => 'surprise']);

    expect($dto->extraField)->toBe('surprise');
});

it('returns null for completely unknown properties', function () {
    $dto = new BaseDtoTestDTO(['id' => 'abc', 'name' => 'John', 'email' => null]);

    expect($dto->nonExistent)->toBeNull();
});

it('prevents modification via __set', function () {
    $dto = new BaseDtoTestDTO(['id' => 'abc', 'name' => 'John', 'email' => null]);

    $dto->name = 'Jane';
})->throws(LogicException::class, "Cannot modify property 'name' on an immutable DTO.");

it('supports isset for existing attributes', function () {
    $dto = new BaseDtoTestDTO(['id' => 'abc', 'name' => 'John', 'email' => null]);

    expect(isset($dto->id))->toBeTrue();
    expect(isset($dto->name))->toBeTrue();
    expect(isset($dto->nonExistent))->toBeFalse();
});

it('converts to array with all attributes', function () {
    $dto = new BaseDtoTestDTO(['id' => 'abc', 'name' => 'John', 'email' => null, 'extra' => 'value']);

    expect($dto->toArray())->toBe([
        'id' => 'abc',
        'name' => 'John',
        'email' => null,
        'extra' => 'value',
    ]);
});
