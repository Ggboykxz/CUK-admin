# Contributing to CUK-Admin

Thank you for your interest in contributing to CUK-Admin!

## Code of Conduct

By participating, you agree to maintain a respectful and welcoming environment.

## How to Contribute

### Reporting Bugs

1. Check if the bug already exists
2. Create a clear bug report with:
   - Expected behavior
   - Actual behavior
   - Steps to reproduce
   - PHP version, OS, browser

### Suggesting Features

1. Check existing issues
2. Describe the feature clearly
3. Explain the use case

### Pull Requests

1. Fork the repository
2. Create a feature branch:
   ```bash
   git checkout -b feature/YourFeature
   ```
3. Make your changes
4. Follow PSR12 coding standards:
   ```bash
   composer run lint
   ```
5. Commit with clear messages
6. Push and create Pull Request

## Coding Standards

- Follow PSR12 standards
- Use meaningful variable names
- Comment complex logic
- Test your changes

## Style Guide

```php
// ✅ Good
public function getStudents(): array
{
    return $this->fetchAll('SELECT * FROM etudiants');
}

// ❌ Bad
public function get() {
    return $this->fetchAll('select * from etudiants');
}
```

## Commit Messages

Use clear commit messages:

```
feat: Add new student registration
fix: Correct notes calculation
docs: Update installation guide
```

## Questions?

Open an issue for questions!