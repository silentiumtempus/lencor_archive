<?php
declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class LoginForm
 * @package App\Form
 */
class AuthForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('_username', TextType::class, array(
                'label' => 'auth.username.label',
                'attr' => array(
                    'name' => '_username',
                    'size' => 30)
            ))
            ->add('_password', PasswordType::class, array(
                'label' => 'auth.password.label',
                'attr' => array(
                    'name' => '_password',
                    'size' => 30)
            ))
            ->add('submitButton', SubmitType::class, array('label' => 'auth.submit.label'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'attr' => array('id' => 'auth_form'),
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
            'translation_domain' => 'auth'
        ));
    }

    /**
     * @return null|string
     */
    public function getBlockPrefix()
    {
        return null;
    }
}
