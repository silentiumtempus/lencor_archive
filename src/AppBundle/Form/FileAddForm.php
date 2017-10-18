<?php

namespace AppBundle\Form;

use AppBundle\Entity\FileEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Yavin\Symfony\Form\Type\TreeType;

class FileAddForm extends AbstractType
{
    protected $em;
    protected $folderRepository;

    /**
     * FileAddForm constructor.
     * @param EntityManagerInterface $em
     */
    function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->folderRepository = $this->em->getRepository('AppBundle:FolderEntity');
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('parentFolder', TreeType::class, array(
                'class' => 'AppBundle:FolderEntity',
                'label' => 'folder.parent',
                'placeholder' => 'Выберите директорию',
                'choice_value' => 'id',
                'levelPrefix' => ' -',
                'orderFields' => ['lft' => 'asc'],
                'prefixAttributeName' => 'data-level-prefix',
                'treeLevelField' => 'lvl',
                'query_builder' => $this->folderRepository->getEntryFoldersQuery($this->folderRepository, $options['attr']['folderId'])
            ))
            ->add ('files', FileType::class, array(
                'label' => 'file.fileName',
                'attr' => array('size' => 20),
                'multiple' => true
            ))
            ->add('submitButton', SubmitType::class, array('label' => 'file.add'));
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
            'attr' => array('id' => 'file_add_form')
        ));
    }
}