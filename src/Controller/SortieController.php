<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Inscription;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SortieType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Doctrine\ORM\QueryBuilder;

/**
 * @Route("/sortie", name="sortie")
 * @IsGranted("ROLE_USER")
 * Class SortieController
 * @package App\Controller
 */
class SortieController extends AbstractController
{
    /**
     * Affiche la page de la liste des sorties
     * @Route("/getList", name="_get_list")
     * @return Response
     */
    public function getList(Request $request)
    {
        return $this->render('sortie/getList.html.twig', [
            'title' => 'Liste des sorties'
        ]);
    }

    /**
     * Affiche la page de création/modification d'une sortie
     * @Route("/getForm/{idSortie}", name="_get_form")
     * @param Request $request
     * @param $idSortie
     * @return RedirectResponse|Response
     */
    public function getForm(Request $request, $idSortie)
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Sortie
        $sortieRepository = $em->getRepository(Sortie::class);
        //Récupération du repository de l'entité Etat
        $etatRepository = $em->getRepository(Etat::class);
        //Récupération du repository de l'entité Participant
        $participantRepository = $em->getRepository(Participant::class);

        //Récupération de l'utilisateur
        $oParticipant = $participantRepository->findOneBy(['pseudo' => $this->getUser()->getUsername()]);

        //Si l'identifiant de la sortie fournie est égal à -1, il s'agit d'une création
        if ($idSortie == -1) {
            $oSortie = new Sortie();
            $title = "Création";
        } else { //Sinon il s'agit d'une modification
            $oSortie = $sortieRepository->findOneBy(['id' => $idSortie]);
            $title = "Modification";
            //Si le participant qui essaye de modifier la sortie n'en est pas l'organisateur
            //On affiche un message d'erreur
            if ($oParticipant->getPseudo() != $oSortie->getOrganisateur()->getPseudo()) {
                $this->addFlash('danger', "Vous n'êtes pas l'organisateur de cette sortie !");
                return $this->redirectToRoute('sortie_get_list');
            }
        }

        //Création du formulaire
        $form = $this->createForm(SortieType::class, $oSortie);
        $form->handleRequest($request);

        //Si le formulaire est soumit en cliquant sur le bouton Enregistrer et est valide
        if ($form->isSubmitted() && $form->isValid() && $form->get('enregistrer')->isClicked()) {
            //On récupère les données et on hydrate l'instance
            $oSortie = $form->getData();
            $oSortie->setOrganisateur($oParticipant);

            //L'état de la sortie est à créé
            $oEtat = $etatRepository->findOneBy(['libelle' => "Créée"]);
            $oSortie->setEtat($oEtat);

            //On sauvegarde
            $em->persist($oSortie);
            $em->flush();

            //On affiche un message de succès et on redirige vers la page des sorties
            $this->addFlash('success', 'Sortie créée !');
            return $this->redirectToRoute("sortie_get_list");
        }
        //Si le formulaire est soumit en cliquant sur le bouton Publier et est valide
        elseif ($form->isSubmitted() && $form->isValid() && $form->get('publier')->isClicked()) {
            //On récupère les données et on hydrate l'instance
            $oSortie = $form->getData();
            $oSortie->setOrganisateur($oParticipant);

            //L'état de la sortie est à ouvert
            $oEtat = $etatRepository->findOneBy(['libelle' => "Ouverte"]);
            $oSortie->setEtat($oEtat);

            //On sauvegarde
            $em->persist($oSortie);
            $em->flush();

            //On affiche un message de succès et on redirige vers la page des sorties
            $this->addFlash('success', 'Sortie publiée et ouverte à l\'inscription !');
            return $this->redirectToRoute("sortie_get_list");
        } else { //Si le formulaire n'est pas valide
            $errors = $this->getErrorsFromForm($form);

            //Pour chaque erreur, on affiche une alerte contenant le message
            foreach ($errors as $error) {
                $this->addFlash("danger", $error[0]);
            }
        }

        return $this->render('sortie/getForm.html.twig', [
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * Affiche la page de détail d'une sortie
     * @Route("/getPage/{idSortie}", name="_get_page")
     * @param $idSortie
     * @return RedirectResponse|Response
     */
    public function getPage($idSortie)
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Sortie
        $sortieRepository = $em->getRepository(Sortie::class);

        //Récupération de la sortie demandée
        $oSortie = $sortieRepository->findOneBy(['id' => $idSortie]);
        //Si la sortie n'existe pas
        if (!$oSortie) {
            //Affichage d'un message d'erreur et redirection vers la liste des sorties
            $this->addFlash('danger', "La sortie recherchée n'existe pas");
            return $this->redirectToRoute('sortie_get_list');
        }

        return $this->render('sortie/getPage.html.twig', [
            'title' => "Détail sortie",
            'oSortie' => $oSortie
        ]);
    }

    /**
     * Permet à l'utilisateur d'annuler une sortie qu'il a organisé
     * @Route("/cancel/{idSortie}", name="_cancel")
     * @param $idSortie
     * @return RedirectResponse
     */
    public function cancel($idSortie) {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Sortie
        $sortieRepository = $em->getRepository(Sortie::class);
        //Récupération du repository de l'entité Participant
        $participantRepository = $em->getRepository(Participant::class);
        //Récupération du repository de l'entité Etat
        $etatRepository = $em->getRepository(Etat::class);
        
        //Récupération de la sortie à annuler
        $oSortie = $sortieRepository->findOneBy(['id' => $idSortie]);
        //Récupération de l'utilisateur
        $oParticipant = $participantRepository->findOneBy(['pseudo' => $this->getUser()->getPseudo()]);
        //Récupération de l'état annulé
        $oEtat = $etatRepository->findOneBy(['libelle' => "Annulée"]);

        //On vérifie qu'il existe bien une sortie et que son état est soit :
        //Créé, Ouvert, Clôturé 
        if($oSortie && ($oSortie->getEtat()->getLibelle() != "Passée" || $oSortie->getEtat()->getLibelle() != "En cours")) {
            //On vérifie si l'utilisateur est bien l'organisateur de la sortie
            if($oParticipant->getPseudo() == $oSortie->getOrganisateur()->getPseudo()) {
                $oSortie->setEtat($oEtat);
                $em->persist($oSortie);
                $em->flush();

                //Affichage d'un message de succès et redirection vers la liste des sorties
                $this->addFlash('warning', "Annulation de la sortie effectuée !");
                return $this->redirectToRoute('sortie_get_list');
            } else {//Affichage d'un message d'erreur et redirection vers la liste des sorties
                $this->addFlash('danger', "Vous n'êtes pas l'organisateur de cette sortie !");
                return $this->redirectToRoute('sortie_get_list');
            }
        } else {//On affiche un message d'erreur et on redirige vers la liste des sorties
            $this->addFlash('danger', "Une erreur s'est produite ! Réessayez ultérieurement");
            return $this->redirectToRoute('sortie_get_list');
        }
    }

    /**
     * Récupère la liste des sorties au format JSON
     * @Route("/getListJson", name="_get_list_json")
     * @return JsonResponse
     */
    public function getListJson()
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Sortie
        $sortieRepository = $em->getRepository(Sortie::class);
        //Récupération du repository de l'entité Inscription
        $inscriptionRepository = $em->getRepository(Inscription::class);
        //Récupération du repository de l'entité Participant
        $participantRepository = $em->getRepository(Participant::class);

        //Récupération de l'utilisateur connecté
        $user = $participantRepository->findOneBy(['pseudo' => $this->getUser()->getUsername()]);

        //Définition du tableau final à retourner
        $array = [];

        $toSortie = $sortieRepository->findAll();

        //Pour chaque sortie, on définit un tableau contenant les informations que l'on souhaite
        foreach ($toSortie as $oSortie) {
            $t = array();
            //On récupère l'identifiant de la sortie
            $t['id'] = $oSortie->getId();
            //On récupère le nom
            $t['nom'] = $oSortie->getNom();

            //On récupère la date de début de la sortie et on la transforme en chaîne de caractère
            $t['dateDebut'] = $oSortie->getDateDebut()->format('d/m/Y H:i');
            //On récupère la date de cloture des inscriptions et on la transforme en chaîne de caractère
            $t['dateCloture'] = $oSortie->getDateCloture()->format('d/m/Y H:i');

            //Récupération du nombre d'inscription sur le nombre de places maximum
            $t['nbMaxInscriptions'] =
                sizeof($oSortie->getInscriptions()->toArray())
                . '/'
                . $oSortie->getNbInscriptionsMax();

            //On vérifie si l'utilisateur est inscrit à la sortie
            $isInscrit = $inscriptionRepository->findBy(
                ['participant' => $user, 'sortie' => $oSortie]
            );
            //S'il est inscrit, on retourne une icône indiquant qu'il est inscrit
            if ($isInscrit) {
                $t['isInscrit'] = '<i class="fas fa-check"></i>';
            } else { //Sinon on laisse le champs vide
                $t['isInscrit'] = "";
            }

            //On récupère le libellé de l'état de la sortie
            $t['etat'] = $oSortie->getEtat()->getLibelle();
            //On récupère le pseudo de l'organisateur de la sortie
            $t['organisateur'] = 
                '<a type="button" href="'. $this->generateUrl('participant_show_profile', ['pseudoParticipant' => $oSortie->getOrganisateur()->getPseudo()]).'" class="btn p-0" title="Voir le profil">'
                . $oSortie->getOrganisateur()->getPseudo()
                .'</a>';

            $t['actions'] =
                '<a type="button" href="'. $this->generateUrl('sortie_get_page', ['idSortie' => $oSortie->getId()]).'" class="btn p-0" title="Afficher">'
                .'<i class="fas fa-search"></i>'
                .'</a>';

            //Si l'utilisateur est organisateur de la sortie et que l'état de la sortie est à créé,
            //l'utilisateur peut toujours la modifier ou la supprimer
            if (
                ($user->getPseudo() == $oSortie->getOrganisateur()->getPseudo())
                &&
                ($oSortie->getEtat()->getLibelle() == "Créée")
            ) {
                $t['actions'] .=
                    '<a type="button" href="'. $this->generateUrl('sortie_get_form', ['idSortie' => $oSortie->getId()]).'" class="btn p-0" title="Modifier">'
                    .'<i class="fas fa-edit"></i>'
                    .'</a>'
                    .'<a type="button" href="'. $this->generateUrl('sortie_cancel', ['idSortie' => $oSortie->getId()]).'" class="btn p-0" title="Annuler">'
                    .'<i class="fas fa-trash"></i>'
                    .'</a>';
            }
            //Si l'utilisateur est organisateur de la sortie et que l'état de la sortie est à ouvert,
            //l'utilisateur peut toujours la supprimer
            elseif (
                ($user->getPseudo() == $oSortie->getOrganisateur()->getPseudo())
                &&
                ($oSortie->getEtat()->getLibelle() == "Ouverte")
            ) {
                $t['actions'] .=
                    '<a type="button" href="'. $this->generateUrl('sortie_cancel', ['idSortie' => $oSortie->getId()]).'" class="btn p-0" title="Annuler">'
                    .'<i class="fas fa-trash"></i>'
                    .'</a>';
            }
            //Si l'utilisateur est organisateur de la sortie et que l'état de la sortie est à clôturé,
            //l'utilisateur peut seulement supprimer la sortie
            elseif (
                ($user->getPseudo() == $oSortie->getOrganisateur()->getPseudo())
                &&
                ($oSortie->getEtat()->getLibelle() == "Cloturée")
            ) {
                $t['actions'] .=
                    '<a type="button" href="'. $this->generateUrl('sortie_cancel', ['idSortie' => $oSortie->getId()]).'" class="btn p-0" title="Annuler">'
                    .'<i class="fas fa-trash"></i>'
                    .'</a>';
            }
            //Si l'utilisateur est inscrit à une sortie et que la sortie n'est pas annulée, passée ou en
            //cours d'activité, il peut se désister
            elseif (
                ($isInscrit && $oSortie->getEtat()->getLibelle() != "Annulée") ||
                ($isInscrit && $oSortie->getEtat()->getLibelle() != "Passée") ||
                ($isInscrit && $oSortie->getEtat()->getLibelle() != "En cours")
            ) {
                $t['actions'] .=
                    '<a type="button" href="'. $this->generateUrl('inscription_withdraw', ['idSortie' => $oSortie->getId()]) .'" class="btn p-0" title="Se désister">'
                    .'<i class="far fa-times-circle"></i>'.
                    '</a>';
            }
            //Si l'utilisateur n'est pas inscrit et que l'état de la sortie est ouvert, il peut s'inscrire
            elseif (!$isInscrit && $oSortie->getEtat()->getLibelle() == "Ouverte") {
                $t['actions'] .=
                    '<a type="button" href="'. $this->generateUrl('inscription_registrate', ['idSortie' => $oSortie->getId()]) .'" class="btn p-0" title="S\'inscrire">'
                    .'<i class="far fa-check-square"></i>'
                    .'</a>';
            }

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
