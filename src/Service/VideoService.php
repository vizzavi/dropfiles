<?php

namespace App\Service;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Video;
use Imagick;
use ImagickException;
use Symfony\Component\Process\Exception\ProcessFailedException;

readonly class VideoService
{
    private const int VIDEO_WIDTH                = 1280;
    private const int POSTER_WIDTH               = 1280;
    private const int POSTER_HEIGHT              = 720;
    private const int POSTER_COMPRESSION_QUALITY = 85;


    public function __construct(private FFMpeg $ffmpeg)
    {
    }

    public function processVideo(string $inputPath, string $outputPath): void
    {
        $video = $this->ffmpeg->open($inputPath);

        $this->createVideoPoster($video, $outputPath);
        $this->convertVideo($video, $outputPath);
    }

    public function convertVideo(Video $video, string $outputPath): void
    {
        // Настройка формата для сжатия видео
        $format = new X264();
        $format->setKiloBitrate(800); // Установка битрейта
        $format->setAudioCodec('aac'); // Установка аудио кодека
        $format->setAdditionalParameters([
            '-vf', 'scale=1280:-2',      // Изменение разрешения до 720p
            '-r', '30',                  // Фиксированный фреймрейт 30 fps
            '-preset', 'fast',           // Пресет сжатия
            '-movflags', '+faststart',   // Оптимизация для прогрессивной загрузки
//            '-crf', '29',              // Контрольный параметр качества (снизить значение для лучшего качества, повысить для меньшего размера)
//            '-maxrate', '800k',        // Максимальный битрейт
//            '-bufsize', '1600k',       // Размер буфера
//            '-pix_fmt', 'yuv420p',     // Формат пикселей
        ]);

        $video->save($format, $outputPath . '/video.mp4');
    }

    public function createVideoPoster(Video $video, string $outputPath): void
    {
        $posterPath = $outputPath . '/poster.jpg';

        $frame = $video->frame(TimeCode::fromSeconds(1));
        $frame->save($posterPath);

        try {
            $this->compressImage($posterPath);
        } catch (ProcessFailedException|ImagickException $e) {}
    }

    /**
     * @throws ImagickException
     */
    private function compressImage(string $filePath): void
    {
        $imagick = new Imagick($filePath);
        $imagick->resizeImage(
            self::POSTER_WIDTH,
            self::POSTER_HEIGHT,
            \Imagick::FILTER_LANCZOS,
            1,
            true
        );
        $imagick->setImageCompressionQuality(self::POSTER_COMPRESSION_QUALITY);
        $imagick->writeImage($filePath);
        $imagick->clear();
        $imagick->destroy();
    }
}