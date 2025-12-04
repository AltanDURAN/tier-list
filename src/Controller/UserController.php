<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserController extends AbstractController
{
    #[Route('/user/create', name: 'user_create')]
    #[Route('/admin/create', name: 'admin_create')]
    public function create(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();

        // Déterminer le rôle et le label selon la route
        $routeName = $request->attributes->get('_route');
        $role = $routeName === 'admin_create' ? 'ROLE_ADMIN' : 'ROLE_USER';
        $label = $role === 'ROLE_ADMIN' ? 'Créer admin' : 'Créer utilisateur';

        $form = $this->createFormBuilder($user)
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('save', SubmitType::class, ['label' => $label])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword($user, $user->getPassword())
            );
            $user->setRoles([$role]);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', $label . ' créé !');

            return $this->redirectToRoute('dashboard');
        }

        return $this->render('user/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users', name: 'user_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN'); // Seuls les admins peuvent voir la liste

        $users = $em->getRepository(User::class)->findAll(); // Récupère tous les utilisateurs

        return $this->render('user/list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/{id}/edit', name: 'user_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $form = $this->createFormBuilder($user)
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class, [
                'required' => false,
                'empty_data' => '',
                'help' => 'Laissez vide pour ne pas changer le mot de passe'
            ])
            ->add('save', SubmitType::class, ['label' => 'Mettre à jour'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getPassword() !== null && $user->getPassword() !== '') {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $user->getPassword())
                );
            }
            $em->flush();

            $this->addFlash('success', 'Utilisateur mis à jour !');
            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/user/{id}/delete', name: 'user_delete')]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé !');
        return $this->redirectToRoute('user_list');
    }

}
