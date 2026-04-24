# <img src="https://laravel-specifications-doc.netlify.app/logo.svg" alt="Laravel Specifications" height="32"> Laravel Specifications

A powerful implementation of the Specification Pattern for Laravel applications.

[![Tests](https://github.com/dangerwayne/laravel-specifications/actions/workflows/tests.yml/badge.svg)](https://github.com/dangerwayne/laravel-specifications/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/dangerwayne/laravel-specifications.svg?style=flat-square)](https://packagist.org/packages/dangerwayne/laravel-specifications)
[![Total Downloads](https://img.shields.io/packagist/dt/dangerwayne/laravel-specifications.svg?style=flat-square)](https://packagist.org/packages/dangerwayne/laravel-specifications)
[![Documentation](https://img.shields.io/badge/docs-ready-green.svg?style=flat-square)](https://laravel-specifications-doc.netlify.app)

📚 **[Full Documentation](https://laravel-specifications-doc.netlify.app)** | 
📦 [Packagist](https://packagist.org/packages/dangerwayne/laravel-specifications) | 
⚡ [Quick Start](https://laravel-specifications-doc.netlify.app/quick-start/)

## Features

- **Artisan Generator**: Create specifications with `php artisan make:specification`
- **Eloquent Integration**: Seamlessly works with Laravel's Eloquent ORM
- **Collection Support**: Filter in-memory collections using the same specifications
- **Fluent Builder**: Intuitive API for building complex specifications
- **Composite Operations**: Combine specifications with AND, OR, NOT operations
- **Caching Support**: Built-in caching for improved performance
- **Laravel 9, 10, 11, 12, 13**: Full compatibility with modern Laravel versions
- **Type Safe**: Full PHP 8.0+ type declarations and PHPStan level 6+ compliance

> **Note**: The `NOT` specification has full functionality in Laravel 10+. In Laravel 9, it provides basic compatibility with limited SQL generation capabilities.

## Installation

```bash
composer require dangerwayne/laravel-specifications
```

The package will automatically register its service provider.

## Artisan Command

Generate specification classes effortlessly using the artisan command:

```bash
php artisan make:specification UserActiveSpecification
```

### Command Options

#### Domain Organization
Organize specifications by domain or module:
```bash
php artisan make:specification Bookmark/SearchSpecification
php artisan make:specification Order/HighValueOrderSpecification
```

#### Model Binding
Generate specifications bound to specific models:
```bash
php artisan make:specification UserPremiumSpecification --model=User
```

#### Advanced Options
```bash
# Composite specification with example composition
php artisan make:specification ComplexFilterSpecification --composite

# Include caching support
php artisan make:specification ExpensiveSpecification --cacheable

# Use builder pattern for complex rules
php artisan make:specification AdvancedRulesSpecification --builder

# Generate with test file
php artisan make:specification TestedSpecification --test

# Generate with Pest test
php artisan make:specification PestSpecification --pest

# Force overwrite existing file
php artisan make:specification ExistingSpecification --force
```

#### Combined Options
```bash
# Model-bound specification with caching and test
php artisan make:specification Order/PremiumOrderSpecification --model=Order --cacheable --test

# Composite specification with builder pattern
php artisan make:specification Product/ComplexSearchSpecification --composite --builder
```

### Publishing Stubs

Customize the generated specifications by publishing the stubs:

```bash
php artisan vendor:publish --tag=specification-stubs
```

The stubs will be published to `resources/stubs/specification/` where you can modify them to match your coding style and requirements.

## Basic Usage

### Using Pre-built Specifications

```php
use DangerWayne\Specification\Specifications\Common\WhereSpecification;

$activeUsers = User::query()
    ->whereSpecification(new WhereSpecification('status', 'active'))
    ->get();
```

### Creating Custom Specifications

```php
use DangerWayne\Specification\Specifications\AbstractSpecification;
use Illuminate\Database\Eloquent\Builder;

class PremiumUserSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $candidate->subscription === 'premium';
    }
    
    public function toQuery(Builder $query): Builder
    {
        return $query->where('subscription', 'premium');
    }
}
```

### Using the Fluent Builder

```php
use DangerWayne\Specification\Facades\Specification;

$spec = Specification::create()
    ->where('status', 'active')
    ->where('age', '>=', 18)
    ->whereNotNull('email_verified_at')
    ->build();

$users = User::whereSpecification($spec)->get();
```

### Combining Specifications

```php
$activeSpec = new WhereSpecification('status', '=', 'active');
$premiumSpec = new PremiumUserSpecification();

// AND combination
$activePremiumSpec = $activeSpec->and($premiumSpec);

// OR combination
$activeOrPremiumSpec = $activeSpec->or($premiumSpec);

// NOT combination
$notActiveSpec = $activeSpec->not();
```

### Working with Collections

```php
$users = collect([
    new User(['status' => 'active', 'age' => 25]),
    new User(['status' => 'inactive', 'age' => 30]),
    new User(['status' => 'active', 'age' => 17]),
]);

$spec = Specification::create()
    ->where('status', 'active')
    ->where('age', '>=', 18)
    ->build();

$filteredUsers = $users->whereSpecification($spec);
```

## Available Specifications

The package includes several pre-built specifications:

### WhereSpecification
```php
new WhereSpecification('status', '=', 'active');
new WhereSpecification('age', '>', 18);
new WhereSpecification('name', 'like', '%john%');
```

### WhereInSpecification
```php
new WhereInSpecification('status', ['active', 'pending']);
```

### WhereBetweenSpecification
```php
new WhereBetweenSpecification('age', 18, 65);
```

### WhereNullSpecification
```php
new WhereNullSpecification('email_verified_at');
```

### WhereHasSpecification
```php
new WhereHasSpecification('posts', new WhereSpecification('published', true));
```

## Fluent Builder Methods

```php
Specification::create()
    ->where('field', 'operator', 'value')    // Basic where clause
    ->where('field', 'value')                // Defaults to '=' operator
    ->whereIn('field', [1, 2, 3])           // WHERE IN clause
    ->whereBetween('field', 1, 10)          // BETWEEN clause
    ->whereNull('field')                     // IS NULL clause
    ->whereNotNull('field')                  // IS NOT NULL clause
    ->whereHas('relation', $specification)   // Has relationship
    ->or()                                   // Next condition uses OR
    ->build();                              // Build the specification
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=specification-config
```

```php
return [
    'cache' => [
        'enabled' => env('SPECIFICATION_CACHE_ENABLED', false),
        'ttl' => env('SPECIFICATION_CACHE_TTL', 3600),
        'prefix' => env('SPECIFICATION_CACHE_PREFIX', 'spec_'),
    ],
    'performance' => [
        'lazy_collections' => env('SPECIFICATION_USE_LAZY', true),
        'chunk_size' => env('SPECIFICATION_CHUNK_SIZE', 1000),
    ],
];
```

## Advanced Usage

### Custom Specifications with Parameters

```php
class AgeRangeSpecification extends AbstractSpecification
{
    public function __construct(
        private int $minAge,
        private int $maxAge
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $candidate->age >= $this->minAge 
            && $candidate->age <= $this->maxAge;
    }
    
    public function toQuery(Builder $query): Builder
    {
        return $query->whereBetween('age', [$this->minAge, $this->maxAge]);
    }

    protected function getParameters(): array
    {
        return [
            'minAge' => $this->minAge,
            'maxAge' => $this->maxAge,
        ];
    }
}
```

### Complex Specifications

```php
$specification = Specification::create()
    ->where('status', 'active')
    ->where(function ($builder) {
        return $builder
            ->where('role', 'admin')
            ->or()
            ->where('role', 'moderator');
    })
    ->whereNotNull('email_verified_at')
    ->build();
```

## Testing

```bash
composer test
```

## Code Quality

```bash
composer analyse    # PHPStan analysis
composer format     # Code formatting with Pint
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Feedback & Support

We'd love to hear from you! Here are ways to provide feedback or get help:

- 🐛 **Report Issues**: [Create an issue](https://github.com/dangerwayne/laravel-specifications/issues/new/choose) for bug reports
- 💡 **Request Features**: [Suggest new features](https://github.com/dangerwayne/laravel-specifications/issues/new?template=feature_request.md)
- 💬 **Ask Questions**: [Start a discussion](https://github.com/dangerwayne/laravel-specifications/discussions) or [ask a question](https://github.com/dangerwayne/laravel-specifications/issues/new?template=question.md)
- 📧 **Email**: For private inquiries, reach out to the maintainers

## Contributing

We welcome contributions! Please see [CONTRIBUTING](CONTRIBUTING.md) for details on:
- How to report issues
- How to suggest features
- How to submit pull requests
- Development setup and standards

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Barry Stovall](https://github.com/bstovall)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
