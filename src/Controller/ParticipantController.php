<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\ParticipantType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/participant", name="participant")
// * @IsGranted("ROLE_USER")
 * Class ParticipantController
 * @package App\Controller
 */
class ParticipantController extends AbstractController
{
    /**
     * Affiche la liste des participants
     * @Route("/getList", name="_get_list")
//     * @IsGranted("ROLE_ADMIN")
     * @return Response
     */
    public function getList()
    {
        return $this->render('participant/getList.html.twig', [
            'title' => 'Participants',
        ]);
    }

    /**
     * @Route("/showProfile/{pseudoParticipant}", name="_show_profile")
     * Affiche le détail d'un profil
     * @param $pseudoParticipant
     * @return Response
     */   
    public function showProfile(Request $request, UserPasswordEncoderInterface $encoder, $pseudoParticipant = null)
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Participant
        $participantRepository = $em->getRepository(Participant::class);

        //Si aucun pseudo n'est renseigné, on récupère le profil de l'utilisateur connecté
        if(!$pseudoParticipant) {
            $title = "Mon profil";
            $oParticipant = $participantRepository->findOneBy(
                ['pseudo' => $this->getUser()->getPseudo()]
            );
            $isAuthorizedToModify = true;

            //Création du formulaire de modification
            $form = $this->createForm(ParticipantType::class, $oParticipant);
            $form->handleRequest($request);

            //Si le formulaire est soumit et est valide
            if($form->isSubmitted() && $form->isValid()) {                
                $photo = $form->get('photo')->getData();
                $oldMotDePasse = $form->get('motDePasse')->getData();
                $newMotDePasse = $form->get('newMotDePasse')->getData();

                //Si le mot de passe entré est valide, on peut effectuer les modifications
                if($encoder->isPasswordValid($oParticipant, $oldMotDePasse)) {   
                    //On récupère les données
                    $oParticipant = $form->getData();
                    //Si une photo a été transmise
                    if($photo) {
                        $originalFilename = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = transliterator_transliterate(
                            'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename
                        );
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$photo->guessExtension();

                        //On essaye de placer l'image dans le répertoire d'images
                        try {
                            $photo->move(
                                $this->getParameter('images_directory'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                            $this->addFlash('danger', $e->getMessage());
                            return $this->redirectToRoute('participant_show_profile');
                        }

                        //On enregistre le nom du fichier
                        $oParticipant->setNomFichierPhoto($newFilename);
                    }

                    //Si l'utilisateur souhaite changer son mot de passe, on le change
                    if($newMotDePasse) {
                        $newEncodedPassword = $encoder->encodePassword($oParticipant, $newMotDePasse);
                        $oParticipant->setPassword($newEncodedPassword);
                    }

                    //On sauvegarde
                    $em->persist($oParticipant);
                    $em->flush();

                    //On affiche un message de succès et on redirige vers cette même page
                    $this->addFlash('success', 'Les modifications ont bien été enregistrées');
                    return $this->redirectToRoute('participant_show_profile');
                } else {//Si le mot de passe n'est pas le bon
                    $this->addFlash('danger', 'Mot de passe incorrect !');
                    return $this->redirectToRoute('participant_show_profile');
                }
            }

            $formView = $form->createView();
        } else { //Sinon on récupère le participant en fonction du pseudo fournit
            $title = $pseudoParticipant;
            $oParticipant = $participantRepository->findOneBy(['pseudo' => $pseudoParticipant]);
            //Si on ne trouve pas de participant
            if(!$oParticipant) {
                //Affichage d'un message d'erreur et redirection vers la liste des sorties
                $this->addFlash('danger', 'L\'utilisateur recherché n\'existe pas');
                return $this->redirectToRoute('sortie_get_list');
            }
            $isAuthorizedToModify = false;
            $formView = null;
        }

        return $this->render('participant/showProfile.html.twig', [
            'title' => $title,
            'oParticipant' => $oParticipant,
            'isAuthorizedToModify' => $isAuthorizedToModify,
            'form' => $formView
        ]);
    }

    /**
     * Affiche la page de création/modification d'un participant
     * @Route("/getForm/{idParticipant}", name="_get_form")
     * @IsGranted("ROLE_ADMIN")
     * @param Request $request
     * @param $idParticipant
     * @return RedirectResponse|Response
     */
    public function getForm(Request $request, UserPasswordEncoderInterface $encoder, $idParticipant)
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Participant
        $participantRepository = $em->getRepository(Participant::class);

        //Si l'identifiant fournit est égal à -1, il s'agit d'une création
        if ($idParticipant == -1) {
            $oParticipant = new Participant();
            $title = "Création";
        } else { //Sinon, il sagit d'une modification
            $oParticipant = $participantRepository->findOneBy(['id' => $idParticipant]);
            $title = "Modification";
        }

        //Création du formulaire
        $form = $this->createForm(ParticipantType::class, $oParticipant);
        $form->handleRequest($request);

        //Si le formulaire est soumit et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            //On récupère les données et on hyddrate l'instance
            $oParticipant = $form->getData();
            $password = $form->get('motDePasse')->getData();

            if ((trim($password) != '') && $password != null) {
                $encodedPassword = $encoder->encodePassword($oParticipant, $password);
                $oParticipant->setPassword($encodedPassword);
            } else {
                $password = $oParticipant->getPassword();
                $oParticipant->setPassword($password);
            }

            //On sauvegarde
            $em->persist($oParticipant);
            $em->flush();

            //On affiche un message de succès et on redirige vers la page de gestion des participants
            if ($idParticipant == -1) {
                $this->addFlash('success', "Participant créé avec succès !");
            } else {
                $this->addFlash('success', "Participant modifié avec succès !");
            }
            return $this->redirectToRoute("participant_get_list");
        } else { //Si le formulaire n'est pas valide
            $errors = $this->getErrorsFromForm($form);

            //Pour chaque erreur, on affiche une alerte contenant le message
            foreach ($errors as $error) {
                $this->addFlash("danger", $error[0]);
            }
        }

        return $this->render('participant/getForm.html.twig', [
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/getListJson", name="_get_list_json")
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function getListJson()
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Participant
        $participantRepository = $em->getRepository(Participant::class);

        //Récupération des participants
        $toParticipant = $participantRepository->findAll();

        //Pour chaque inscription, on récupère les informations du participant
        foreach ($toParticipant as $oParticipant) {
            $t = array();
            $t['id'] = $oParticipant->getId();
            $t['pseudo'] =
                '<a type="button" href="' . $this->generateUrl('participant_show_profile', ['pseudoParticipant' => $oParticipant->getPseudo()]) . '" class="btn p-0" title="Voir le profil">'
                . $oParticipant->getPseudo()
                .'</a>';
            $t['nom'] = $oParticipant->getNom();
            $t['prenom'] = $oParticipant->getPrenom();
            $t['telephone'] = $oParticipant->getTelephone();
            $t['mail'] = $oParticipant->getMail();
            $t['campus'] = $oParticipant->getCampus()->getNom();
            $t['actions'] =
                '<a type="button" href="' . $this->generateUrl('participant_get_form', ['idParticipant' => $oParticipant->getId()]) . '" class="btn p-0" title="Modifier">'
                .' <i class="fa fa-edit"></i>'
                .'</a>';

            $array[] = $t;
        }

        return new JsonResponse($array);
    }

    /**
     * @Route("/getListJsonBySortie", name="_get_list_json_by_sortie")
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function getListJsonBySortie(Request $request)
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Sortie
        $sortieRepository = $em->getRepository(Sortie::class);

        //Récupération de l'identifiant de la sortie
        $idSortie = $request->get("idSortie");

        //Récupération de la sortie
        $oSortie = $sortieRepository->findOneBy(['id' => $idSortie]);
        //Si on ne trouve pas la sortie
        if (!$oSortie) {
            //On affiche un message d'erreur et on redirige vers la liste des sorties
            $this->addFlash('danger', "La sortie n'existe pas");
            return $this->redirectToRoute('sortie_get_list');
        }

        //Récupération des inscriptions à la sortie
        $toInscription = $oSortie->getInscriptions();
        $array= [];

        //Pour chaque inscription, on récupère les informations du participant
        foreach ($toInscription as $oInscription) {
            $oParticipant = $oInscription->getParticipant();

            $t = array();
            $t['pseudo'] =
                '<a type="button" href="' . $this->generateUrl('participant_show_profile', ['pseudoParticipant' => $oParticipant->getPseudo()]) . '" class="btn p-0" title="Voir le profil">'
                . $oParticipant->getPseudo()
                .'</a>';
            $t['nom'] = $oParticipant->getNom() . " " . $oParticipant->getNom();

            $array[] = $t;
        }

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
