# Named Dataset Rows

`->with()` rows MUST use descriptive string keys naming the edge case — never bare positional arrays. A failing case then self-identifies in the test name.

```php
})->with([
    'extreme floats' => [['a' => 1.7976931348623157e308, 'b' => 1e-7]],
    'strings resembling toon syntax' => [['a' => '[3]: x,y,z', 'b' => '- item']],
    'literal dotted key' => [['a.b' => 1]],
]);
```

- Key names the boundary being probed (unicode, toon-lookalike strings, dotted keys, big integers).
- New behavior/edge → add a named row rather than a new one-off test.
