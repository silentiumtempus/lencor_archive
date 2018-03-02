<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class LogRowsCountForm
 * @package App\Form
 */
class LogRowsCountForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $file = $options['attr']['file'];
        $entryId = $options['attr']['entryId'];
        $builder
            ->add('rowsCount', NumberType::class, array(
                'label' => 'logs.rowsCount.placeholder',
                'attr' => array('size' => 10)
            ))
            ->add('file', HiddenType::class, array(
                'data' => $file
            ))
            ->add('entryId', HiddenType::class, array(
                'data' => $entryId
            ))
            ->add('submitButton', SubmitType::class, array('label' => 'logs.rowsCount.submit'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'attr' => array('id' => 'logs_rows_count_form')
        ));
    }
}