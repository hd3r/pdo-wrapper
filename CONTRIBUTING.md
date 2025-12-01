# Development

## Requirements

- PHP 8.1+
- Composer
- Docker (for MySQL/PostgreSQL tests)

## Setup

```bash
git clone <repository-url>
cd pdo-wrapper
composer install
```

## Running Tests

### SQLite only (no Docker)

```bash
./vendor/bin/phpunit --exclude-group mysql,postgres
```

### Full Test Suite

```bash
docker-compose up -d
./vendor/bin/phpunit
docker-compose down
```

### Code Coverage

```bash
./vendor/bin/phpunit --coverage-html coverage/
```
