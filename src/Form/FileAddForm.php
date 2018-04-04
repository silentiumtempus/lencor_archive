<?php

namespace App\Form;

use App\Entity\FileEntity;
use Doctrine\ORM\EntityManagerInterface;
use Glifery\EntityHiddenTypeBundle\Form\Type\EntityHiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Yavin\Symfony\Form\Type\TreeType;

/**
 * Class FileAddForm
 * @package App\Form
 */
class FileAddForm extends AbstractType
{
    protected $em;
    protected $folderRepository;

    /**
     * FileAddForm constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->folderRepository = $this->em->getRepository('App:FolderEntity');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $parentFolder = $this->folderRepository->findOneById($options['attr']['folderId']);
        if ($options['attr']['isRoot']) {
            $builder
                ->add('parentFolder', TreeType::class, array(
                    'class' => 'App:FolderEntity',
                    'label' => 'folder.create.parent.label',
                    'placeholder' => 'folder.create.parent.placeholder',
                    'choice_value' => 'id',
                    'levelPrefix' => ' -',
                    'orderFields' => ['lft' => 'asc'],
                    'prefixAttributeName' => 'data-level-prefix',
                    'treeLevelField' => 'lvl',
                    'query_builder' => $this->folderRepository->getEntryFoldersQuery($this->folderRepository, $options['attr']['folderId'])
                ));
        } else {
            $builder
                ->add('parentFolder', EntityHiddenType::class, array(
                    'class' => 'App\Entity\FolderEntity',
                    'data' => $parentFolder,
                    'label' => $parentFolder->getFolderName()
                ));
        }
        $builder
            ->add('files', FileType::class, array(
                'label' => 'file.upload.name',
                'attr' => array('size' => 20),
                'multiple' => true
            ))
            ->add('submitButton', SubmitType::class, array('label' => 'file.upload.button'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => FileEntity::class,
            'cascade_validation' => true,
            'validation_groups' => array('file_upload'),
            'attr' => array('id' => 'file_add_form'),
            'translation_domain' => 'files_folders'
        ));
    }
}
