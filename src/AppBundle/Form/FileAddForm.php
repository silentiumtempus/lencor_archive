<?php

namespace AppBundle\Form;

use AppBundle\Entity\FileEntity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Yavin\Symfony\Form\Type\TreeType;

class FileAddForm extends AbstractType
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
                'placeholder' => 'Выберите директорию',
                'choice_value' => 'id',
                'levelPrefix' => ' -',
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
            ->add ('fileName', FileType::class, array(
                'label' => 'file.fileName',
                'attr' => array('size' => 20)
            ))
            ->add('submitButton', SubmitType::class, array('label' => 'file.add'));
    }
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