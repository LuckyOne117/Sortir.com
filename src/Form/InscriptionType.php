<?php


namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class InscriptionType extends AbstractType

session_start();
include('bd/connexionDB.php'); // Fichier PHP contenant la connexion à votre BDD

    // S'il y a une session alors on ne retourne plus sur cette page
    if (isset($_SESSION['id'])){
    header('Location: index.php');
    exit;
    }

// Si la variable "$_Post" contient des informations alors on les traitres
if(!empty($_POST)){
    extract($_POST);
}
$valid = true;

// On se place sur le bon formulaire grâce au "name" de la balise "input"
if (isset($_POST['inscription'])){
    $nom = htmlentities(trim($nom)); // On récupère le nom
    $prenom = htmlentities(trim($prenom)); // on récupère le prénom
    $mail = htmlentities(strtolower(trim($mail))); // On récupère le mail
    $mdp = trim($mdp); // On récupère le mot de passe
    $confmdp = trim($confmdp); // On récupère la confirmation du mot de passe

//Vérification du nom
if(empty($nom)){
    $valid = false;
$er_nom = ("Le nom d' utilisateur ne peut pas être vide");
}

//Vérification du prénom
if(empty($prenom)){
            $valid = false;
$er_prenom = ("Le prenom d' utilisateur ne peut pas être vide");
}

// Vérification du mail
if(empty($mail)){
           $valid = false;
$er_mail = "Le mail ne peut pas être vide";

// On vérifit que le mail est dans le bon format
}elseif(!preg_match("/^[a-z0-9\-_.]+@[a-z]+\.[a-z]{2,3}$/i", $mail)){
            $valid = false;
$er_mail = "Le mail n'est pas valide";

}else{
// On vérifit que le mail est disponible
$req_mail = $DB->query("SELECT mail FROM utilisateur WHERE mail = ?",
                array($mail));

$req_mail = $req_mail->fetch();

if ($req_mail['mail'] <> ""){
$valid = false;
$er_mail = "Ce mail existe déjà";
}
}

// Vérification du mot de passe
if(empty($mdp)) {
 $valid = false;
$er_mdp = "Le mot de passe ne peut pas être vide";

}elseif($mdp != $confmdp){
$valid = false;
$er_mdp = "La confirmation du mot de passe ne correspond pas";
}

// Si toutes les conditions sont remplies alors on fait le traitement
if($valid){

    $mdp = crypt($mdp, "$6$rounds=5000$macleapersonnaliseretagardersecret$");
$date_creation_compte = date('Y-m-d H:i:s');

// On insert nos données dans la table utilisateur
$DB->insert("INSERT INTO utilisateur (nom, prenom, mail, mdp, date_creation_compte) VALUES(?, ?, ?, ?, ?)",
array($nom, $prenom, $mail, $mdp, $date_creation_compte));

 header('Location: index.php');
exit;
}
}
}
?>





    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pseudo', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 30
                ]
            ])
            ->add('nom', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 30
                ]
            ])
            ->add('prenom', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 30
                ]
            ])
            ->add('telephone', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 15
                ]
            ])
            ->add('mail', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 150
                ]
            ])
            ->add('motDePasse', PasswordType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'mapped' => false
            ])
            ->add('newMotDePasse', RepeatedType::class, [
                'mapped' => false,
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passes ne sont pas les mêmes',
                'options' => ['attr' => ['class' => 'form-control']],
                'required' => false,
            ])
            ->add('isAdministrateur')
            ->add('isActif')
            ->add('photo', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png'
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valable'
                    ])
                ]
            ])
            ->add('campus', EntityType::class, [
                'required' => true,
                'class' => Campus::class,
                'choice_label' => function ($campus) {
                    return $campus->getNom();
                },
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('enregistrer',SubmitType::class,[
                'label' => 'Enregister',
                'attr' => [
                    'class' => 'btn btn-success'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}