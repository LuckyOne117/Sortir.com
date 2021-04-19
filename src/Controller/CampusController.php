<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Form\CampusType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/campus", name="campus")
 * @IsGranted("ROLE_ADMIN")
 * Class CampusController
 * @package App\Controller
 */
class CampusController extends AbstractController
{
    /**
     * Affiche la liste des campus
     * @Route("/getList", name="_get_list")
     * @return Response
     */
    public function getList()
    {
        return $this->render('campus/getList.html.twig', [
            'title' => 'Campus',
        ]);
    }

    /**
     * Affiche la page de création/modification d'un campus
     * @Route("/getForm/{idCampus}", name="_get_form")
     * @param Request $request
     * @param $idVille
     * @return RedirectResponse|Response
     */
    public function getForm(Request $request, $idCampus)
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Ville
        $campusRepository = $em->getRepository(Campus::class);

        //Si l'identifiant du campus fournit est égal à -1, il s'agit d'une création
        if ($idCampus == -1) {
            $oCampus = new Campus();
            $title = "Création";
        } else { //Sinon il s'agit d'une modification
            $oCampus = $campusRepository->findOneBy(['id' => $idCampus]);
            $title = "Modification";
        }

        //Création du formulaire
        $form = $this->createForm(CampusType::class, $oCampus);
        $form->handleRequest($request);

        //Si le formulaire est soumit et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            //On récupère les données et on hydrate l'instance
            $oCampus = $form->getData();

            //On sauvegarde
            $em->persist($oCampus);
            $em->flush();

            //On affiche un message de succès et on redirige vers la liste des villes
            $this->addFlash('success', 'Campus créé !');
            return $this->redirectToRoute("campus_get_list");
        } else { //Si le formulaire n'est pas valide
            $errors = $this->getErrorsFromForm($form);

            //Pour chaque erreur, on affiche une alerte contenant le message
            foreach ($errors as $error) {
                $this->addFlash("danger", $error[0]);
            }
        }

        return $this->render('campus/getForm.html.twig', [
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * Récupère la liste des campus au format JSON
     * @Route("/getListJson", name="_get_list_json")
     * @return JsonResponse
     */
    public function getListJson()
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Campus
        $campusRepository = $em->getRepository(Campus::class);

        //Définition du tableau final à retourner
        $array = [];

        $toCampus = $campusRepository->findAll();

        //Pour chaque ville, on définit un tableau contenant les informations que l'on souhaite
        foreach ($toCampus as $oCampus) {
            $t = array();
            //On récupère l'identifiant d'un campus
            $t['id'] = $oCampus->getId();
            //On récupère le nom
            $t['nom'] = $oCampus->getNom();

            $t['actions'] =
                '<a type="button" href="'. $this->generateUrl('campus_get_form', ['idCampus' => $oCampus->getId()]) .'" class="btn p-0" title="Modifier">'
                .'<i class="fas fa-edit"></i>'
                .'</a>';

            //On stocke les informations dans le tableau final
            $array[] = $t;
        }

        //On retourne le tableau au format JSON
        return new JsonResponse($array);
    }

    /**
     * Permet de récupérer les erreurs lors de la soumission d'un formulaire non valide
     * @param FormInterface $form
     * @return array
     */
    private function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }
}
