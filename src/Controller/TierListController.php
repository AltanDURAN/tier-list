<?php

namespace App\Controller;

use App\Entity\TierList;
use App\Entity\Tier;
use App\Entity\TierItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tierlist')]
class TierListController extends AbstractController
{
    #[Route('/', name: 'tierlist_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $tierLists = $em->getRepository(TierList::class)->findBy(['owner' => $this->getUser()]);

        return $this->render('tier_list/index.html.twig', [
            'tierLists' => $tierLists,
        ]);
    }

    #[Route('/create', name: 'tierlist_create')]
    public function create(EntityManagerInterface $em): Response
    {
        #$this->denyAccessUnlessGranted('ROLE_USER');

        $tierList = new TierList();
        $tierList->setName('Nouvelle Tier List');
        $tierList->setOwner($this->getUser());

        // Ajouter les tiers par défaut S, A, B, C, D
        $tiers = ['S', 'A', 'B', 'C', 'D'];
        foreach ($tiers as $position => $name) {
            $tier = new Tier();
            $tier->setName($name);
            $tier->setPosition($position);
            $tierList->addTier($tier);
        }

        $em->persist($tierList);
        $em->flush();

        return $this->redirectToRoute('tierlist_edit', ['id' => $tierList->getId()]);
    }

    #[Route('/{id}/edit', name: 'tierlist_edit')]
    public function edit(TierList $tierList): Response
    {
        #$this->denyAccessUnlessGranted('ROLE_USER');
        #$this->denyAccessUnlessGranted('ROLE_ADMIN', $tierList); // si tu veux restreindre l'accès au propriétaire ou admin

        return $this->render('tier_list/edit.html.twig', [
            'tierList' => $tierList,
        ]);
    }

    #[Route('/{id}/delete', name: 'tierlist_delete')]
    public function delete(TierList $tierList, EntityManagerInterface $em): Response
    {
        #$this->denyAccessUnlessGranted('ROLE_USER');

        $em->remove($tierList);
        $em->flush();

        return $this->redirectToRoute('tierlist_index');
    }
}
