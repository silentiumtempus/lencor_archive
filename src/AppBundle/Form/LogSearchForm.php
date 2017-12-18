<?php

namespace AppBundle\Form;

use AppBundle\Entity\ArchiveEntryEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class LogSearchForm
 * @package AppBundle\Form
 */
class LogSearchForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', NumberType::class, array(
                'label' => 'logs.search.placeholder',
                'attr' => array('size' => 10)
            ))
            ->add('submitButton', SubmitType::class, array('label' => 'logs.search.submit'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'attr' => array('id' => 'entry_logs_search_form')
        ));
    }
}