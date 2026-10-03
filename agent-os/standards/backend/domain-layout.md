# Domain Layout

Code is grouped into domain modules under `src/Domains/{Domain}/`, namespace `JayI\Toon\Domains\{Domain}`, mirroring the mono application's domain-module standard:

```
src/
├── Toon.php                    # static API / facade target (package-wide)
├── ToonServiceProvider.php     # config merge + publish tag; registers DomainServiceProvider
├── Facades/Toon.php
├── Exceptions/ToonException.php   # base exception shared by every domain
└── Domains/
    ├── DomainServiceProvider.php  # private $providers array, registered in a loop
    ├── Encoding/   Data/EncoderOptions  Enums/{Delimiter,KeyFolding}  Exceptions/  Support/{ToonEncoder,ValueEncoder,KeyFolder,NumberEncoder}
    ├── Decoding/   Data/DecoderOptions  Enums/PathExpansion  Exceptions/  Support/{ToonDecoder,ValueDecoder,HeaderParser,PathExpander}
    └── Integration/
        ├── IntegrationServiceProvider.php   # toToon()/toon() macros
        ├── Concerns/HasToon.php
        ├── Ai/    # laravel/ai (tool results, middleware)
        └── Pao/   # laravel/pao (Pest output filter)
```

- **Encoding** and **Decoding** are the framework-free core. They have no service provider (nothing to wire), like mono's model-only domains.
- **Integration** holds everything coupled to Laravel or third-party packages. New third-party integration → new vendor subfolder under `Domains/Integration/`.
- Create subdirectories only when used — never empty scaffolding. A new domain with Laravel wiring adds its `{Domain}ServiceProvider` to `DomainServiceProvider::$providers`.
- `Delimiter` lives in Encoding (it is an encoder option); Decoding reads it from the array header. Cross-domain imports between Encoding and Decoding are fine; neither may import from Integration.

Rules:
- NEVER import an external-package class into the core (`Toon`, `Domains/Encoding`, `Domains/Decoding`). Only `illuminate/support` is a core dep.
