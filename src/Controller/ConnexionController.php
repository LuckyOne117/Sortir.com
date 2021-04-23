<?php

namespace App\Controller;

use App\Entity\Participant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class ConnexionController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        if ($this->getUser()) {
             return $this->redirectToRoute('getList');
         }

        $em = $this->getDoctrine()->getManager();
        $participantRepository = $em->getRepository(Participant::class);

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
  //     return $this->render('sortie/getList.html.twig', [
        return $this->render('connexion/login.html.twig', [
            'title' => 'Connexion',
            'error' => $error,
            'lastUsername' => $lastUsername
        ]);

    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout() {

    }
}
