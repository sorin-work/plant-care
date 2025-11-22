<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use App\Household\CurrentHousehold;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rooms')]
final class RoomController extends AbstractController
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
        $this->assertInCurrentHousehold($room);
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
        $this->assertInCurrentHousehold($room);
        if (!$this->isCsrfTokenValid('delete-room-'.$room->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->em->remove($room);
        $this->em->flush();
        $this->addFlash('success', \sprintf('Removed %s.', $room->getName()));

        return $this->redirectToRoute('room_index');
    }

    private function assertInCurrentHousehold(Room $room): void
    {
        if ($room->getHousehold()->getId() !== $this->current->get()->getId()) {
            throw $this->createNotFoundException();
        }
    }
}
