<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use App\Household\CurrentHousehold;
use App\Repository\PlantRepository;
use App\Repository\RoomRepository;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rooms')]
final class RoomController extends AppController
{
    public function __construct(
        private readonly CurrentHousehold $current,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'room_index', methods: ['GET', 'POST'])]
    public function index(Request $request, RoomRepository $rooms): Response
    {
        $household = $this->current->get();
        $room = new Room($household);
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guard(Permission::EDIT, $household);
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($room);
            $this->em->flush();
            $this->addFlash('success', \sprintf('Added %s.', $room->getName()));

            return $this->redirectToRoute('room_index');
        }

        return $this->render('room/index.html.twig', [
            'rooms' => $rooms->findForHousehold($household),
            'plant_counts' => $rooms->countPlants($household),
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/edit', name: 'room_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Room $room, Request $request, PlantRepository $plants): Response
    {
        $this->guard(Permission::EDIT, $room);
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Room renamed.');

            return $this->redirectToRoute('room_index');
        }

        return $this->render('room/edit.html.twig', [
            'room' => $room,
            'others' => array_values(array_filter($plants->findActive($room->getHousehold()), static fn ($p) => $p->getRoom()?->getId() !== $room->getId())),
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/plants', name: 'room_move_plants', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function movePlants(Room $room, Request $request, PlantRepository $plants): Response
    {
        $this->guard(Permission::EDIT, $room);
        $this->assertCsrf('move-plants-'.$room->getId(), $request);

        $ids = array_map('intval', $request->request->all('plants'));
        $moved = 0;
        foreach ([] === $ids ? [] : $plants->findBy(['id' => $ids, 'household' => $room->getHousehold()]) as $plant) {
            $plant->setRoom($room);
            ++$moved;
        }
        $this->em->flush();
        $this->addFlash('success', \sprintf('Moved %d %s to %s.', $moved, 1 === $moved ? 'plant' : 'plants', $room->getName()));

        return $this->redirectToRoute('room_edit', ['id' => $room->getId()]);
    }

    #[Route('/{id}/delete', name: 'room_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Room $room, Request $request): Response
    {
        $this->guard(Permission::EDIT, $room);
        $this->assertCsrf('delete-room-'.$room->getId(), $request);

        $this->em->remove($room);
        $this->em->flush();
        $this->addFlash('success', \sprintf('Removed %s.', $room->getName()));

        return $this->redirectToRoute('room_index');
    }
}
