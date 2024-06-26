<?php

namespace App\Controller;

use App\Entity\Video;
use App\Form\PlaylistType;
use App\Repository\PlaylistRepository;
use App\Repository\VideoRepository;
use App\Service\PlaylistService;
use App\ValueObject\FileSize;
use DateTimeImmutable;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use ZipArchive;

class PlaylistController extends AbstractController
{
    public function __construct(
        private readonly PlaylistService $playlistService,
        private readonly PlaylistRepository $playlistRepository,
        private readonly VideoRepository $videoRepository,
        private readonly Filesystem $filesystem,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/', name: 'app_playlist')]
    public function index(Request $request): Response
    {
        $playlistId = $request->cookies->get('playlistId') ?: $this->playlistService->generatePlaylistId();

        $form = $this->createForm(PlaylistType::class, null, [
            'playlistId' => $playlistId,
        ]);
        $form->handleRequest($request);

        # TODO: Будет другая загрузка через Ajax
        if ($form->isSubmitted() && $form->isValid()) {
            $destination = $this->getParameter('kernel.project_dir') . '/uploads/private/' . $playlistId;

            $storageDuration = $form->getData()['storageDuration'];
            $playlistId      = $form->getData()['playlistId'] ?? $playlistId;
            $uploadedFiles   = $form->get('files')->getData();

            $result = $this->playlistService->handleFileUpload(
                $playlistId,
                $storageDuration,
                $uploadedFiles,
                $destination
            );

            if ($result['status'] === 'error') {
                return new JsonResponse(['message' => $result['message']], 400);
            }

            $cookie = new Cookie(
                'playlistId',
                $playlistId,
                new DateTimeImmutable('+1 hour')
            );

//                $response = new JsonResponse($responseData);
            $response = $this->redirectToRoute('app_upload_success');
            $response->headers->setCookie($cookie);

            return $response;
//            return $this->redirectToRoute('app_upload_success');
        }

        return $this->render('playlist/index.html.twig', [
            'link_for_downloading' => $playlistId,
            'playlistId' => $playlistId,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{playlistId}', name: 'app_show_playlist', methods: ['GET'])]
    public function show(Request $request, string $playlistId): Response
    {
        $playlist = $this->playlistRepository->findOneBy(['uuid' => $playlistId]);

        if (! $playlist) {
            throw $this->createNotFoundException('The playlist does not exist');
        }

        $response = new Response();
        if (! $request->cookies->get('playlist_visited')) {
            $cookie = new Cookie('playlist_visited', true, new DateTimeImmutable('+1 hour'));
            $response->headers->setCookie($cookie);
            $this->playlistRepository->updatePageViewed($playlist);
        }

        $isPlaylistOwner = $this->playlistService->isPlaylistOwner($request->cookies->get('playlistId'), $playlistId);

        $videos = $playlist->getVideos();
        $playlistTitle = $this->playlistService->generatePlaylistTitle($videos);
        $allVideosSize = $this->playlistService->getVideosSize($videos);

        return $this->render('playlist/playlist.html.twig', [
            'isPlaylistOwner' => $isPlaylistOwner,
            'playlist' => $playlist,
            'playlistTitle' => $playlistTitle,
            'allVideosSize' => $allVideosSize,
            'videos' => $videos,
            'playlistId' => $playlistId,
        ], $response);
    }

    #[Route('/w/{playlistId}/{videoId?}', name: 'app_watch_playlist', methods: ['GET'])]
    public function watch(Request $request, string $playlistId, ?string $videoId): Response
    {
        $playlist = $this->playlistRepository->findOneBy(['uuid' => $playlistId]);

        $isPlaylistOwner = $this->playlistService->isPlaylistOwner($request->cookies->get('playlistId'), $playlistId);

        $videos = $playlist?->getVideos();

        $videosPrepare = [];

        foreach ($videos as $video) {
            $size = new FileSize($video->getSize() ?? 0, FileSize::UNIT_KILOBYTE);
            $videoSize = $size->convertTo(FileSize::UNIT_MEGABYTE) . ' ' .FileSize::UNIT_MEGABYTE;

            $posterUrl = $this->urlGenerator->generate('private_video_poster', [
                'videoId' => $video->getUuid()->toRfc4122()
            ]);

            $videoUrl = $this->urlGenerator->generate('private_video_watch', [
                'videoId' => $video->getUuid()->toRfc4122()
            ]);

            $videosPrepare[] = [
                'uuid' => $video->getUuid()->toRfc4122(),
                'deletionDate' => $video->getDeletionDate()->format('Y-m-d H:i:s'),
                'views' => $video->getViews() ?? 0,
                'downloads' => $video->getDownloads() ?? 0,
                'size' => $videoSize,
                'name' => $video->getName(),
                'posterUrl' => $posterUrl,
                'videoUrl' => $videoUrl,
                'linkForDownloading' => 'w/' . $playlist->getUuid()->toRfc4122() . '/' . $video->getUuid()->toRfc4122(),
            ];
        }

        return $this->render('playlist/watch.html.twig', [
            'playlist' => $playlist,
            'isPlaylistOwner' => $isPlaylistOwner,
            'activeVideoId' => $videoId,
            'videos' => $videosPrepare,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/api/playlist/{playlistId}', name: 'api_playlist_delete', methods: ['DELETE'])]
    public function delete(string $playlistId): Response
    {
        $playlist = $this->playlistRepository->findOneBy(['uuid' => $playlistId]);

        if (! $playlist) {
            return new JsonResponse(['status' => 'Плейлист не найден'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->playlistRepository->deletePlaylistWithVideos($playlist);

            # TODO: вынести в константу
            $folderPath = $this->getParameter('kernel.project_dir') . '/uploads/private/' . $playlist->getUuid();

            if ($this->filesystem->exists($folderPath)) {
                $this->filesystem->remove($folderPath);
            }

            //        $this->addFlash('success', 'Playlist has been deleted.');
        } catch (Exception) {
             return new JsonResponse(['status' => 'Ошибка удаления плейлиста'], 400);
        }

        return new JsonResponse(['status' => 'Плейлист удален'], 200);
    }

    #[Route('/api/playlist/{playlistId}/download/{videoId?}', name: 'api_playlist_download_videos', methods: ['GET'])]
    public function downloadPlaylist(string $playlistId, ?string $videoId = null): Response
    {
        if ($videoId !== null) {
            /** @var Video $video */
            $video = $this->videoRepository->findOneBy(['uuid' => $videoId]);
            $filePath = $video->getPath();

            if (! file_exists($filePath)) {
                throw $this->createNotFoundException('The file does not exist');
            }

            $this->videoRepository->incrementDownloads($video);

            $response = $this->file($filePath);
            $response->headers->set('Content-Type', 'video/mp4');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            return $response;
        }

        $playlist = $this->playlistRepository->findOneBy(['uuid' => $playlistId]);
        $countVideos = $playlist?->getVideos()->count();

        if ($countVideos === null && $countVideos > 0) {
            throw $this->createNotFoundException('The file does not exist');
        }

        $downloadFileName = 'playlist_' . $playlist->getUuid()?->toRfc4122() . '.zip';
        $zipFileName = tempnam(sys_get_temp_dir(), $downloadFileName);

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not open zip file');
            }

            foreach ($playlist->getVideos() as $video) {
                $filePath = $video->getPath();

                if (!$this->filesystem->exists($filePath)) {
                    throw $this->createNotFoundException('Video file not found: ' . $video->getName());
                }

                $zip->addFile($filePath, $video->getName() . '.mp4');
                $this->videoRepository->incrementDownloads($video);
            }

            $zip->close();

            $response = new StreamedResponse(function () use ($zipFileName) {
                readfile($zipFileName);
                $this->filesystem->remove($zipFileName);
            });

            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $downloadFileName);
            $response->headers->set('Content-Length', filesize($zipFileName));
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            return $response;
        } catch (IOExceptionInterface $exception) {
            throw new RuntimeException('An error occurred while creating or removing the zip file: ' . $exception->getMessage());
        }
    }

    /**
     * This method need only for testing in development process
     */
    #[Route('/playlist/upload-success', name: 'app_upload_success', methods: ['GET'])]
    public function uploadSuccess(Request $request): Response
    {
        return new Response('Good', Response::HTTP_OK);
    }

    /**
     * Для загрузки файлов будет использоваться этот endpoint
     */
    #[Route('/api/playlist/upload', name: 'app_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $playlistId = $request->request->get('playlistId');
        $storageDuration = $request->request->get('storageDuration');
        $uploadedFiles = $request->files->get('files');

        $destination = $this->getParameter('kernel.project_dir') . '/uploads/private/' . $playlistId;
        $result = $this->playlistService->handleFileUpload($playlistId, $storageDuration, $uploadedFiles, $destination);

        if ($result['status'] === 'error') {
            return new JsonResponse(['message' => $result['message']], 400);
        }

        return new JsonResponse(['message' => 'Upload successful', 'playlistId' => $playlistId]);
    }
}
