# `packages/`

In-tree Composer path repositories for code that wants to live as a package
(its own `composer.json`, its own namespace, its own tests) but isn't ready
to be published yet.

Any subdirectory of `packages/` with a `composer.json` is auto-registered as a
path repository via the root `composer.json`:

```json
{ "type": "path", "url": "packages/*", "options": { "symlink": true } }
```

Add a package by creating `packages/<vendor>/<name>/composer.json`, then
`composer require <vendor>/<name>:@dev` from the project root.

This directory is intentionally empty. Add packages as they emerge.
