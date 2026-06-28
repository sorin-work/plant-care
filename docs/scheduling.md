# How care is scheduled

Every plant has up to four schedules: **water**, **fertilize**, **mist** and
**repot**. A schedule stores a base interval in days, the next due date, the date
it was last done and an optional snooze date.

## Default schedules

When a plant is added, its species (if any) provides the defaults:

| Task      | Default                                               | First due              |
| --------- | ----------------------------------------------------- | ---------------------- |
| Water     | species interval, or 7 days                           | today                  |
| Fertilize | species interval; none for species never fertilized   | one interval from today |
| Repot     | species interval in months × 30, or 18 months         | from the acquisition date |
| Mist      | not created automatically, 3 days when added          | today                  |

Repotting is never seasonal.

## Seasons

Seasons are meteorological and depend on the household's hemisphere:

| Months (north) | Months (south) | Season |
| -------------- | -------------- | ------ |
| Dec – Feb      | Jun – Aug      | Winter |
| Mar – May      | Sep – Nov      | Spring |
| Jun – Aug      | Dec – Feb      | Summer |
| Sep – Nov      | Mar – May      | Autumn |

## Seasonal intervals

For seasonal schedules the base interval is multiplied by a factor for the season
of the day the task is done, rounded to the nearest day and never below one day:

| Task      | Winter | Spring | Summer | Autumn |
| --------- | ------ | ------ | ------ | ------ |
| Water     | 1.5    | 1.0    | 0.75   | 1.2    |
| Fertilize | 2.0    | 1.0    | 1.0    | 1.5    |
| Mist      | 0.5    | 1.0    | 1.0    | 1.0    |
| Repot     | 1.0    | 1.0    | 1.0    | 1.0    |

Example: a monstera watered every 7 days is watered every 11 days in winter
(7 × 1.5 = 10.5) and every 5 days in summer (7 × 0.75 = 5.25).

With **Pause fertilizing in winter** in the household settings, a fertilizer
reminder that would fall in winter moves to the first day of spring (1 March in
the north, 1 September in the south).

## Today, done, snooze and skip

"Today" is the current date in the household's timezone. A task is on the today
list when its next due date is today or earlier and it is not snoozed past today.

- **Done** sets the last done date and plans the next time one (seasonal)
  interval after the day it was done. "Did it yesterday" counts from yesterday;
  care can be logged up to 7 days back.
- **Snooze** hides the task for 1 to 14 days without changing the due date.
- **Skip** plans the next time one interval from today without logging it as done.

Every action is logged as a care event and shows up in the plant's history and
the stats.
