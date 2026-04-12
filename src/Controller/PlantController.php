<?php

namespace App\Controller;

use App\Care\PlantSorter;
use App\Care\Propagator;
use App\Care\ScheduleFactory;
use App\Entity\JournalEntry;
use App\Entity\Plant;
use App\Entity\User;
use App\Form\JournalEntryType;
use App\Form\PlantType;
use App\Household\CurrentHousehold;
use App\Household\HouseholdClock;
use App\Photo\InvalidPhotoException;
use App\Photo\PhotoStorage;
use App\Repository\CareEventRepository;
use App\Repository\JournalEntryRepository;
use App\Repository\PlantRepository;
use App\Repository\RoomRepository;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/plants')]
final class PlantController extends AppController
{
    public function __construct(
        private readonly CurrentHousehold $current,
        private readonly HouseholdClock $clock,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'plant_index', methods: ['GET'])]
    public function index(Request $request, PlantRepository $plants, RoomRepository $rooms, PlantSorter $sorter): Response
    {
        $household = $this->current->get();
        $allRooms = $rooms->findForHousehold($household);
        $selected = null;
        $roomId = $request->query->getInt('room');
        foreach ($allRooms as $room) {
            if ($room->getId() === $roomId) {
                $selected = $room;
            }
        }

        $query = $request->query->getString('q');
        $toxicOnly = $request->query->getBoolean('toxic');
        $sort = $request->query->getString('sort', PlantSorter::NAME);
        $sort = \array_key_exists($sort, PlantSorter::ALL) ? $sort : PlantSorter::NAME;

        return $this->render('plant/index.html.twig', [
            'plants' => $sorter->sort(array_values(array_filter(
                $plants->findActive($household, $selected, $query),
                static fn (Plant $p): bool => !$toxicOnly || true === $p->getSpecies()?->isToxicToPets(),
            )), $sort),
            'toxic_only' => $toxicOnly,
            'sort' => $sort,
            'sorts' => PlantSorter::ALL,
            'rooms' => $allRooms,
            'selected_room' => $selected,
            'query' => $query,
        ]);
    }

    #[Route('/archived', name: 'plant_archived', methods: ['GET'])]
    public function archived(PlantRepository $plants): Response
    {
        return $this->render('plant/archived.html.twig', [
            'plants' => $plants->findArchived($this->current->get()),
        ]);
    }

    #[Route('/new', name: 'plant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ScheduleFactory $schedules, PhotoStorage $photos): Response
    {
        $household = $this->current->get();
        $this->guard(Permission::EDIT, $household);
        $plant = new Plant($household);
        $form = $this->createForm(PlantType::class, $plant, ['household' => $household]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->handleCover($form, $plant, $photos)) {
            $schedules->createDefaults($plant, $this->clock->today($household));
            $this->em->persist($plant);
            $this->em->flush();
            $this->addFlash('success', \sprintf('%s has been added.', $plant->getName()));

            return $this->redirectToRoute('plant_show', ['id' => $plant->getId()]);
        }

        return $this->render('plant/new.html.twig', [
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}', name: 'plant_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Plant $plant, CareEventRepository $events, JournalEntryRepository $journal): Response
    {
        $this->guard(Permission::VIEW, $plant);
        $form = $this->createForm(JournalEntryType::class, new JournalEntry($plant, null, $this->clock->today($plant->getHousehold())), [
            'action' => $this->generateUrl('journal_new', ['id' => $plant->getId()]),
        ]);

        return $this->render('plant/show.html.twig', [
            'plant' => $plant,
            'events' => $events->findRecentForPlant($plant),
            'entries' => $journal->findLatestForPlant($plant),
            'form' => $form,
        ]);
    }

    #[Route('/{id}/history', name: 'plant_history', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function history(Plant $plant, Request $request, CareEventRepository $events): Response
    {
        $this->guard(Permission::VIEW, $plant);

        return $this->render('plant/history.html.twig', [
            'plant' => $plant,
            'page' => $events->pageForPlant($plant, $request->query->getInt('page', 1)),
        ]);
    }

    #[Route('/{id}/edit', name: 'plant_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Plant $plant, Request $request, PhotoStorage $photos): Response
    {
        $this->guard(Permission::EDIT, $plant);
        $form = $this->createForm(PlantType::class, $plant, ['household' => $plant->getHousehold()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->handleCover($form, $plant, $photos)) {
            $this->em->flush();
            $this->addFlash('success', 'Changes saved.');

            return $this->redirectToRoute('plant_show', ['id' => $plant->getId()]);
        }

        return $this->render('plant/edit.html.twig', [
            'plant' => $plant,
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/archive', name: 'plant_archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function archive(Plant $plant, Request $request): Response
    {
        $this->guard(Permission::EDIT, $plant);
        $this->assertCsrf('archive-plant-'.$plant->getId(), $request);

        $plant->archive();
        $this->em->flush();
        $this->addFlash('success', \sprintf('%s was archived. You can restore it from the archive.', $plant->getName()));

        return $this->redirectToRoute('plant_index');
    }

    #[Route('/{id}/propagate', name: 'plant_propagate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function propagate(Plant $plant, Request $request, Propagator $propagator, #[CurrentUser] User $user): Response
    {
        $this->guard(Permission::EDIT, $plant);
        $this->assertCsrf('propagate-plant-'.$plant->getId(), $request);

        $baby = $propagator->propagate($plant, $user, $this->clock->today($plant->getHousehold()));
        $this->addFlash('success', \sprintf('Welcome, %s! Rename it and give it its own spot.', $baby->getName()));

        return $this->redirectToRoute('plant_edit', ['id' => $baby->getId()]);
    }

    #[Route('/{id}/restore', name: 'plant_restore', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function restore(Plant $plant, Request $request): Response
    {
        $this->guard(Permission::EDIT, $plant);
        $this->assertCsrf('restore-plant-'.$plant->getId(), $request);

        $plant->restore();
        $this->em->flush();
        $this->addFlash('success', \sprintf('%s is back.', $plant->getName()));

        return $this->redirectToRoute('plant_show', ['id' => $plant->getId()]);
    }

    /**
     * @param FormInterface<Plant> $form
     */
    private function handleCover(FormInterface $form, Plant $plant, PhotoStorage $photos): bool
    {
        $upload = $form->get('coverFile')->getData();
        if (!$upload instanceof UploadedFile) {
            return true;
        }

        try {
            $previous = $plant->getCoverPhoto();
            $plant->setCoverPhoto($photos->store($upload));
            if (null !== $previous) {
                $photos->delete($previous);
            }
        } catch (InvalidPhotoException $e) {
            $form->get('coverFile')->addError(new FormError($e->getMessage()));

            return false;
        }

        return true;
    }
}
