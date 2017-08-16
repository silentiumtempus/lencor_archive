<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 001 01.03.17
 * Time: 15:21
 */

namespace AppBundle\Form;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\FactoryEntity;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArchiveEntryAddForm extends AbstractType
{
    protected $em;

    function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('year', ChoiceType::class, array(
                'choices' => $this->getYears(1990),
                'label' => 'label.year',
                'placeholder' => 'Выберите год'
            ))
            ->add('factory', EntityType::class, array(
                'class' => 'AppBundle:FactoryEntity',
                'label' => 'label.factory',
                'placeholder' => 'Выберите завод',
                'choice_value' => 'id'
            ));

        $settingLoader = function (FormInterface $builder, FactoryEntity $factory = null) {
            $settingsList = array();

            if ($factory) {
                $repository = $this->em->getRepository('AppBundle:SettingEntity');
                $settingsList = $repository->findByFactory($factory, array('id' => 'asc'));
            }

            $builder->add('setting', EntityType::class, array(
                'class' => 'AppBundle:SettingEntity',
                'choices' => $settingsList,
                'label' => 'label.setting',
                'placeholder' => 'Выберите установку'
            ));
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($settingLoader) {
                $data = $event->getData();
                $factory = $data->getFactory();
                $settingLoader($event->getForm(), $factory);
            }
        );

        $builder->get('factory')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($settingLoader) {
                $factory = $event->getForm()->getData();
                $settingLoader($event->getForm()->getParent(), $factory);
            }
        );

        $builder
            ->add('archiveNumber', TextType::class, array('label' => 'label.archive_number', 'attr' => array('size' => 30)))
            ->add('registerNumber', TextType::class, array('label' => 'label.register_number', 'attr' => array('size' => 30), 'required' => false))
            ->add('contractNumber', TextType::class, array('label' => 'label.contract_number', 'attr' => array('size' => 30)))
            ->add('fullConclusionName', TextType::class, array('label' => 'label.conclusion_fullname', 'attr' => array('size' => 30)))

            ->add('submitButton', SubmitType::class, array('label' => 'Добавить запись'));

    }

    private function getYears($min, $max = 'current')
    {
        $years = range($min, ($max === 'current' ? date('Y') : $max));

        return array_combine($years, $years);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ArchiveEntryEntity::class,
            'validation_groups' => array('entry_addition'),
            'attr' => array('id' => 'archive_entry_add_form')
        ));
    }
}