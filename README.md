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

2. Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

3. Configure your `.env` file:

**Application URLs:**
```
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173
```

**Database Connection:**
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=training_events
DB_USERNAME=root
DB_PASSWORD=
```

**AWS Configuration (for file storage):**
```
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=your-region
AWS_BUCKET=your-bucket-name
```

**CORS Configuration (set to your frontend URL):**
```
CORS_ALLOWED_ORIGINS=http://localhost:5173
```

4. Run migrations and seed the database:
```bash
php artisan migrate --seed
```

**Default Login Credentials:**
- Email: `admin@securetrainingservices.co.uk`
- Password: `password`

To change these credentials before seeding, edit `database/seeders/UserSeeder.php`.

5. Set directory permissions (Linux/Mac only):
```bash
chmod -R 775 storage bootstrap/cache
```

6. Create storage symlink:
```bash
php artisan storage:link
```

7. Clear application cache:
```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```
