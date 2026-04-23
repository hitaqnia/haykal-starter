# `support/`

App-wide utilities and base classes that do not belong to a single domain.

Anything shared across multiple `Domain\*` modules or multiple `App\*` sub-namespaces
— custom casts, macros, base classes, helpers, concerns — lives here under the
`Support\` namespace. Code that belongs to exactly one domain stays inside that
domain instead.

This directory is intentionally empty. Add files as patterns emerge.
