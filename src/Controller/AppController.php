<?php

namespace App\Controller;

use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AppController extends AbstractController
{
    /**
     * Content of households the user is not a member of does not exist as far
     * as they are concerned (404); members without the needed role get a 403.
     */
    protected function guard(string $attribute, object $subject): void
    {
        if (!$this->isGranted(Permission::VIEW, $subject)) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted($attribute, $subject);
    }

    protected function assertCsrf(string $id, Request $request): void
    {
        if (!$this->isCsrfTokenValid($id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
