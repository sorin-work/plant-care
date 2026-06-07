<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Household\MemberManager;
use App\Household\MembershipException;
use App\Repository\MembershipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/account')]
final class AccountController extends AppController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'account', methods: ['GET', 'POST'])]
    public function index(Request $request, #[CurrentUser] User $user, UserPasswordHasherInterface $hasher): Response
    {
        $profile = $this->createForm(ProfileType::class, $user);
        $profile->handleRequest($request);
        if ($profile->isSubmitted() && $profile->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Profile saved.');

            return $this->redirectToRoute('account');
        }

        $password = $this->createForm(ChangePasswordType::class);
        $password->handleRequest($request);
        if ($password->isSubmitted() && $password->isValid()) {
            /** @var array{current: string, password: string} $data */
            $data = $password->getData();
            $user->setPassword($hasher->hashPassword($user, $data['password']));
            $this->em->flush();
            $this->addFlash('success', 'Your password was changed.');

            return $this->redirectToRoute('account');
        }

        if ($profile->isSubmitted() || $password->isSubmitted()) {
            $this->em->refresh($user);
        }

        return $this->render('account/index.html.twig', [
            'profile' => $profile,
            'password' => $password,
        ], new Response(status: $profile->isSubmitted() || $password->isSubmitted() ? 422 : 200));
    }

    #[Route('/api-token', name: 'account_token', methods: ['POST'])]
    public function generateToken(Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertCsrf('api-token', $request);
        $token = $user->generateApiToken();
        $this->em->flush();

        return $this->render('account/index.html.twig', [
            'new_token' => $token,
            'profile' => $this->createForm(ProfileType::class, $user),
            'password' => $this->createForm(ChangePasswordType::class),
        ]);
    }

    #[Route('/api-token/revoke', name: 'account_token_revoke', methods: ['POST'])]
    public function revokeToken(Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertCsrf('api-token', $request);
        $user->revokeApiToken();
        $this->em->flush();
        $this->addFlash('success', 'Your API token was revoked.');

        return $this->redirectToRoute('account');
    }

    #[Route('/calendar', name: 'account_calendar', methods: ['POST'])]
    public function resetCalendar(Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertCsrf('calendar-feed', $request);
        $hadFeed = null !== $user->getCalendarToken();
        $user->resetCalendarToken();
        $this->em->flush();
        $this->addFlash('success', $hadFeed ? 'Your calendar link was replaced. Update it in your calendar app.' : 'Your calendar link is ready.');

        return $this->redirectToRoute('account');
    }

    #[Route('/delete', name: 'account_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[CurrentUser] User $user,
        UserPasswordHasherInterface $hasher,
        MembershipRepository $memberships,
        MemberManager $manager,
        Security $security,
    ): Response {
        $this->assertCsrf('delete-account', $request);
        if (!$hasher->isPasswordValid($user, $request->request->getString('password'))) {
            $this->addFlash('error', 'That is not your password, your account was not deleted.');

            return $this->redirectToRoute('account');
        }

        try {
            $this->em->wrapInTransaction(function () use ($user, $memberships, $manager): void {
                foreach ($memberships->findForUser($user) as $membership) {
                    $manager->leave($membership);
                }
                $this->em->remove($user);
                $this->em->flush();
            });
        } catch (MembershipException $e) {
            $this->addFlash('error', $e->getMessage().' Your account was not deleted.');

            return $this->redirectToRoute('account');
        }

        $security->logout(false);
        $this->addFlash('success', 'Your account was deleted. Take care of your plants!');

        return $this->redirectToRoute('app_login');
    }
}
