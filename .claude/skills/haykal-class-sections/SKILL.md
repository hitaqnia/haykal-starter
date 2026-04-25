---
name: haykal-class-sections
description: Use when writing or editing a class that has many methods or properties grouped into logically distinct concerns (e.g., model with relationships + scopes + accessors; API response factory with 2xx/4xx/5xx blocks; form component with setup + getters + setters). Inserts the canonical haykal three-line section header that already appears across the haykal-* packages.
---

# haykal-class-sections

When a class body contains two or more logically distinct groups of methods or properties, separate them with the canonical Haykal section header. This is a documentation aid — it doesn't change behavior — but it's how every non-trivial class in the haykal packages is laid out, and the team relies on it to navigate large files.

## The header format

A section header is a three-line `//` block: a rule, the title, then another rule. The rule is exactly **61 dashes** after `// `. Indentation matches the surrounding code (4 spaces inside a class body):

```php
    // -------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------
```

Surround each section header with one blank line above and below.

This matches the existing convention in:
- `haykal-monorepo/packages/haykal-api/src/Response/ApiResponse.php` — separates `2xx — Success`, `4xx — Client error`, `5xx — Server error`, `Business error`.
- `haykal-monorepo/packages/haykal-filament/src/Forms/TranslatableTabs.php` — separates the setUp methods from `Configuration` getters/setters.

Don't pick your own width. **61 dashes**, not 60, not 76 — anything else looks wrong next to the haykal packages.

## When to use a section header

Use one **only** when the class has two or more genuinely distinct groups. The rule of thumb: if a reader scrolling through the file would benefit from a "you are now in section X" anchor, add a header. If the class has three short methods that all do the same thing, don't.

Concrete triggers:
- An Eloquent model that mixes relationships, query-builder helpers, accessors, and registerMediaCollections — three or more groups.
- A response factory / facade that maps 2xx vs 4xx vs 5xx (`ApiResponse`).
- A form component class that splits internal `setUp` / render hooks from public `Configuration` getters/setters (`TranslatableTabs`).
- A service class that has both "private orchestration helpers" and "public command methods".
- A trait that exposes both boot hooks and helper accessors.

Don't add headers to:
- Action classes — they have one public `execute()` method by design.
- DTOs / value objects — constructor-promoted, no method body to group.
- FormRequest classes — small, with `authorize` / `rules` / `attributes` already self-named.
- JsonResource classes — just `toArray`.
- Single-purpose controllers with one method per HTTP verb (CRUD style).

If you find yourself adding a section that contains only one method, you don't need the header — delete it.

## Standard section names

For Eloquent models, prefer this ordering and these section titles when they apply (skip ones that don't):

```php
class Property extends Model implements HasMedia
{
    use HasUlids, HasTenant, HasFactory, SoftDeletes, HasTranslations, InteractsWithMedia;

    protected string $tenantModel = Complex::class;

    protected $fillable = [/* ... */];
    protected $casts = [/* ... */];
    public array $translatable = ['name', 'description'];

    // -------------------------------------------------------------
    // Boot hooks
    // -------------------------------------------------------------

    protected static function booted(): void { /* ... */ }

    public function newEloquentBuilder($query): PropertyQueryBuilder
    {
        return new PropertyQueryBuilder($query);
    }

    // -------------------------------------------------------------
    // Media
    // -------------------------------------------------------------

    public function registerMediaCollections(): void { /* ... */ }

    // -------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------

    public function complex(): BelongsTo { return $this->belongsTo(Complex::class); }
    public function units(): HasMany { return $this->hasMany(Unit::class); }

    // -------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------

    protected function fullAddress(): Attribute { /* ... */ }

    // -------------------------------------------------------------
    // Domain helpers
    // -------------------------------------------------------------

    public function isOccupied(): bool { return $this->status === PropertyStatus::Occupied; }
}
```

Common section titles — pick what fits, don't invent synonyms:

- `Boot hooks` — `booted()`, `newEloquentBuilder()`, lifecycle observers.
- `Media` — `registerMediaCollections()`, `registerMediaConversions()` (Spatie MediaLibrary).
- `Relationships` — every `belongsTo`/`hasMany`/`belongsToMany`.
- `Scopes` — query scope methods (when not extracted into a custom QueryBuilder).
- `Accessors` — PHP 8 `Attribute::make` accessors and mutators.
- `Casting` — non-trivial cast helper methods.
- `Configuration` — fluent setters that mutate `$this` and return it (form components).
- `Setup` — `setUp()` / `boot()` / one-time initializers.
- `Domain helpers` — small predicates and value-returning methods specific to the entity.

For non-model classes, name the sections after the concern, not the access level. Use `2xx — Success` / `4xx — Client error` / `5xx — Server error` for HTTP envelope factories (matches `ApiResponse`); `Configuration` for fluent setter clusters (matches `TranslatableTabs`); `Public API` / `Internal helpers` only when no better domain word fits.

## Title style

- Sentence case (`Relationships`, not `RELATIONSHIPS` and not `relationships`).
- En dash with spaces for sub-categories (`2xx — Success`), not `--` and not `-`.
- No trailing punctuation (no period, no colon).
- One short noun phrase. If it's wrapping, you've named it wrong — pick a tighter word.

## What goes inside a section

- Methods that share the section's concern. One blank line between methods.
- The order within a section is small-to-large: simple getters first, complex orchestrators last. For `Relationships`, BelongsTo first, then HasOne, HasMany, BelongsToMany, MorphTo last.

## Don't

- Don't put section headers around constants or properties at the top of the class. The trait/use list, properties, and constructor live above the first section header.
- Don't use a different rule character (`=`, `*`, `#`) — only `-`.
- Don't omit the trailing rule — it's a three-line block, always.
- Don't section-header a class with fewer than two real groups. Empty rituals make code worse.
- Don't auto-insert headers when refactoring an existing class for a one-line change. Add them only when you're already touching the file's structure.

## Don't enforce mechanically

Pint won't add or remove these — they're free-form comments. Consistency comes from review and from this skill, not from a formatter. When in doubt, look at `ApiResponse.php` and `TranslatableTabs.php` and copy that style.

## References

- Canonical examples: `haykal-monorepo/packages/haykal-api/src/Response/ApiResponse.php`, `haykal-monorepo/packages/haykal-filament/src/Forms/TranslatableTabs.php`
- See also: **haykal-models** (recommended ordering inside model classes)
