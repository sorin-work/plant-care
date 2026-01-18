<?php

namespace App\Controller;

use App\Entity\JournalEntry;
use App\Entity\Plant;
use App\Entity\User;
use App\Enum\HouseholdRole;
use App\Form\JournalEntryType;
use App\Household\CurrentHousehold;
use App\Photo\InvalidPhotoException;
use App\Photo\PhotoStorage;
use App\Repository\JournalEntryRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class JournalController extends AbstractController
{
    public function __construct(
        private readonly CurrentHousehold $current,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/plants/{id}/journal', name: 'journal_index', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function index(Plant $plant, Request $request, JournalEntryRepository $entries): Response
    {
        if ($plant->getHousehold()->getId() !== $this->current->get()->getId()) {
            throw $this->createNotFoundException();
        }

        return $this->render('journal/index.html.twig', [
            'plant' => $plant,
            'page' => $entries->pageForPlant($plant, $request->query->getInt('page', 1)),
        ]);
    }

    #[Route('/plants/{id}/journal/new', name: 'journal_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Plant $plant, Request $request, #[CurrentUser] User $user, PhotoStorage $photos): Response
    {
        if ($plant->getHousehold()->getId() !== $this->current->get()->getId()) {
            throw $this->createNotFoundException();
        }

        $entry = new JournalEntry($plant, $user, new DateTimeImmutable('today'));
        $form = $this->createForm(JournalEntryType::class, $entry, [
            'action' => $this->generateUrl('journal_new', ['id' => $plant->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $upload = $form->get('photoFile')->getData();
            if ($upload instanceof UploadedFile) {
                try {
                    $entry->setPhoto($photos->store($upload));
                } catch (InvalidPhotoException $e) {
                    $form->get('photoFile')->addError(new FormError($e->getMessage()));

                    return $this->render('journal/new.html.twig', [
                        'plant' => $plant,
                        'form' => $form,
                    ], new Response(status: 422));
                }
            }

            $this->em->persist($entry);
            $this->em->flush();
            $this->addFlash('success', 'Journal entry added.');

            return $this->redirectToRoute('plant_show', ['id' => $plant->getId()]);
        }

        return $this->render('journal/new.html.twig', [
            'plant' => $plant,
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/journal/{id}/delete', name: 'journal_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(JournalEntry $entry, Request $request, #[CurrentUser] User $user, PhotoStorage $photos): Response
    {
        $plant = $entry->getPlant();
        if ($plant->getHousehold()->getId() !== $this->current->get()->getId()) {
            throw $this->createNotFoundException();
        }
        $isAuthor = $entry->getAuthor()?->getId() === $user->getId();
        if (!$isAuthor && HouseholdRole::Owner !== $this->current->membership()->getRole()) {
            throw $this->createAccessDeniedException('Only the author or the household owner can delete this entry.');
        }
        if (!$this->isCsrfTokenValid('delete-entry-'.$entry->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (null !== $entry->getPhoto()) {
            $photos->delete($entry->getPhoto());
        }
        $this->em->remove($entry);
        $this->em->flush();
        $this->addFlash('success', 'Journal entry deleted.');

        return $this->redirectToRoute('journal_index', ['id' => $plant->getId()]);
    }
}
