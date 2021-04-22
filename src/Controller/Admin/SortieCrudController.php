<?php

namespace App\Controller\Admin;

use App\Entity\Sortie;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;


class SortieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sortie::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('nom'),
            TextEditorField::new('descriptionInfos'),
            DateField::new('dateDebut'),
            DateField::new('dateCloture'),
            TimeField::new('duree'),
            IntegerField::new('nbInscriptionsMax'),
            //DateField::new('createdAt')->hideOnForm(),
            TextEditorField::new('descriptionInfos'),
            TextField::new('organisateur'),
        ];
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
->setDefaultSort(['id' => 'DESC', 'nom' => 'ASC', 'dateDebut' => 'DESC'])
            //      ->setDefaultSort(['CreatedAt'=>'DESC']);
            ->setSearchFields(['nom', 'description', 'dateDebut']);

    }

}
