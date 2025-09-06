<?php

namespace App\Form\Paiement;

use App\Entity\Paiement\CatalogueServices;
use App\Entity\Paiement\TypePaiement;
use App\Entity\References\TypesService;
use App\Entity\Paiement\CategoriesActivite;
use App\Entity\References\TypesDemandeur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CatalogueServicesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code_service')
            ->add('designation')
            ->add('montant_fcfa')
            ->add('note')
            ->add('type_service', EntityType::class, [
                'class' => TypesService::class,
'choice_label' => 'id',
            ])
            ->add('categorie_activite', EntityType::class, [
                'class' => CategoriesActivite::class,
'choice_label' => 'id',
            ])
            ->add('type_demandeur', EntityType::class, [
                'class' => TypesDemandeur::class,
'choice_label' => 'id',
            ])
            ->add('typePaiement', EntityType::class, [
                'class' => TypePaiement::class,
'choice_label' => 'libelle',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CatalogueServices::class,
        ]);
    }
}
