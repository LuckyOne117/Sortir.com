<?php
namespace App\EventSubscriber;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Lieu;
use App\Entity\Etat;
use App\Entity\Campus;
use App\Entity\Ville;
use App\Entity\Inscription;
use App\Entity\ResetPasswordRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EasyCorp\Bundle\EasyAdminBundle\BeforeEntityPersitedEvent;
use Symfony\Component\String\Slugger\SluggerInterface;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    private $slugger;
    private $security;

    public function _construct(SluggerInterface $slugger, Security $security)
    {
        $this->slugger= $slugger;
        $this->security= $security;
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforEntityPersistedEvent::class => ['setParticipantSlugAndUser '],
        ];
    }
    public function setParticipantSlugAndUser(BeforeEntityPersitedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof Participant)) {
            return;
        }

        $slug = $this->slugger->slug($entity->getPseudo());
        $entity->setSlug($slug);

        $user=$this->security->getUser();
        $entity->setUser($user);

    }

    /*public function setParticipantSlugAndDateAndUser(BeforeEntityPersitedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof Participant)) {
            return;
        }

        $slug = $this->slugger->slug($entity->getPseudo());
        $entity->setSlug($slug);

        $now=new DateTime('now');
        $entity->setCreatedAt($now);

        $user=$this->security->getUser();
        $entity->setUser($user);

    }*/
}