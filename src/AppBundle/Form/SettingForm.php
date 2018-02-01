<?php

namespace AppBundle\Form;

use AppBundle\Entity\SettingEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SettingAddForm
 * @package AppBundle\Form
 */
class SettingForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $function = $options['attr']['function'];
        $builder
            ->add('settingName', TextType::class, array(
                'label' => 'settings.add.name.label',
                'attr' => array('size' => 30)
            ));
        switch ($function) {
            case 'add' :
                $builder
                    ->add('factory', EntityType::class, array(
                        'label' => 'factories.add.name.label',
                        'placeholder' => 'Выберите завод',
                        'class' => 'AppBundle:FactoryEntity',
                        'choice_label' => 'factoryName',
                    ))
                    ->add('submitButton', SubmitType::class, array('label' => 'button.setting.create'));
                break;
            case 'edit' :
                $builder
                    ->add('submitButton', SubmitType::class, array('label' => 'button.setting.edit'))
                    ->add('cancelButton', ResetType::class, array('label' => 'button.setting.cancel'));
                break;
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => SettingEntity::class,
            'validation_groups' => array('setting_addition'),
            'attr' => array('id' => 'setting_form')
        ));
    }
}