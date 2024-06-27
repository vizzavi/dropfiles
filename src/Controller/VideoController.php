<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Video;
use App\Repository\VideoRepository;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        $cookieName = 'video_viewed_' . $video->getUuid()->toRfc4122();
        $cookieValue = $request->cookies->get($cookieName);

        if ($cookieValue) {
            $responseData = ['message' => 'Cookie exists', 'value' => $cookieValue];
            return new JsonResponse($responseData);
        }

        $this->videoRepository->incrementViews($video);

        $cookie = new Cookie(
            'video_viewed_', $video->getUuid()->toRfc4122(),
            new DateTimeImmutable('+1 hour')
        );

        $responseData = ['message' => 'New session set', 'value' => $cookie->getValue()];
        $response = new JsonResponse($responseData);
        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/video/{videoId}/popup', name: 'popup_private_video', methods: ['GET'])]
    public function popupPrivateVideoStream(string $videoId)
    {
        $video = $this->videoRepository->findOneBy(['uuid' => $videoId]);

        if (!$video) {
            throw $this->createNotFoundException('Video not found');
        }

        $filePath = $video->getPath();

        if (! file_exists($filePath)) {
            throw $this->createNotFoundException('The file does not exist');
        }

        $mimeType = mime_content_type($filePath);

        $response = new StreamedResponse(function () use ($filePath) {
            $fileStream = fopen($filePath, 'rb');
            fpassthru($fileStream);
            fclose($fileStream);
        });

        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', 'inline; filename="'.basename($filePath).'"');

        return $response;
    }
}
