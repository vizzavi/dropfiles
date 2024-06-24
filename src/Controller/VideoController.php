<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Video;
use App\Repository\VideoRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/api/video/{videoId}/watch', name: 'private_video_watch', methods: ['GET'])]
    public function video(string $videoId): Response
    {
        $video = $this->videoRepository->find($videoId);

        try {
            if (!$video) {
                throw $this->createNotFoundException('Video not found');
            }

            $fullPath = $video->getPath();

            if (!file_exists($fullPath)) {
                throw $this->createNotFoundException('File not found');
            }
        } catch (NotFoundHttpException $e) {
            return new Response($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->file($fullPath);
    }

    #[Route('/api/video/{videoId}/update-views', name: 'video_update_views', methods: ['POST'])]
    public function updateViews(
        #[MapEntity(expr: 'repository.find(videoId)')]
        Video $video,
        Request $request,
    ): Response {
        $sessionId = 'video_viewed_' . $video->getUuid()->toRfc4122();
        $session = $request->getSession();

        # Получить кол. и вернуть знач.

        if ($session->has($sessionId)) {
            $value = $session->get($sessionId);
            $responseData = ['message' => 'Session exists', 'value' => $value];
            return new JsonResponse($responseData);
        }

        $this->videoRepository->updateViews($video);

        $newValue = '1';
        $session->set($sessionId, $newValue);

        $responseData = ['message' => 'New session set', 'value' => $newValue];
        return new JsonResponse($responseData);
    }

}
