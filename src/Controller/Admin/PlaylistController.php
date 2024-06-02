<?php

namespace App\Controller\Admin;

use App\Entity\Playlist;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

class PlaylistController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Playlist::class;
    }

//    #[Route('/admin/playlist/{id}', name: 'admin_playlist_show', methods: ['GET'])]
//    public function show(int $id): Response
//    {
//        return $this->render('admin/dashboard/index.html.twig');
//    }

    public function configureFields(string $pageName): iterable
    {
        $deleteFlagField = BooleanField::new('DeleteFlag')
                                       ->setLabel('В корзине');

        if ($pageName === Crud::PAGE_INDEX) {
            $deleteFlagField->setDisabled();
        }

        $fields = [
            TextField::new('uuid')
                ->setLabel('Токен для ссылки')
                ->setDisabled(),
            DateTimeField::new('createdAt')
                ->setLabel('Дата создания списка')
                ->setDisabled(),
            DateTimeField::new('DeletionData')
                ->setLabel('Дата удаления'),
            NumberField::new('PageViewed')
                ->setDisabled()
                ->setLabel('Просмотров страницы'),
            $deleteFlagField,
            AssociationField::new('videos', 'Кол. видео')
                ->hideOnForm(),
        ];

        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = CollectionField::new('videos')
                ->setLabel('Список видео')
                ->onlyOnDetail()
                ->setTemplatePath('admin/playlist/videos_collection.html.twig')
            ;
        }

        return $fields;
    }


    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Crud::PAGE_DETAIL)
            ->disable(Action::NEW, Action::DELETE);
    }
}
