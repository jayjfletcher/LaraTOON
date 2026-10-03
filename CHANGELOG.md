# Changelog

## Unreleased

### Breaking

The package now uses the domain-module layout (`src/Domains/{Encoding,Decoding,Integration}`). Classes moved without aliases; update imports:

| Old | New |
|-----|-----|
| `JayI\Toon\Encoding\EncoderOptions` | `JayI\Toon\Domains\Encoding\Data\EncoderOptions` |
| `JayI\Toon\Encoding\ToonEncoder` | `JayI\Toon\Domains\Encoding\Support\ToonEncoder` |
| `JayI\Toon\Encoding\ValueEncoder` | `JayI\Toon\Domains\Encoding\Support\ValueEncoder` |
| `JayI\Toon\Encoding\KeyFolder` | `JayI\Toon\Domains\Encoding\Support\KeyFolder` |
| `JayI\Toon\Encoding\NumberEncoder` | `JayI\Toon\Domains\Encoding\Support\NumberEncoder` |
| `JayI\Toon\Enums\Delimiter` | `JayI\Toon\Domains\Encoding\Enums\Delimiter` |
| `JayI\Toon\Enums\KeyFolding` | `JayI\Toon\Domains\Encoding\Enums\KeyFolding` |
| `JayI\Toon\Exceptions\ToonEncodeException` | `JayI\Toon\Domains\Encoding\Exceptions\ToonEncodeException` |
| `JayI\Toon\Decoding\DecoderOptions` | `JayI\Toon\Domains\Decoding\Data\DecoderOptions` |
| `JayI\Toon\Decoding\ToonDecoder` | `JayI\Toon\Domains\Decoding\Support\ToonDecoder` |
| `JayI\Toon\Decoding\ValueDecoder` | `JayI\Toon\Domains\Decoding\Support\ValueDecoder` |
| `JayI\Toon\Decoding\HeaderParser` | `JayI\Toon\Domains\Decoding\Support\HeaderParser` |
| `JayI\Toon\Decoding\PathExpander` | `JayI\Toon\Domains\Decoding\Support\PathExpander` |
| `JayI\Toon\Enums\PathExpansion` | `JayI\Toon\Domains\Decoding\Enums\PathExpansion` |
| `JayI\Toon\Exceptions\ToonDecodeException` | `JayI\Toon\Domains\Decoding\Exceptions\ToonDecodeException` |
| `JayI\Toon\Exceptions\ToonStrictModeException` | `JayI\Toon\Domains\Decoding\Exceptions\ToonStrictModeException` |
| `JayI\Toon\Traits\HasToon` | `JayI\Toon\Domains\Integration\Concerns\HasToon` |
| `JayI\Toon\Overrides\Laravel\Ai\EncodesToonToolResults` | `JayI\Toon\Domains\Integration\Ai\EncodesToonToolResults` |
| `JayI\Toon\Overrides\Laravel\Ai\ToonMiddleware` | `JayI\Toon\Domains\Integration\Ai\ToonMiddleware` |
| `JayI\Toon\Overrides\Laravel\Ai\ToonToolResponse` | `JayI\Toon\Domains\Integration\Ai\ToonToolResponse` |
| `JayI\Toon\Overrides\Laravel\Pao\Plugin` | `JayI\Toon\Domains\Integration\Pao\Plugin` |
| `JayI\Toon\Overrides\Laravel\Pao\ToonOutputFilter` | `JayI\Toon\Domains\Integration\Pao\ToonOutputFilter` |

The Laravel macros are now registered by `JayI\Toon\Domains\Integration\IntegrationServiceProvider`, which `ToonServiceProvider` registers through `JayI\Toon\Domains\DomainServiceProvider`. `ToonServiceProvider`, `Toon`, the `Toon` facade, `ToonException`, the `toon` config, the `toon-config` publish tag and the macro names are unchanged. The Pest plugin entry in `extra.pest.plugins` now points at `JayI\Toon\Domains\Integration\Pao\Plugin`.
