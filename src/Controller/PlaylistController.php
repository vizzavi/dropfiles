<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\Video;
use App\Enum\FileLifeTime;
use App\Enum\ProcessingStatus;
use App\Form\PlaylistType;
use App\Message\Video\VideoProcessingMessege;
use App\Repository\PlaylistRepository;
use App\Service\MercureService;
use App\Service\PlaylistService;
use App\ValueObject\FileSize;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PlaylistController extends AbstractController
{
    public function __construct(
        private readonly PlaylistService $playlistService,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlaylistRepository $playlistRepository,
        private readonly MercureService $mercureService,
        private readonly Filesystem $filesystem,
    ) {
    }

    #[Route('/', name: 'app_playlist')]
    public function index(Request $request, MessageBusInterface $bus): Response
    {
        $playlistId = $this->playlistService->generatePlaylistId($request->getSession());

        $form = $this->createForm(PlaylistType::class, null, [
            'playlistId' => $playlistId,
        ]);
        $form->handleRequest($request);

        # TODO: Будет другая загрузка через Ajax
        if ($form->isSubmitted() && $form->isValid()) {
            $storageDuration = $form->getData()['storageDuration'];
            $playlistId      = $form->getData()['playlistId'];
            $uploadedFiles   = $form->get('files')->getData();

            if ($uploadedFiles) {
                $destination = $this->getParameter('kernel.project_dir') . '/uploads/private/' . $playlistId;

                $deletionDate = FileLifeTime::from($storageDuration)->getDate();
                $playlistUuid = Uuid::fromString($playlistId);

                $playlist = $this->entityManager->getRepository(Playlist::class)->findOneBy(['uuid' => $playlistUuid]);

                if (!$playlist) {
                    $playlist = (new Playlist())
                        ->setUuid($playlistUuid)
                        ->setCreatedAt(new DateTimeImmutable())
                        ->setDeletionData($deletionDate)
                        ->setPageViewed(0)
                        ->setDeleteFlag(false)
                    ;
                    $this->entityManager->persist($playlist);
                    $this->entityManager->flush();
                }

                foreach ($uploadedFiles as $uploadedFile) {
                    $videoID  = Uuid::v4();
                    $fileName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $newFilename = $fileName . '.' . $uploadedFile->guessExtension();

                    try {
                        $uploadedFile->move($destination . '/' . $videoID . '/input', $newFilename);

                        $uploadedFileSize     = new FileSize($uploadedFile->getSize(), FileSize::UNIT_BYTE);
                        $uploadedFileSizeInKB = $uploadedFileSize->convertTo(FileSize::UNIT_KILOBYTE);

                        $video = (new Video())
                            ->setPlaylist($playlist)
                            ->setCreatedAt(new DateTimeImmutable())
                            ->setName($fileName)
                            ->setUuid($videoID)
                            ->setDeletionDate($deletionDate)
                            ->setSize($uploadedFileSizeInKB)
                            ->setImagePreview(null)
                            ->setPath($destination . '/' . $videoID)
                            ->setProcessingStatus(ProcessingStatus::queue->value)
                        ;

                        $this->entityManager->persist($video);
                        $this->entityManager->flush();

                        $videoMessage = new VideoProcessingMessege(
                            $videoID,
                            $playlist->getUuid(),
                            $destination . '/' . $videoID . '/input/' . $newFilename,
                            $destination . '/' . $videoID,
                            $fileName,
                            $storageDuration,
                        );
                        $bus->dispatch($videoMessage);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload file.');
                        dd('Error: ' . $e->getMessage());
                    } catch (TransportExceptionInterface $e) {
                    }
                }
            }

            return $this->redirectToRoute('app_upload_success');
        }

        return $this->render('playlist/index.html.twig', [
            'link_for_downloading' => 'https://files.davinci.pm/' . $playlistId,
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

        $isPlaylistOwner = $this->playlistService->isPlaylistOwner($request->getSession());

        $videos = $playlist->getVideos();

//        dd($playlist->getDeletionData());

//        echo 'count - '  . $videos->count();
//        dd($videos->getValues());

        // Show playlist

        $playlistTitle = $this->playlistService->generatePlaylistTitle($videos);

        $allVideosSize = $this->playlistService->getVideosSize($videos);

        return $this->render('playlist/playlist.html.twig', [
            'isPlaylistOwner' => $isPlaylistOwner,
            'playlist' => $playlist,
            'playlistTitle' => $playlistTitle,
            'allVideosSize' => $allVideosSize,
        ], $response);
    }

    /**
     * @throws Exception
     */
    #[Route('/api/playlist/{playlistId}', name: 'api_playlist_delete', methods: ['DELETE'])]
    public function delete(Request $request, string $playlistId): Response
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

    #[Route('/playlist/upload-success', name: 'app_upload_success', methods: ['GET'])]
    public function uploadSuccess(Request $request): Response
    {
        return new Response('Good', Response::HTTP_OK);
    }

    #[Route('/playlist/upload', name: 'app_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        # TODO: Потом как буду верстку подключать сделать ajax загрузку.

        $playlistId = $request->request->get('playlistId');

        $files = $request->files->get('files');


        //        Можно в трай кетч поместить и вообще с танзакцией
        //        Если выбирает ошибка на uniq в бд сделать ролбек или в кетч добавить дату сегодня в тайм стемх хз
        //        Что бы перед каждым сохданием не спраивать бд

        if (!$files) {
            return new JsonResponse(['error' => 'No files uploaded'], 400);
        }

        $playlistDirectory = $this->getParameter('uploads_directory') . '/' . $playlistId;

        $uploadedFiles = [];
        foreach ($files as $file) {
            //            if ($file->getSize() > 10 * 1024 * 1024) { // Проверка размера файла (10 МБ)
            //                return new JsonResponse(['error' => sprintf('File %s is too large.', $file->getClientOriginalName())], 400);
            //            }

            //            if (!in_array($file->getMimeType(), ['image/png', 'image/jpeg', 'image/gif'])) { // Проверка типа файла
            //                return new JsonResponse(['error' => sprintf('File %s has an invalid file type.', $file->getClientOriginalName())], 400);
            //            }

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename      = $playlistId . '-' . $originalFilename . '.' . $file->guessExtension();

            try {
                $file->move($playlistDirectory, // Путь для сохранения файлов
                    $newFilename);

                $uploadedFiles[] = $newFilename;
            } catch (FileException) {
                return new JsonResponse(['error' => sprintf('Failed to upload file %s.', $file->getClientOriginalName())], 500);
            }
        }

        return new JsonResponse(['files' => $uploadedFiles, 'uuid' => $playlistId]);
    }
}
