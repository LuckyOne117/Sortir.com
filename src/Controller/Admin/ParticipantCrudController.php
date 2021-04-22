<?php

namespace App\Controller\Admin;

use App\Entity\Participant;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;

class ParticipantCrudController extends AbstractCrudController
{
    public function configureCrud(Crud $crud): Crud
{
    return $crud
        // the names of the Doctrine entity properties where the search is made on
        // (by default it looks for in all properties)
        // use dots (e.g. 'seller.email') to search in Doctrine associations
        ->setSearchFields(['nom', 'prenom','pseudo', 'seller.mail', 'telephone', 'campus'])
        // set it to null to disable and hide the search box


        // defines the initial sorting applied to the list of entities
        // (user can later change this sorting by clicking on the table columns)
        ->setDefaultSort(['nom' => 'ASC'])
       // ->setDefaultSort(['id' => 'DESC', 'nom' => 'ASC'])

        // the max number of entities to display per page
        ->setPaginatorPageSize(30)
        // the number of pages to display on each side of the current page
        // e.g. if num pages = 35, current page = 7 and you set ->setPaginatorRangeSize(4)
        // the paginator displays: [Previous]  1 ... 3  4  5  6  [7]  8  9  10  11 ... 35  [Next]
        // set this number to 0 to display a simple "< Previous | Next >" pager
        ->setPaginatorRangeSize(4)

        // these are advanced options related to Doctrine Pagination
        // (see https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/tutorials/pagination.html)
        ->setPaginatorUseOutputWalkers(true)
        ->setPaginatorFetchJoinCollection(true)
        ;
}
    public static function getEntityFqcn(): string
    {
        return Participant::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('pseudo'),
            TextField::new('nom'),
            TextField::new('prenom'),
            ChoiceField::new('campus')
                     ->allowMultipleChoices()
                     ->autocomplete()
                ->setChoices(['Nantes', 'Rennes', 'Niort'])
                     ,
            EmailField::new('mail'),
            TextField::new('telephone'),




        ];}







    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
