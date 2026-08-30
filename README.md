# plant-care

A houseplant care tracker: watering, fertilizing and repotting schedules for the
plants in your home, with a daily list of what needs doing.

- **Plants** with species, room, light and a cover photo; search, sort, archive and
  propagate cuttings
- **Schedules** for watering, fertilizing, misting and repotting that stretch in
  winter and shrink in summer, with an optional winter pause for fertilizer
- **Today**: due and overdue tasks grouped by room, done / snooze / skip in one click,
  or water a whole room at once
- **Coming up**: what is due over the next week, plus an iCal feed for your calendar
- **Journal** with notes and photos for every plant, a photo timeline and CSV export
- **Species catalog** with care defaults and pet toxicity for 40+ common houseplants
- **Sharing**: invite the people you live with to a household, each with a role
- **Stats** on the care of the last weeks
- **JSON API** with personal tokens

## Households and roles

Everything (rooms, plants, schedules, journal) belongs to a household. You get one
when you sign up and can start more, e.g. for the office. Owners invite people on
the Members page, either with a one-time link (valid for 7 days, works for people
without an account yet) or directly by the email of an existing account. Every
member has one of these roles:

| Role      | Can do                                                          |
| --------- | --------------------------------------------------------------- |
| Owner     | Everything, including household settings and members            |
| Editor    | Add and edit plants, rooms and schedules; take care of tasks    |
| Caretaker | Mark tasks done, snooze or skip them, write in the journal      |
| Viewer    | Look at plants, tasks and the journal                           |

The household timezone decides when a new day starts for the today list, and the
hemisphere decides which months count as winter and summer.

How intervals change with the seasons is described in [docs/scheduling.md](docs/scheduling.md).
There is also a small JSON API with personal tokens, see [docs/api.md](docs/api.md).

## Running with Docker

```bash
docker compose up --build
```

Open http://localhost:4414 and sign in with the demo account:

- **Email:** `demo@example.com`
- **Password:** `demo-password`

The demo account has a household with five rooms, a dozen plants and a few months
of care history, a flatmate with the caretaker role (`sam@example.com`, same
password) and access to Sam's office household as an editor. Set `SEED_DEMO=false`
to start with an empty database.

| Service   | URL / port             | Notes                                                 |
| --------- | ---------------------- | ----------------------------------------------------- |
| app       | http://localhost:4414  | PHP 8.3 + Apache; runs migrations on start            |
| db        | internal               | MySQL 8.4, user/password `plantcare`                  |
| scheduler | -                      | Runs `app:digest:send` every hour                     |
| mailpit   | http://localhost:4415  | Catches every email the app sends                     |

Ports can be changed with `WEB_PORT` and `MAILPIT_PORT`. Data lives in the
`db-data`, `photos` and `sessions` volumes; `docker compose down -v` removes them.

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

## Configuration

Set these as environment variables or in `.env.local`:

| Variable       | Default                          | Description                                              |
| -------------- | -------------------------------- | -------------------------------------------------------- |
| `APP_ENV`      | `dev`                            | `prod` in the Docker image                               |
| `APP_SECRET`   | (placeholder)                    | Random string used to sign sessions and CSRF tokens      |
| `DATABASE_URL` | `mysql://plantcare:...@127.0.0.1` | MySQL 8 / MariaDB 10.11+ connection                     |
| `DEFAULT_URI`  | `http://localhost:8000`          | Public URL, used for links in emails and the calendar    |
| `PHOTO_DIR`    | `var/uploads/photos`             | Where uploaded photos are stored                         |
| `MAILER_DSN`   | `null://null`                    | Outgoing mail; Docker sends to Mailpit                   |
| `MAILER_FROM`  | `plants@localhost`               | Sender address of the daily email                        |
| `SEED_DEMO`    | `true` (Docker only)             | Create the demo account on first start                   |

The daily email goes out from `php bin/console app:digest:send`, which should run
every hour (the `scheduler` service does this in Docker). Each user who opted in on
the Account page gets one email a day after 7:00 in their household's timezone, and
only when something is due.

## Tests

```bash
vendor/bin/phpunit
```

The tests use SQLite, so no database server is needed.

## License

MIT
