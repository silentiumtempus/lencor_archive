<?php

namespace App\Form;

use App\Entity\FactoryEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FactoryAddForm
 * @package App\Form
 */

class FactoryForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $function = $options['attr']['function'];
        $builder
            ->add('factoryName', TextType::class, array(
                'label' => 'factories.add.name.label',
                'attr' => array('size' => 30)
            ));
        switch ($function) {
            case 'add':
                $builder
                    ->add('submitButton', SubmitType::class, array('label' => 'facset.factory.create'));
                break;
            case 'edit':
                $builder
                    ->add('submitButton', SubmitType::class, array('label' => 'facset.factory.edit'))
                    ->add('cancelButton', ResetType::class, array('label' => 'facset.factory.cancel'));
                break;
        }
    }

    /**
     * @param OptionsResolver $resolver
     */

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => FactoryEntity::class,
            'validation_groups' => array('factory_addition'),
            'attr' => array('id' => 'factory_form'),
            'translation_domain' => 'facset'
        ));
    }
}
