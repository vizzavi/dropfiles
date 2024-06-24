<?php

namespace App\Service;

use App\Entity\Video;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

readonly class MercureService
{
    public function __construct(private HubInterface $hub)
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function publishUpdate(Video $video): void
    {
        $update = new Update('http://localhost:8080/video/' . $video->getUuid(), json_encode([
                'status' => $video->getProcessingStatus(),
                'data'   => $video,
            ], JSON_THROW_ON_ERROR));

        $this->hub->publish($update);
    }
}