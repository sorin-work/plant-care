<?php

namespace App\Demo;

use App\Care\CareScheduler;
use App\Care\ScheduleFactory;
use App\Entity\CareEvent;
use App\Entity\JournalEntry;
use App\Entity\Plant;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\CareAction;
use App\Enum\LightLevel;
use App\Household\HouseholdManager;
use App\Repository\SpeciesRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Creates a demo account with a furnished household and a few months of care
 * history, so the app has something to show on first start.
 */
final class DemoSeeder
{
    public const EMAIL = 'demo@example.com';
    public const PASSWORD = 'demo-password';

    /**
     * name, scientific name of the species, room, light, days owned, notes.
     */
    private const PLANTS = [
        ['Monty', 'Monstera deliciosa', 'Living room', LightLevel::BrightIndirect, 400, 'Big one next to the sofa. Needs a moss pole soon.'],
        ['Goldie', 'Epipremnum aureum', 'Living room', LightLevel::Medium, 700, 'Trailing from the bookshelf.'],
        ['Fig', 'Ficus lyrata', 'Living room', LightLevel::BrightIndirect, 250, null],
        ['Sansa', 'Dracaena trifasciata', 'Bedroom', LightLevel::Low, 900, 'Gift from grandma.'],
        ['Zed', 'Zamioculcas zamiifolia', 'Bedroom', LightLevel::Low, 300, null],
        ['Lily', 'Spathiphyllum wallisii', 'Bathroom', LightLevel::Medium, 180, 'Droops dramatically when thirsty.'],
        ['Fern Gully', 'Nephrolepis exaltata', 'Bathroom', LightLevel::Medium, 120, null],
        ['Pancake', 'Pilea peperomioides', 'Kitchen', LightLevel::BrightIndirect, 210, 'Rotate weekly. Has two babies to pot up.'],
        ['Basil', 'Ocimum basilicum', 'Kitchen', LightLevel::Direct, 40, null],
        ['Aloe', 'Aloe vera', 'Kitchen', LightLevel::Direct, 1000, null],
        ['Orchid', 'Phalaenopsis amabilis', 'Office', LightLevel::BrightIndirect, 330, 'Reblooming after the winter rest.'],
        ['Spike', 'Opuntia microdasys', 'Office', LightLevel::Direct, 500, 'Gloves!'],
    ];

    private const NOTES = [
        'New leaf unfurling.',
        'Moved a bit closer to the window.',
        'Wiped the leaves, they look much happier.',
        'Found a few yellow leaves at the bottom, removed them.',
        'Soil was still damp, waited a couple more days.',
        'Rotated it a quarter turn.',
        'Spotted a tiny new shoot at the base!',
        'Gave it a shower to wash off the dust.',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly SpeciesRepository $species,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly HouseholdManager $households,
        private readonly ScheduleFactory $scheduleFactory,
        private readonly CareScheduler $scheduler,
    ) {
    }

    public function exists(): bool
    {
        return null !== $this->users->findOneByEmail(self::EMAIL);
    }

    public function seed(DateTimeImmutable $today): User
    {
        mt_srand(4414);
        $today = $today->setTime(0, 0);

        $user = (new User())->setEmail(self::EMAIL)->setDisplayName('Demo');
        $user->setPassword($this->hasher->hashPassword($user, self::PASSWORD));
        $this->em->persist($user);

        $household = $this->households->create($user, 'Demo home');
        $household->setTimezone('Europe/Berlin');

        $rooms = [];
        foreach (['Living room', 'Bedroom', 'Bathroom', 'Kitchen', 'Office'] as $name) {
            $rooms[$name] = new Room($household, $name);
            $this->em->persist($rooms[$name]);
        }

        foreach (self::PLANTS as [$name, $scientific, $room, $light, $daysOwned, $notes]) {
            $plant = (new Plant($household))
                ->setName($name)
                ->setSpecies($this->species->findOneByScientificName($scientific))
                ->setRoom($rooms[$room])
                ->setLight($light)
                ->setAcquiredOn($today->modify(\sprintf('-%d days', $daysOwned)))
                ->setNotes($notes);
            $this->em->persist($plant);
            $this->scheduleFactory->createDefaults($plant, $today->modify(\sprintf('-%d days', min($daysOwned, 120))));
            $this->simulateHistory($plant, $user, $today, min($daysOwned, 120));
        }

        $this->em->flush();

        return $user;
    }

    /**
     * Replays a few months of care so the journal and history are not empty.
     * Roughly one in five plants is left a little behind so Today has work.
     */
    private function simulateHistory(Plant $plant, User $user, DateTimeImmutable $today, int $days): void
    {
        $behind = 0 === mt_rand(0, 4);

        foreach ($plant->getSchedules() as $schedule) {
            $guard = 0;
            while ($schedule->getNextDueOn() < $today && $guard++ < 200) {
                $day = $schedule->getNextDueOn()->modify(\sprintf('+%d days', mt_rand(0, 1)));
                if ($day >= $today || ($behind && $day > $today->modify('-4 days'))) {
                    break;
                }
                $action = 0 === mt_rand(0, 12) ? CareAction::Skipped : CareAction::Done;
                if (CareAction::Done === $action) {
                    $this->scheduler->complete($schedule, $day);
                } else {
                    $this->scheduler->skip($schedule, $day);
                }
                $this->em->persist(new CareEvent($plant, $schedule->getType(), $action, $day, $user));
            }
        }

        for ($i = 0, $n = mt_rand(1, 3); $i < $n; ++$i) {
            $entry = (new JournalEntry($plant, $user, $today->modify(\sprintf('-%d days', mt_rand(1, max(1, $days))))))
                ->setNote(self::NOTES[mt_rand(0, \count(self::NOTES) - 1)]);
            $this->em->persist($entry);
        }
    }
}
