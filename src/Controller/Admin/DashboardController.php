<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Lieu;
use App\Entity\Etat;
use App\Entity\Campus;
use App\Entity\Ville;
use App\Entity\Inscription;
use App\Entity\ResetPasswordRequest;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Sortir');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Etudiants', 'fa fa-users', Participant::class);
        yield MenuItem::linkToCrud('Sorties', 'fa fa-child', Sortie::class);
        yield MenuItem::linkToCrud('Lieux', 'fa fa-map', Lieu::class);
        yield MenuItem::linkToCrud('Villes', 'fa fa-industry', Ville::class);
        yield MenuItem::linkToCrud('Campus', 'fa fa-map-marker', Campus::class);






    }
}
