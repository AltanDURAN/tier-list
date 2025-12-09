<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\TierItem;

final class TierItemController extends AbstractController
{
    #[Route('/tier/item', name: 'app_tier_item')]
    public function index(): Response
    {
        return $this->render('tier_item/index.html.twig', [
            'controller_name' => 'TierItemController',
        ]);
    }
    
    #[Route('/tier-item/create', name: 'tier_item_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {

        $name = $request->request->get('name', 'unamed');
        $imageFile = $request->files->get('image');

        if (!$imageFile) {
            return new JsonResponse(['error' => 'Aucune image reçue'], 400);
        }

        // Nom fichier unique formaté
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $fileName = uniqid('tier_' . $cleanName . '_', true) . '.png';

        // On déplace dans /public/uploads/image
        $imageFile->move(
            $this->getParameter('kernel.project_dir') . '/public/uploads/image',
            $fileName
        );

        $tierList = $em->getRepository('App\Entity\TierList')->find($request->request->get('tierListId'));

        $item = new TierItem();
        $item->setName($name);
        $item->setImage('uploads/image/' . $fileName);
        $item->setPosition(null);
        $item->setTier(null);
        $item->setTierList($tierList);


        $em->persist($item);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'id' => $item->getId(),
            'name' => $item->getName(),
            'imageUrl' => '/' . $item->getImage()
        ]);
    }
}
