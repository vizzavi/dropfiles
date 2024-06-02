<?php

namespace App\DataFixtures;

use App\Entity\Playlist;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class PlaylistFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $playlist = new Playlist();
        $playlist->setUuid(Uuid::v4());
        $playlist->setDeleteFlag(false);
        $playlist->setDeletionData(null);
        $playlist->setPageViewed(0);
        $playlist->setCreatedAt(new DateTimeImmutable());

        $manager->persist($playlist);

        $manager->flush();
    }
}