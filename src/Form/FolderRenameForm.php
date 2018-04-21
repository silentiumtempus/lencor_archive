<?php

namespace App\Form;

use App\Entity\FolderEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FolderRenameForm
 * @package App\Form
 */
class FolderRenameForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('folderName', TextType::class, array(
                'label' => 'file.rename.label',
                'attr' => array('size' => 20)
            ))
            ->add('submitButton', SubmitType::class, array('label' => 'file.rename.submit'))
            ->add('cancelButton', ResetType::class, array('label' => 'file.rename.cancel'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => FolderEntity::class,
            'validation_groups' => array('folder_rename'),
            'attr' => array('id' => 'folder_rename_form'),
            'translation_domain' => 'files_folders'
        ));
    }
}