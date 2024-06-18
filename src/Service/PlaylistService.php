<?php

namespace App\Service;

use App\Entity\Video;
use App\ValueObject\FileSize;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Uid\Uuid;

class PlaylistService
{
    private const int ONE_VIDEO = 1;

    public function generatePlaylistId(SessionInterface $session): string
    {
        if ($session->has('playlistId')) {
            return $session->get('playlistId');
        }

        $playlistId = Uuid::v4()->toRfc4122();
        $session->set('playlistId', $playlistId);

        return $playlistId;
    }

    public function isPlaylistOwner(SessionInterface $session): bool
    {
        if ($session->has('playlistId')) {
            return $session->get('playlistId');
        }

        return false;
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
}