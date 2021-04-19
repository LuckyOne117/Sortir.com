<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/lieu", name="lieu")
 * @IsGranted("ROLE_USER")
 * Class LieuController
 * @package App\Controller
 */
class LieuController extends AbstractController
{
    /**
     * @Route("/getForm", name="_get_form")
     * @param Request $request
     * @return Response
     */
    public function getForm(Request $request)
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entity Lieu
        $lieuRepository = $em->getRepository(Lieu::class);

        //Création d'un nouveau lieu
        $oLieu = new Lieu();

        //Création du formulaire
        $form = $this->createForm(LieuType::class, $oLieu);
        $form->handleRequest($request);

        //Si le formulaire est soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            //On récupère les données et on hydrate l'instance
            $oLieu = $form->getData();

            //On sauvegarde
            $em->persist($oLieu);
            $em->flush();

            $this->addFlash('success', 'Lieu ajouté !');
            return $this->redirectToRoute('sortie_get_form', ['idSortie' => -1]);
        }

        return $this->render('lieu/getForm.html.twig', [
            'title' => "Création d'un lieu",
            'form' => $form->createView()
        ]);
    }
}
