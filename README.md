# Secure Healthcare Trainings Backend

Laravel-based backend API for the Secure Healthcare Trainings system.

## Prerequisites

- PHP >= 8.1
- MySQL/MariaDB
- Composer

## Installation

1. Install dependencies:
```bash
composer install
```

2. Configure your database in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=training_events
DB_USERNAME=root
DB_PASSWORD=
```

3. Run migrations and seed the database:
```bash
php artisan migrate --seed
```

4. Set directory permissions (Linux/Mac only):
```bash
chmod -R 775 storage bootstrap/cache
```

5. Create storage symlink:
```bash
php artisan storage:link
```

6. Clear application cache:
```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```
