<?php

namespace App\Controller\Admin;

use App\Entity\Playlist;
use App\Entity\Video;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        //        return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        //         $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        //         return $this->redirect(
        //             $adminUrlGenerator->setController(PlaylistCrudController::class)
        //                               ->generateUrl()
        //         );

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        return $this->render('admin/dashboard/index.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Dropfiles');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToDashboard('Dashboard', 'fa fa-home'),

            MenuItem::section('Video hosting'),
            MenuItem::linkToCrud('Плейлисты', 'fa fa-list', Playlist::class),
            MenuItem::linkToCrud('Видео', 'fa fa-video-camera', Video::class),

            MenuItem::linkToRoute('Процессинг видео', 'fas fa-hourglass-half', 'show_video_processing'),

        ];
    }

    #[Route('/admin/logout', name: 'admin_logout')]
    public function logout(): void
    {
        throw new RuntimeException('logout');
    }
}
