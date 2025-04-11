# Artisan Alley Unit Tests

This directory contains unit tests for the ArtisenAlley marketplace application. These tests are designed to verify the functionality of key components.

- `bootstrap.php` - Sets up the test environment
- `TestUtils.php` - Contains utility functions 
- `run.php` - Script to run all tests
- `unit/` - Directory containing all unit tests

## Running Tests

To run all tests, execute the following command from the project root:

```bash
php tests/run.php
```

## Available Tests

- **ProductUtilsTest.php** - Tests the product utility functions (average rating calculation)
- **ImageUtilsTest.php** - Tests image utility functions (profile image encoding)
- **CsrfTest.php** - Tests CSRF protection utilities
- **DatabaseTest.php** - Tests database operations 

## Writing New Tests

1. Create a new test file in the `unit/` directory with a name ending in `Test.php`
2. Include the bootstrap and utility files:
   ```php
   require_once __DIR__ . '/../bootstrap.php';
   require_once __DIR__ . '/../TestUtils.php';
   ```
3. Use the `assert_true()` function for assertions
4. Your test will be automatically discovered by the test runner
