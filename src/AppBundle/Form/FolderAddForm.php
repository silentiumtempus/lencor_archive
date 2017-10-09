<?php

namespace AppBundle\Form;

use AppBundle\Entity\FolderEntity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Yavin\Symfony\Form\Type\TreeType;

class FolderAddForm extends AbstractType
{
    protected $em;

    function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //$folderId = '2';
        $folderId = $options['attr']['folderId'];
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
                'query_builder' => function(EntityRepository $repository) use ($folderId) {

                    $parentFolder = $repository->createQueryBuilder('parent')
                        ->where('parent.root = :folderId')->setParameter(':folderId', $folderId)
                        ->orderBy('parent.lft', 'ASC');
                    return $parentFolder;
                }
            ))

            ->add ('folderName', TextType::class, array(
                'label' => 'folder.name',
                'attr' => array('size' => 20)
            ))
            ->add('submitButton', SubmitType::class, array('label' => 'folder.add'));
    }
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