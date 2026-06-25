# Laravel Integration

Toon registers several macros and provides a model trait for seamless Laravel usage.

## Collection Macro

Convert any collection to TOON:

```php
collect(['admin', 'ops', 'dev'])->toToon();
// Output: [3]: admin,ops,dev

collect([
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 2, 'name' => 'Bob'],
])->toToon();
// Output:
// [2]{id,name}:
//   1,Alice
//   2,Bob
```

The macro accepts an optional `EncoderOptions` argument:

```php
collect($data)->toToon(EncoderOptions::compact());
```

## Builder Macro

Query and encode Eloquent results directly:

```php
use App\Models\User;

User::query()->toToon();
```

This fetches all matching records and encodes the result set as TOON. The macro calls `get()->toArray()` internally, so it supports any query builder chain:

```php
User::where('active', true)
    ->select('id', 'name', 'email')
    ->toToon();
```

## JsonResponse Macro

Convert an existing `JsonResponse` to TOON:

```php
$response->toToon();
```

## HasToon Model Trait

Add TOON encoding to any Eloquent model:

```php
use Jayi\Toon\Traits\HasToon;

class User extends Model
{
    use HasToon;
}

$user->toToon();
$user->toToon(EncoderOptions::compact());
```

The trait encodes the model's `toArray()` output as TOON.
