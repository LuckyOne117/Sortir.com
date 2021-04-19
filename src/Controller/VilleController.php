<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/ville", name="ville")
 * @IsGranted("ROLE_ADMIN")
 * Class VilleController
 * @package App\Controller
 */
class VilleController extends AbstractController
{
    /**
     * Affiche la liste des villes
     * @Route("/getList", name="_get_list")
     * @return Response
     */
    public function getList()
    {
        return $this->render('ville/getList.html.twig', [
            'title' => 'Villes',
        ]);
    }

    /**
     * Affiche la page de création/modification d'une ville
     * @Route("/getForm/{idVille}", name="_get_form")
     * @param Request $request
     * @param $idVille
     * @return RedirectResponse|Response
     */
    public function getForm(Request $request, $idVille)
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Ville
        $villeRepository = $em->getRepository(Ville::class);

        //Si l'identifiant de la ville fournie est égal à -1, il s'agit d'une création
        if ($idVille == -1) {
            $oVille = new Ville();
            $title = "Création";
        } else { //Sinon il s'agit d'une modification
            $oVille = $villeRepository->findOneBy(['id' => $idVille]);
            $title = "Modification";
        }

        //Création du formulaire
        $form = $this->createForm(VilleType::class, $oVille);
        $form->handleRequest($request);

        //Si le formulaire est soumit et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            //On récupère les données et on hydrate l'instance
            $oVille = $form->getData();

            //On sauvegarde
            $em->persist($oVille);
            $em->flush();

            //On affiche un message de succès et on redirige vers la liste des villes
            $this->addFlash('success', 'Ville créée !');
            return $this->redirectToRoute("ville_get_list");
        } else { //Si le formulaire n'est pas valide
            $errors = $this->getErrorsFromForm($form);

            //Pour chaque erreur, on affiche une alerte contenant le message
            foreach ($errors as $error) {
                $this->addFlash("danger", $error[0]);
            }
        }

        return $this->render('ville/getForm.html.twig', [
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * Récupère la liste des villes au format JSON
     * @Route("/getListJson", name="_get_list_json")
     * @return JsonResponse
     */
    public function getListJson()
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Ville
        $villeRepository = $em->getRepository(Ville::class);

        //Définition du tableau final à retourner
        $array = [];

        $toVille = $villeRepository->findAll();

        //Pour chaque ville, on définit un tableau contenant les informations que l'on souhaite
        foreach ($toVille as $oVille) {
            $t = array();
            //On récupère l'identifiant d'une ville
            $t['id'] = $oVille->getId();
            //On récupère le nom
            $t['nom'] = $oVille->getNom();
            //On récupère le code postal
            $t['codePostal'] = $oVille->getCodePostal();

            $t['actions'] = 
                '<a type="button" href="'. $this->generateUrl('ville_get_form', ['idVille' => $oVille->getId()]) .'" class="btn p-0" title="Modifier">'
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
