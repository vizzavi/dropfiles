<?php

namespace App\Controller;

use App\Form\PlaylistType;
use App\Message\Video\VideoProcessingMessege;
use App\Service\PlaylistService;
use App\Service\VideoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class PlaylistController extends AbstractController
{
    public function __construct(
        private readonly VideoService $videoService,
        private readonly PlaylistService $playlistService,
    ) {
    }

    #[Route('/', name: 'app_playlist')]
    public function index(Request $request, MessageBusInterface $bus): Response
    {
        $playlistId = $this->playlistService->generatePlaylistId($request->getSession());

        echo "<h1>Playlist: $playlistId</h1>";

        $form = $this->createForm(PlaylistType::class, null, [
            'playlistId' => $playlistId,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $storageDuration = $form->getData()['storageDuration'];
            $playlistId = $form->getData()['playlistId'];
            $uploadedFiles = $form->get('files')->getData();

            if ($uploadedFiles) {
                $destination = $this->getParameter('kernel.project_dir') . '/uploads/private/' . $playlistId;

                # TODO: Тут создать сущность плейлиста

                foreach ($uploadedFiles as $uploadedFile) {
                    $videoID = Uuid::v4();
                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $newFilename = $originalFilename . '.' .  $uploadedFile->guessExtension();

                    try {
                        $uploadedFile->move($destination . '/' . $videoID . '/input', $newFilename);
                        # TODO: Тут создать сущность видео
                        $this->addFlash('success', 'File successfully uploaded!');


                        $videoMessage = new VideoProcessingMessege(
                            $videoID,
                            $playlistId,
                            $destination . '/' . $videoID . '/input/' . $newFilename,
                            $destination . '/' . $videoID,
                            $newFilename,
                            $storageDuration
                        );

                        $bus->dispatch($videoMessage);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload file.');
                        dd('Error: ' . $e->getMessage());
                    }
                }
            }

            return $this->redirectToRoute('app_upload_success');
        }

        return $this->render('playlist/index.html.twig', [
            'controller_name' => 'PlaylistController',
            'form' => $form->createView(),
        ]);
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
            $newFilename = $playlistId . '-' . $originalFilename . '.' . $file->guessExtension();

            try {
                $file->move(
                    $playlistDirectory, // Путь для сохранения файлов
                    $newFilename
                );

                $uploadedFiles[] = $newFilename;
            } catch (FileException) {
                return new JsonResponse(['error' => sprintf('Failed to upload file %s.', $file->getClientOriginalName())], 500);
            }
        }

        return new JsonResponse(['files' => $uploadedFiles, 'uuid' => $playlistId]);
    }
}
