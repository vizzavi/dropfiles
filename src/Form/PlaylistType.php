<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Dropzone\Form\DropzoneType;

//        DropzoneMultipleType
//        DropzoneType
# TODO: playlistId в верхнем записать.
//                    'data-controller' => 'dropzone',
//                    'data-dropzone-max-filesize-value' => '10', // Максимальный размер файла в мегабайтах
//                    'data-dropzone-max-files-value' => '10', // Максимальное количество файлов
//                    'data-dropzone-accepted-files-value' => '.png,.jpg,.jpeg,.gif', // Разрешенные типы файлов
//                    'data-dropzone-url-value' => '/playlist/upload', // URL для загрузки файлов

class PlaylistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('files', FileType::class, [
                'label' => ' ',
                'multiple' => true,
//                'attr' => [
//                    'accept' => 'video/*',
//                ],
                'mapped' => false,
                'required' => true,
            ])
            ->add('storageDuration', ChoiceType::class, [
                'label' => ' ',
                'choices' => [
                    '6 МЕСЯЦЕВ' => '6 month',
                    '1 ГОД' => '1 year',
                    'БЕССРОЧНО' => 'forever',
                ],
                'expanded' => true,
                'required' => true,
            ])
            ->add('playlistId', HiddenType::class, [
                'data' => $options['playlistId'],
            ])
            ->add('Upload', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);

        $resolver->setRequired('playlistId');
    }
}
