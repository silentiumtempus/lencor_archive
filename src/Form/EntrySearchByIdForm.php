<?php
declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class EntrySearchByIdForm
 * @package App\Form
 */
class EntrySearchByIdForm extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', NumberType::class, array(
                'label' => 'entries.id-search.placeholder',
                'attr' => array('size' => 10),
                'constraints' => array(
                    new NotBlank()
                    // TODO: additional validation required
                    //new Length(array('min' => 2)),
                    //new Type(array('type' => 'int'))

                )))
            ->add('submitButton', SubmitType::class, array('label' => 'entries.id-search.submit'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'attr' => array('id' => 'entry_search_by_id_form'),
            'translation_domain' => 'entries'
        ));
    }
}
