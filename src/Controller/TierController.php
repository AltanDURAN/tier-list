<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Repository\TierRepository;
use App\Repository\TierItemRepository;
use App\Entity\Tier;
use App\Entity\TierItem;

#[Route('/tier')]
final class TierController extends AbstractController
{
    #[Route('/update-item', name: 'tier_update_item', methods: ['POST'])]
    public function updateTierItem(
        Request $request,
        EntityManagerInterface $em,
        TierRepository $tierRepo,
        TierItemRepository $tierItemRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $itemIdFull = $data['itemId'];
        dump($itemIdFull);
        $parts = explode('_', $itemIdFull);
        $itemId = $parts[1];

        $fromTierId = $data['fromTierId'];
        dump($fromTierId);
        $toTierId = $data['toTierId'];
        dump($toTierId);

        // Récupérer les entités
        $item = $tierItemRepo->findOneBy(['id' => $itemId]); // ou selon ton ID réel

        if (!$item) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }  

        if ($fromTierId === 'unassigned' and $toTierId !== 'unassigned') {
            
            $newTier = $tierRepo->findOneBy(['id' => $toTierId]);

            if (!$newTier) {
                return new JsonResponse(['error' => 'Invalid data'], 400);
            }

            $newTier->addTierItem($item);
        }
        else {
            if ($fromTierId !== 'unassigned' and $toTierId === 'unassigned') {
                $oldTier = $tierRepo->findOneBy(['id' => $fromTierId]);

                if (!$oldTier) {
                    return new JsonResponse(['error' => 'Invalid data'], 400);
                }

                $oldTier->removeTierItem($item);
            }
            else {
                if ($fromTierId !== 'unassigned' and $toTierId !== 'unassigned') {
                    $oldTier = $tierRepo->findOneBy(['id' => $fromTierId]);
                    $newTier = $tierRepo->findOneBy(['id' => $toTierId]);

                    if (!$oldTier || !$newTier) {
                        return new JsonResponse(['error' => 'Invalid data'], 400);
                    }

                    $oldTier->removeTierItem($item);
                    $newTier->addTierItem($item);
                }
            }
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/update-name', name: 'tiers_update_name', methods: ['POST'])]
    public function updateName(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['tierId'], $data['name'])) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        $name = trim($data['name']);

        // 🔒 Validation
        if ($name === '') {
            $name = 'Tier sans nom';
        }

        if (mb_strlen($name) > 255) {
            $name = 'Nom de tier trop long';
        }

        // 🔎 On récupère le tier
        $tier = $em->getRepository(Tier::class)->find($data['tierId']);
        
        if (!$tier) {
            return new JsonResponse(['error' => 'Tier introuvable'], 404);
        }

        $tier->setName($name);
        
        $em->persist($tier);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'name' => $tier->getName(),
            'id' => $tier->getId()
        ]);
    }

    #[Route('/update-color', name: 'tier_update_color', methods: ['POST'])]
    public function updateColor(Request $request, EntityManagerInterface $em, TierRepository $tierRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $tier = $tierRepo->find($data['tierId']);
        if (!$tier) return $this->json(['error' => 'Tier not found'], 404);

        $tier->setColor($data['color']);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/update-positions', name: 'tier_update_positions', methods: ['POST'])]
    public function updatePositions(Request $request, EntityManagerInterface $em, TierRepository $tierRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['positions'])) {
            return $this->json(['error' => 'Positions manquantes'], 400);
        }

        foreach ($data['positions'] as $pos) {
            $tier = $tierRepo->find($pos['tierId']);
            if ($tier) {
                $tier->setPosition($pos['position']);
            }
        }

        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/add', name: 'tier_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em, TierRepository $tierRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $referenceTier = $tierRepo->find($data['referenceTierId']);
        if (!$referenceTier) return $this->json(['error' => 'Tier not found'], 404);

        $tierList = $referenceTier->getTierList();
        $position = $referenceTier->getPosition();
        if ($data['position'] === 'below') $position++;

        // Incrémenter les positions existantes
        $tiersToUpdate = $tierRepo->createQueryBuilder('t')
            ->andWhere('t.tierList = :list')
            ->andWhere('t.position >= :pos')
            ->setParameter('list', $tierList)
            ->setParameter('pos', $position)
            ->getQuery()
            ->getResult();

        foreach ($tiersToUpdate as $t) {
            $t->setPosition($t->getPosition() + 1);
        }

        // Créer le nouveau tier
        $newTier = new Tier();
        $newTier->setName('Nouveau Tier');
        $newTier->setPosition($position);
        $newTier->setTierList($tierList);
        $em->persist($newTier);
        $em->flush();

        // Retourner le HTML du nouveau tier pour insertion dynamique
        $html = $this->renderView('tier/_tier.html.twig', ['tier' => $newTier]);

        return $this->json(['id' => $newTier->getId(), 'html' => $html]);
    }

    #[Route('/{id}', name: 'tier_delete', methods: ['DELETE'])]
    public function delete(Tier $tier, EntityManagerInterface $em, TierRepository $tierRepo): JsonResponse
    {
        $tierList = $tier->getTierList();
        $deletedPosition = $tier->getPosition();

        // 1️⃣ Déplacer les positions des autres tiers vers le haut
        $tiersToUpdate = $tierRepo->createQueryBuilder('t')
            ->andWhere('t.tierList = :list')
            ->andWhere('t.position > :pos')
            ->setParameter('list', $tierList)
            ->setParameter('pos', $deletedPosition)
            ->getQuery()
            ->getResult();

        foreach ($tiersToUpdate as $t) {
            $t->setPosition($t->getPosition() - 1);
        }

        // 2️⃣ Récupérer les tierItems avant suppression
        $tierItemsData = [];
        foreach ($tier->getTierItems() as $item) {
            $tierItemsData[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'imageUrl' => '/' . $item->getImage(),
            ];
        }

        // 3️⃣ Supprimer le Tier
        $tierList->removeTier($tier);

        $em->persist($tierList);
        $em->flush();

        // 4️⃣ Retourner le succès + tierItems déplacés
        return $this->json([
            'success' => true,
            'tierItems' => $tierItemsData
        ]);
    }
}
