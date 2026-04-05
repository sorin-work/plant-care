<?php

namespace App\Tests\Unit\Care;

use App\Care\Propagator;
use App\Entity\CareSchedule;
use App\Entity\Household;
use App\Entity\JournalEntry;
use App\Entity\Plant;
use App\Entity\Room;
use App\Enum\CareTaskType;
use App\Enum\LightLevel;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PropagatorTest extends TestCase
{
    public function testCopiesPlantAndCareSchedules(): void
    {
        $household = new Household('Home');
        $parent = (new Plant($household))->setName('Goldie')->setRoom(new Room($household, 'Office'))->setLight(LightLevel::Low);
        new CareSchedule($parent, CareTaskType::Water, 6, new DateTimeImmutable('2026-04-01'));
        (new CareSchedule($parent, CareTaskType::Fertilize, 30, new DateTimeImmutable('2026-04-01')))->setSeasonal(false);
        new CareSchedule($parent, CareTaskType::Repot, 540, new DateTimeImmutable('2027-01-01'));

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $o) use (&$persisted): void {
            $persisted[] = $o;
        });
        $em->expects(self::once())->method('flush');

        $baby = (new Propagator($em))->propagate($parent, null, new DateTimeImmutable('2026-04-05 15:00'));

        self::assertSame('Goldie (cutting)', $baby->getName());
        self::assertSame('Office', $baby->getRoom()?->getName());
        self::assertSame(LightLevel::Low, $baby->getLight());
        self::assertSame('2026-04-05', $baby->getAcquiredOn()?->format('Y-m-d'));

        $schedules = [];
        foreach ($baby->getSchedules() as $s) {
            $schedules[$s->getType()->value] = $s;
        }
        self::assertSame(['water', 'fertilize'], array_keys($schedules));
        self::assertSame('2026-04-05', $schedules['water']->getNextDueOn()->format('Y-m-d'));
        self::assertSame('2026-05-05', $schedules['fertilize']->getNextDueOn()->format('Y-m-d'));
        self::assertFalse($schedules['fertilize']->isSeasonal());

        $entries = array_values(array_filter($persisted, fn ($o) => $o instanceof JournalEntry));
        self::assertSame('Took a cutting: Goldie (cutting).', $entries[0]->getNote());
    }
}
