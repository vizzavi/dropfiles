<?php

namespace App\Form;

use App\Enum\FileLifeTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                    '6 МЕСЯЦЕВ' => FileLifeTime::sixMonth->value,
                    '1 ГОД'     => FileLifeTime::oneYear->value,
                    'БЕССРОЧНО' => FileLifeTime::forever->value,
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
