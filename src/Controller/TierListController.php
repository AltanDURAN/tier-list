<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\TierList;
use App\Entity\Tier;
use App\Entity\TierItem;

use App\Repository\TierListRepository;
use App\Repository\TierRepository;

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

        // Ajouter les tiers par défaut à la nouvelle Tier List
        $defaultTiersName = ['S', 'A', 'B', 'C', 'D'];

        foreach ($defaultTiersName as $key => $tierName) {
            $defaultTier = new Tier();
            $defaultTier->setName($tierName);
            $defaultTier->setPosition($key);
            $defaultTier->setTierList($tierList);
            $em->persist($defaultTier);

            $tierList->addTier($defaultTier);
        }

        // Sauvegarder la Tier List avec les tiers
        $em->persist($tierList);
        $em->flush();

        return $this->redirectToRoute('tierlist_edit', ['id' => $tierList->getId()]);
    }

    #[Route('/{id}/edit', name: 'tierlist_edit')]
    public function edit(TierList $tierList, TierRepository $tierRepo,): Response
    {
        #$this->denyAccessUnlessGranted('ROLE_USER');
        #$this->denyAccessUnlessGranted('ROLE_ADMIN', $tierList); // si tu veux restreindre l'accès au propriétaire ou admin
        $tiers = $tierRepo->findByTierListOrderedByPosition($tierList->getId());

        return $this->render('tier_list/edit.html.twig', [
            'tierList' => $tierList,
            'tiers' => $tiers,
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

    #[Route('/update-name/{id}', name: 'tierlist_update_name', methods: ['POST'])]
    public function updateTierListName(
        Request $request, 
        TierListRepository $repo,
        EntityManagerInterface $em,
        int $id
    ): JsonResponse {
        $tierList = $repo->find($id);

        if (!$tierList) {
            return new JsonResponse(['error' => 'TierList not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $name = trim($data['name']);

        if ($name === '') {
            $name = 'Nouvelle Tier List';
        }

        if (strlen($name) > 255) {
            $name = 'Nom de la Tier List trop long';
        }

        $tierList->setName($name);

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/tiers-html', name: 'tierlist_partial_tiers')]
    public function partialTiers(TierList $tierList, TierRepository $repo): Response
    {
        $tiers = $repo->findByTierListOrderedByPosition($tierList->getId());

        return $this->render('tier_list/_tiers.html.twig', [
            'tiers' => $tiers,
        ]);
    }

    #[Route('/{id}/tiers-html', name: 'tierlist_tiers_html', methods: ['GET'])]
    public function tiersHtml(TierList $tierList, TierRepository $tierRepo): Response
    {
        $tiers = $tierRepo->findByTierListOrderedByPosition($tierList->getId());

        return $this->render('tier_list/_tiers.html.twig', [
            'tierList' => $tierList,
            'tiers' => $tiers,
        ]);
    }
}
