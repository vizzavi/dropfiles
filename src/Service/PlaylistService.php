<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Uid\Uuid;

class PlaylistService
{
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
}