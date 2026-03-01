<?php

namespace App\Controller\Api;

use App\Entity\Household;
use App\Entity\User;
use App\Repository\MembershipRepository;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class ApiController extends AbstractController
{
    public function __construct(protected readonly MembershipRepository $memberships)
    {
    }

    /**
     * The household named by ?household=ID, or the user's first one.
     */
    protected function household(Request $request): Household
    {
        $user = $this->getUser();
        \assert($user instanceof User);
        $memberships = $this->memberships->findForUser($user);
        $wanted = $request->query->getInt('household');

        foreach ($memberships as $membership) {
            if (0 === $wanted || $membership->getHousehold()->getId() === $wanted) {
                return $membership->getHousehold();
            }
        }

        throw new NotFoundHttpException('Household not found.');
    }

    protected function guard(string $attribute, object $subject): void
    {
        if (!$this->isGranted(Permission::VIEW, $subject)) {
            throw new NotFoundHttpException('Not found.');
        }
        $this->denyAccessUnlessGranted($attribute, $subject);
    }
}
