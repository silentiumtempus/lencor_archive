<?php

namespace AppBundle\Form;

use AppBundle\Entity\FolderEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Yavin\Symfony\Form\Type\TreeType;

class FolderAddForm extends AbstractType
{
    protected $em;
    protected $folderRepository;

    /**
     * FolderAddForm constructor.
     * @param EntityManagerInterface $em
     *
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
                'placeholder' => 'Выберите родителя',
                'choice_value' => 'id',
                'levelPrefix' => '--',
                'orderFields' => ['lft' => 'asc'],
                'prefixAttributeName' => 'data-level-prefix',
                'treeLevelField' => 'lvl',
                'query_builder' => $this->folderRepository->getEntryFoldersQuery($this->folderRepository, $options['attr']['folderId'])
            ))

            ->add ('folderName', TextType::class, array(
                'label' => 'folder.name',
                'attr' => array('size' => 20)
            ))
            ->add('submitButton', SubmitType::class, array('label' => 'folder.add'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => FolderEntity::class,
            'cascade_validation' => true,
            'validation_groups' => array('folder_creation'),
            'attr' => array('id' => 'folder_add_form')
        ));
    }
}