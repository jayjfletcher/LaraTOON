<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Jayi\Toon\Encoding\EncoderOptions;
use Jayi\Toon\Enums\KeyFolding;
use Jayi\Toon\Toon;
use Jayi\Toon\Traits\HasToon;

beforeEach(function () {
    if (! Collection::hasMacro('toToon')) {
        Collection::macro('toToon', function (?EncoderOptions $options = null): string {
            /** @var Collection $this */
            return Toon::encode($this->all(), $options);
        });
    }
});

it('registers the toToon collection macro', function () {
    expect(Collection::hasMacro('toToon'))->toBeTrue();
});

it('converts a collection to toon', function () {
    $collection = collect([
        'id' => 123,
        'name' => 'Ada',
        'active' => true,
    ]);

    $result = $collection->toToon();

    expect($result)->toBe("id: 123\nname: Ada\nactive: true");
});

it('converts a collection of objects to tabular toon', function () {
    $collection = collect([
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ]);

    $result = $collection->toToon();

    expect($result)->toContain('[2]{id,name}:');
    expect($result)->toContain('1,Alice');
    expect($result)->toContain('2,Bob');
});

it('converts a simple list collection to toon', function () {
    $collection = collect(['admin', 'ops', 'dev']);

    $result = $collection->toToon();

    expect($result)->toBe('[3]: admin,ops,dev');
});

it('accepts encoder options on collection', function () {
    $collection = collect([
        'a' => ['b' => ['c' => 1]],
    ]);

    $result = $collection->toToon(new EncoderOptions(
        keyFolding: KeyFolding::Safe,
    ));

    expect($result)->toBe('a.b.c: 1');
});

it('converts a model to toon via HasToon trait', function () {
    $model = new class extends Model
    {
        use HasToon;

        protected $guarded = [];
    };

    $model->forceFill(['id' => 1, 'name' => 'Ada', 'role' => 'admin']);

    $result = $model->toToon();

    expect($result)->toContain('name: Ada');
    expect($result)->toContain('role: admin');
});
