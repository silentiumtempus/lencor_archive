<?php

namespace AppBundle\Form;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\FactoryEntity;
use AppBundle\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
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
    protected $settingService;

    /**
     * ArchiveEntryAddForm constructor.
     * @param EntityManagerInterface $em
     * @param SettingService $settingService
     */
    function __construct(EntityManagerInterface $em, SettingService $settingService)
    {
        $this->em = $em;
        $this->settingService = $settingService;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
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
                $settingsList = $this->settingService->findSettingsByFactory($factory);
                $status = false;
            } else {
                $status = true;
            }

            $builder->add('setting', EntityType::class, array(
                'class' => 'AppBundle:SettingEntity',
                'choices' => $settingsList,
                'label' => 'label.setting',
                'placeholder' => 'Выберите установку',
                'disabled' => $status
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

    /**
     * @param $min
     * @param string $max
     * @return array
     */
    private function getYears($min, $max = 'current')
    {
        $years = range(($max === 'current' ? date('Y') : $max), $min);

        return array_combine($years, $years);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ArchiveEntryEntity::class,
            'validation_groups' => array('entry_addition'),
            'attr' => array('id' => 'archive_entry_add_form')
        ));
    }
}