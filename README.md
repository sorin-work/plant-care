# plant-care

A houseplant care tracker: watering, fertilizing and repotting schedules for the
plants in your home, with a daily list of what needs doing.

- **Plants** with species, room, light and a cover photo
- **Schedules** for watering, fertilizing and repotting that stretch in winter and
  shrink in summer
- **Today**: due and overdue tasks grouped by room, done / snooze / skip in one click
- **Journal** with notes and photos for every plant
- **Species catalog** with care defaults for 30+ common houseplants
- **Sharing**: invite the people you live with to a household, each with a role

## Households and roles

Everything (rooms, plants, schedules, journal) belongs to a household. You get one
when you sign up and can start more, e.g. for the office. Members are added by the
email of their account and get one of these roles:

| Role      | Can do                                                          |
| --------- | --------------------------------------------------------------- |
| Owner     | Everything, including household settings and members            |
| Editor    | Add and edit plants, rooms and schedules; take care of tasks    |
| Caretaker | Mark tasks done, snooze or skip them, write in the journal      |
| Viewer    | Look at plants, tasks and the journal                           |

The household timezone decides when a new day starts for the today list, and the
hemisphere decides which months count as winter and summer.

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
