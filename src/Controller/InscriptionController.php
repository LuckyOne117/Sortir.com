<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Entity\Participant;
use App\Entity\Sortie;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/inscription", name="inscription")
 * @IsGranted("ROLE_USER")
 * Class InscriptionController
 * @package App\Controller
 */
class InscriptionController extends AbstractController
{
    /**
     * Permet à un participant de s'inscrire à une sortie
     * @Route("/registrate/{idSortie}", name="_registrate")
     * @param $idSortie
     * @return RedirectResponse
     */
    public function registrate($idSortie)
    {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Inscription
        $inscriptionRepository = $em->getRepository(Inscription::class);
        //Récupération du repository de l'entité Participant
        $participantRepository = $em->getRepository(Participant::class);
        //Récupération du repository de l'entité Sortie
        $sortieRepository = $em->getRepository(Sortie::class);

        //Récupération de la sortie à laquelle l'utilisateur souhaite s'inscrire
        $oSortie = $sortieRepository->findOneBy(['id' => $idSortie]);
        //Si la sortie n'existe pas
        if(!$oSortie) {
            //On affiche un message d'erreur et on redirige vers la liste des sorties
            $this->addFlash('danger', "La sortie à laquelle vous souhaitez vous inscrire n'existe pas");
            return $this->redirectToRoute('sortie_get_list');
        } else {
            //Si la sortie n'est pas ouverte aux inscriptions
            if(!$oSortie->getEtat()->getLibelle() == "Ouverte") {
                //On affiche un message d'erreur et on redirige vers la liste des sorties
                $this->addFlash('danger', "La sortie n'est pas encore ouverte aux inscriptions");
                return $this->redirectToRoute('sortie_get_list');
            }
        }

        //Récupération de l'utilisateur
        $oParticipant = $participantRepository->findOneBy(['pseudo' => $this->getUser()->getPseudo()]);

        //On vérifie si l'utilisateur n'est pas déjà inscrit à la sortie
        $isInscrit = $inscriptionRepository->findOneBy([
            'participant' => $oParticipant,
            'sortie' => $oSortie
        ]);
        //Si l'utilisateur est déjà inscrit, on lui fait savoir et on redirige vers la liste des sorties
        if($isInscrit) {
            $this->addFlash('info', "Vous êtes déjà inscrit à cette sortie");
            return $this->redirectToRoute('sortie_get_list');
        }

        //On créer l'inscription
        $oInscription = new Inscription();
        $oInscription->setParticipant($oParticipant);
        $oInscription->setSortie($oSortie);
        $oInscription->setDateInscription(new DateTime());

        //On sauvegarde
        $em->persist($oInscription);
        $em->flush();

        //Affichage d'un message de succès et redirection vers la liste des sorties
        $this->addFlash('success', "Vôtre inscription a été prise en compte");
        return $this->redirectToRoute('sortie_get_list');
    }

    /**
     * Permet à un utilisateur de se désister d'une sortie à laquelle il est inscrit
     * @Route("/withdraw/{idSortie}", name="_withdraw")
     * @param $idSortie
     * @return RedirectResponse
     */
    public function withdraw($idSortie) {
        //Récupération de l'entity manager
        $em = $this->getDoctrine()->getManager();
        //Récupération du repository de l'entité Inscription
        $inscriptionRepository = $em->getRepository(Inscription::class);
        //Récupération du repository de l'entité Participant
        $participantRepository = $em->getRepository(Participant::class);
        //Récupération du repository de l'entité Sortie
        $sortieRepository = $em->getRepository(Sortie::class);

        //Récupération de la sortie à laquelle l'utilisateur souhaite se désister
        $oSortie = $sortieRepository->findOneBy(['id' => $idSortie]);
        //Si la sortie n'existe pas
        if(!$oSortie) {
            //On affiche un message d'erreur et on redirige vers la liste des sorties
            $this->addFlash('danger', "La sortie à laquelle vous souhaitez vous désister n'existe pas");
            return $this->redirectToRoute('sortie_get_list');
        } else {
            //Si la sortie est en cours
            if($oSortie->getEtat()->getLibelle() == "En cours") {
                //On affiche un message d'erreur et on redirige vers la liste des sorties
                $this->addFlash('danger', "La sortie est en cours d'activité, vous ne pouvez vous désister");
                return $this->redirectToRoute('sortie_get_list');
            }
        }

        //Récupération de l'utilisateur
        $oParticipant = $participantRepository->findOneBy(['pseudo' => $this->getUser()->getPseudo()]);

        //On vérifie si l'utilisateur est bien inscrit à la sortie
        $oInscription = $inscriptionRepository->findOneBy([
            'participant' => $oParticipant,
            'sortie' => $oSortie
        ]);
        //Si l'utilisateur n'est pas inscrit, on lui affiche un message d'erreur et on redirige vers la liste des sorties
        if(!$oInscription) {
            $this->addFlash('danger', "Vous n'êtes pas inscrit à cette sortie");
            return $this->redirectToRoute('sortie_get_list');
        }

        //On supprime l'inscription et on sauvegarde
        $em->remove($oInscription);
        $em->flush();

        //Affichage d'un message de succès et redirection vers la liste des sorties
        $this->addFlash('success', "Vous êtes bien désinscrit");
        return $this->redirectToRoute('sortie_get_list');
    }
}
