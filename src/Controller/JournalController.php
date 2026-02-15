<?php

namespace App\Controller;

use App\Entity\JournalEntry;
use App\Entity\Plant;
use App\Entity\User;
use App\Form\JournalEntryType;
use App\Household\HouseholdClock;
use App\Photo\InvalidPhotoException;
use App\Photo\PhotoStorage;
use App\Repository\JournalEntryRepository;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class JournalController extends AppController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/plants/{id}/journal', name: 'journal_index', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function index(Plant $plant, Request $request, JournalEntryRepository $entries): Response
    {
        $this->guard(Permission::VIEW, $plant);

        return $this->render('journal/index.html.twig', [
            'plant' => $plant,
            'page' => $entries->pageForPlant($plant, $request->query->getInt('page', 1)),
        ]);
    }

    #[Route('/plants/{id}/journal/new', name: 'journal_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Plant $plant, Request $request, #[CurrentUser] User $user, PhotoStorage $photos, HouseholdClock $clock): Response
    {
        $this->guard(Permission::CARE, $plant);

        $entry = new JournalEntry($plant, $user, $clock->today($plant->getHousehold()));
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
        $this->guard(Permission::CARE, $entry);
        $isAuthor = $entry->getAuthor()?->getId() === $user->getId();
        if (!$isAuthor && !$this->isGranted(Permission::MANAGE, $entry)) {
            throw $this->createAccessDeniedException('Only the author or the household owner can delete this entry.');
        }
        $this->assertCsrf('delete-entry-'.$entry->getId(), $request);

        if (null !== $entry->getPhoto()) {
            $photos->delete($entry->getPhoto());
        }
        $this->em->remove($entry);
        $this->em->flush();
        $this->addFlash('success', 'Journal entry deleted.');

        return $this->redirectToRoute('journal_index', ['id' => $plant->getId()]);
    }
}
