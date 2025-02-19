<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
class Video
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, length: 180, unique: true)]
    private ?Uuid $uuid = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $deletionDate = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ["default" => 0])]
    private ?int $views = null;

    #[ORM\Column(options:["comment"=>"Размер файла в килобайтах"]) ]
    private ?int $size = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ["default" => 0])]
    private ?int $downloads = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePreview = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $path = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => false])]
    private ?bool $deleteFlag = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $processingStatus = null;

    #[ORM\ManyToOne(inversedBy: 'videos')]
    #[ORM\JoinColumn(name: "playlist_uuid", referencedColumnName: "uuid", nullable: false)]
    private ?Playlist $playlist = null;

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(?Uuid $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getDeletionDate(): ?DateTimeImmutable
    {
        return $this->deletionDate;
    }

    public function setDeletionDate(?DateTimeImmutable $deletionDate): static
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(int $views): static
    {
        $this->views = $views;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $sizeInKiloBytes): self
    {
        $this->size = $sizeInKiloBytes;

        return $this;
    }

    public function getDownloads(): ?int
    {
        return $this->downloads;
    }

    public function setDownloads(int $downloads): static
    {
        $this->downloads = $downloads;

        return $this;
    }

    public function getImagePreview(): ?string
    {
        return $this->imagePreview;
    }

    public function setImagePreview(?string $imagePreview): static
    {
        $this->imagePreview = $imagePreview;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

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

    public function isDeleteFlag(): ?bool
    {
        return $this->deleteFlag;
    }

    public function setDeleteFlag(bool $deleteFlag): static
    {
        $this->deleteFlag = $deleteFlag;

        return $this;
    }

    public function getPlaylist(): ?Playlist
    {
        return $this->playlist;
    }

    public function setPlaylist(?Playlist $playlist): static
    {
        $this->playlist = $playlist;

        return $this;
    }

    public function getProcessingStatus(): ?string
    {
        return $this->processingStatus;
    }

    public function setProcessingStatus(?string $processingStatus): static
    {
        $this->processingStatus = $processingStatus;

        return $this;
    }

    // Методы для совместимости с Symfony Workflow
    public function getMarking(): ?string
    {
        return $this->getProcessingStatus();
    }

    public function setMarking(?string $marking): self
    {
        return $this->setProcessingStatus($marking);
    }
}
