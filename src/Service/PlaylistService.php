<?php

namespace App\Service;

use App\Entity\Playlist;
use App\Entity\Video;
use App\Enum\FileLifeTime;
use App\Message\Video\VideoProcessingMessege;
use App\ValueObject\FileSize;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class PlaylistService
{
    private const int ONE_VIDEO = 1;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $bus
    ) {
    }

    public function generatePlaylistId(): string
    {
        return Uuid::v4()->toRfc4122();
    }

    public function isPlaylistOwner(mixed $playlistInStore, string $playlistId): bool
    {
        if (! $playlistInStore) {
            return false;
        }

        if ($playlistInStore !== $playlistId) {
            return false;
        }

        return true;
    }

    public function generatePlaylistTitle(Collection $videos): string
    {
        $countVideos = $videos->count();

        /** @var Video $firstVideo */
        $firstVideo = $videos->first();
        $videoName = $firstVideo->getName();

        if ($countVideos === self::ONE_VIDEO) {
            return $videoName;
        }

        $countOtherVideos = $countVideos - self::ONE_VIDEO;

        return $videoName . " и ещё $countOtherVideos видео";
    }

    public function getVideosSize(Collection $videos): float|int
    {
        $totalSizeInKb = $videos->map(function($video) {
            return $video->getSize();
        })->reduce(function($totalSize, $size) {
            return $totalSize + $size;
        }, 0);

        return (new FileSize($totalSizeInKb, FileSize::UNIT_KILOBYTE))
            ->convertTo(FileSize::UNIT_MEGABYTE);
    }

    public function handleFileUpload(string $playlistId, string $storageDuration, array $uploadedFiles, string $playlistDirectory)
    {
        $deletionDate = FileLifeTime::from($storageDuration)->getDate();
        $playlistUuid = Uuid::fromString($playlistId);

        $playlist = $this->entityManager->getRepository(Playlist::class)->findOneBy(['uuid' => $playlistUuid]);

        if (!$playlist) {
            $playlist = (new Playlist())
                ->setUuid($playlistUuid)
                ->setCreatedAt(new DateTimeImmutable())
                ->setDeletionData($deletionDate)
                ->setPageViewed(0)
                ->setDeleteFlag(false);
            $this->entityManager->persist($playlist);
            $this->entityManager->flush();
        }

        foreach ($uploadedFiles as $uploadedFile) {
            $videoID  = Uuid::v4();
            $fileName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = $fileName . '.' . $uploadedFile->guessExtension();

            try {
                $uploadedFile->move($playlistDirectory . '/' . $videoID . '/input', $newFilename);

                $uploadedFileSize = $uploadedFile->getSize() / 1024; // Convert to KB

                $video = (new Video())
                    ->setPlaylist($playlist)
                    ->setCreatedAt(new DateTimeImmutable())
                    ->setName($fileName)
                    ->setUuid($videoID)
                    ->setDeletionDate($deletionDate)
                    ->setSize($uploadedFileSize)
                    ->setImagePreview(null)
                    ->setPath($playlistDirectory . '/' . $videoID)
                    ->setProcessingStatus('queued');

                $this->entityManager->persist($video);
                $this->entityManager->flush();

                $videoMessage = new VideoProcessingMessege(
                    $videoID,
                    $playlist->getUuid(),
                    $playlistDirectory . '/' . $videoID . '/input/' . $newFilename,
                    $playlistDirectory . '/' . $videoID,
                    $fileName,
                    $storageDuration,
                );
                $this->bus->dispatch($videoMessage);
            } catch (FileException $e) {
                return ['status' => 'error', 'message' => 'Failed to upload file.'];
            }
        }

        return ['status' => 'success'];
    }
}