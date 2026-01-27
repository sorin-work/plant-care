<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use App\Household\CurrentHousehold;
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
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/edit', name: 'room_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Room $room, Request $request): Response
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
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
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
