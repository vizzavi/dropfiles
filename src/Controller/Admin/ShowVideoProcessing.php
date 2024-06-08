<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowVideoProcessing extends AbstractController
{
    #[Route("/admin/show-video-processing", name:"show_video_processing")]
    public function index(): Response
    {
        $videos = [
            [
                'video' => '4422f72f-484a-4285-a9bb-c51b64fa7074',
                'processing' => 'В обработке',
                'name' => 'тест.mp4',
                'playlist' => '9a3f0780-d86b-4ef2-89f0-a202175c2e35'
            ],
            [
                'video' => 'fa222ffa-2e0c-4843-85bc-7c4f6fae743d',
                'processing' => 'В обработке',
                'name' => 'тест2.mp4',
                'playlist' => '9a3f0780-d86b-4ef2-89f0-a202175c2e35'
            ],
        ];

        return $this->render('admin/processing/show_video_processing.html.twig', [
            'page_title' => 'Процессинг видео',
            'videos' => $videos,
        ]);
    }
}