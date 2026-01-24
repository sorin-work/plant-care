# plant-care

A houseplant care tracker: watering, fertilizing and repotting schedules for the
plants in your home, with a daily list of what needs doing.

- **Plants** with species, room, light and a cover photo
- **Schedules** for watering, fertilizing and repotting that stretch in winter and
  shrink in summer
- **Today**: due and overdue tasks grouped by room, done / snooze / skip in one click
- **Journal** with notes and photos for every plant
- **Species catalog** with care defaults for 30+ common houseplants

## Running with Docker

```bash
docker compose up --build
```

Open http://localhost:4414 and sign in with the demo account:

- **Email:** `demo@example.com`
- **Password:** `demo-password`

Set `SEED_DEMO=false` to start with an empty database.

## Local development

You need PHP 8.3 with the `intl`, `pdo_mysql` and `pdo_sqlite` extensions,
Composer and a MySQL 8 (or MariaDB 10.11+) database.

```bash
composer install
# point DATABASE_URL at your database in .env.local
php bin/console doctrine:migrations:migrate
php bin/console app:species:import
php bin/console app:demo:seed
symfony serve   # or: php -S localhost:8000 -t public
```

## Tests

```bash
vendor/bin/phpunit
```

The tests use SQLite, so no database server is needed.

## License

MIT
