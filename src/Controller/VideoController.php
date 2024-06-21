<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\VideoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class VideoController extends AbstractController
{
    public function __construct(private readonly VideoRepository $videoRepository)
    {
    }

    #[Route('/api/video/{videoId}/poster', name: 'private_video_poster', methods: ['GET'])]
    public function poster(string $videoId): Response
    {
        $video = $this->videoRepository->find($videoId);

        try {
            if (!$video) {
                throw $this->createNotFoundException('Video not found');
            }

            $fullPath = $video->getImagePreview();

            if (!file_exists($fullPath)) {
                throw $this->createNotFoundException('File not found');
            }
        } catch (NotFoundHttpException $e) {
            return new Response($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->file($fullPath);
    }

//$fullPath = $this->getParameter('kernel.project_dir' . 'assets/img/load.png');

//    #[Route('/api/video/{path}')]
//    public function video(): Response
//    {
//        //
//    }
}
