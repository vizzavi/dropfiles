<?php

namespace App\Controller\Admin;

use App\Entity\Video;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class VideoController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Video::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $deleteFlagField = BooleanField::new('DeleteFlag')
                                       ->setLabel('В корзине');

        if ($pageName === Crud::PAGE_INDEX) {
            $deleteFlagField->setDisabled();
        }

        $fields = [
            TextField::new('imagePreview')
                     ->setLabel('Превью')
                     ->setDisabled(),
            DateTimeField::new('createdAt')
                         ->setLabel('Дата создания видео')
                         ->setDisabled(),
            DateTimeField::new('DeletionData')
                         ->setLabel('Дата удаления'),
            NumberField::new('views')
                       ->setDisabled()
                       ->setLabel('Просмотров'),
            NumberField::new('downloads')
                       ->setDisabled()
                       ->setLabel('Скачано раз'),
            NumberField::new('size')
                       ->setDisabled()
                       ->setLabel('Размер'),
            $deleteFlagField,
        ];

//        if ($pageName === Crud::PAGE_DETAIL) {
//            $fields[] = CollectionField::new('videos')
//                                       ->setLabel('Список видео')
//                                       ->onlyOnDetail()
//                                       ->setTemplatePath('admin/playlist/videos_collection.html.twig')
//            ;
//        }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Crud::PAGE_DETAIL)
            ->disable(Action::NEW, Action::DELETE);
    }
}
