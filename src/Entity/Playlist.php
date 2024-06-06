<?php

namespace App\Entity;

use App\Repository\PlaylistRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PlaylistRepository::class)]
class Playlist
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, length: 180, unique: true)]
    private ?Uuid $uuid = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $deletionData = null;

    #[ORM\Column]
    private ?int $pageViewed = null;

    #[ORM\Column]
    private ?bool $deleteFlag = null;

    /**
     * @var Collection<int, Video>
     */
    #[ORM\OneToMany(targetEntity: Video::class, mappedBy: 'playlist')]
    #[ORM\JoinColumn(name:"uuid", referencedColumnName:"playlist_uuid")]
    private Collection $videos;

    public function __construct()
    {
        $this->videos = new ArrayCollection();
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(?Uuid $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDeletionData(): ?DateTimeImmutable
    {
        return $this->deletionData;
    }

    public function setDeletionData(?DateTimeImmutable $deletionData): static
    {
        $this->deletionData = $deletionData;

        return $this;
    }

    public function getPageViewed(): ?int
    {
        return $this->pageViewed;
    }

    public function setPageViewed(int $pageViewed): static
    {
        $this->pageViewed = $pageViewed;

        return $this;
    }

    public function isDeleteFlag(): ?bool
    {
        return $this->deleteFlag;
    }

    public function setDeleteFlag(bool $deleteFlag): static
    {
        $this->deleteFlag = $deleteFlag;

        return $this;
    }

    /**
     * @return Collection<int, Video>
     */
    public function getVideos(): Collection
    {
        return $this->videos;
    }

    public function addVideo(Video $video): static
    {
        if (!$this->videos->contains($video)) {
            $this->videos->add($video);
            $video->setPlaylist($this);
        }

        return $this;
    }

    public function removeVideo(Video $video): static
    {
        if ($this->videos->removeElement($video)) {
            // set the owning side to null (unless already changed)
            if ($video->getPlaylist() === $this) {
                $video->setPlaylist(null);
            }
        }

        return $this;
    }
}
