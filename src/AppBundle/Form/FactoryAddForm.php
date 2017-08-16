<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 028 28.02.17
 * Time: 12:39
 */

namespace AppBundle\Form;


use AppBundle\Entity\FactoryEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FactoryAddForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add ('factoryName', TextType::class, array(
                'label' => 'factory.name',
                'attr' => array('size' => 30)
            ))
            ->add('submitButton', SubmitType::class, array('label' => 'Добавить завод'));
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => FactoryEntity::class,
            'validation_groups' => array('factory_addition'),
            'attr' => array('id' => 'factory_add_form')
        ));
    }
}


