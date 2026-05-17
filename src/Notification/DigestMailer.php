<?php

namespace App\Notification;

use App\Care\DueTaskFinder;
use App\Entity\User;
use App\Household\HouseholdClock;
use App\Repository\MembershipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Sends the "your plants today" morning email.
 */
final class DigestMailer
{
    public function __construct(
        private readonly MembershipRepository $memberships,
        private readonly DueTaskFinder $finder,
        private readonly HouseholdClock $clock,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return bool whether an email was sent (nothing is sent when no task is due)
     */
    public function sendTo(User $user): bool
    {
        $sections = [];
        $count = 0;
        $today = null;
        foreach ($this->memberships->findForUser($user) as $membership) {
            $household = $membership->getHousehold();
            $day = $this->clock->today($household);
            $today ??= $day;
            $tasks = $this->finder->dueOn($household, $day);
            if ([] !== $tasks) {
                $sections[] = ['household' => $household, 'tasks' => $tasks];
                $count += \count($tasks);
            }
        }

        if (null !== $today) {
            $user->setLastDigestOn($today);
            $this->em->flush();
        }

        if (0 === $count) {
            return false;
        }

        $email = (new TemplatedEmail())
            ->to(new Address($user->getEmail(), $user->getDisplayName()))
            ->subject(\sprintf('%d %s for your plants today', $count, 1 === $count ? 'thing' : 'things'))
            ->htmlTemplate('email/digest.html.twig')
            ->textTemplate('email/digest.txt.twig')
            ->context(['user' => $user, 'sections' => $sections, 'count' => $count]);

        $this->mailer->send($email);

        return true;
    }
}
