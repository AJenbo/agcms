<?php

namespace App\Services;

use App\Exceptions\Exception;
use App\Exceptions\InvalidInput;
use App\Models\File;
use DateTime;
use getID3;
use Symfony\Component\HttpFoundation\File\File as FileHandeler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHandler
{
    /** A well compressed JPEG */
    private const MAX_BYTE_PER_PIXEL = 0.7;

    /** @var string Foler where the current upload will be saved. */
    private string $targetDir = '';
    /** @var string File name with out extension. */
    private string $baseName = '';
    /** @var string File extension. */
    private string $extension = '';
    private FileService $fileService;
    private FileHandeler $file;

    /**
     * Initialize the service.
     */
    public function __construct(string $targetDir)
    {
        $this->fileService = new FileService();
        $this->fileService->checkPermittedPath($targetDir);
        $this->targetDir = $targetDir;
    }

    /**
     * Set the target folder.
     */
    public function settargetDir(string $targetDir): void
    {
        $this->targetDir = $targetDir;
    }

    /**
     * Process a given file upload.
     *
     * @throws InvalidInput
     */
    public function process(
        UploadedFile $uploadedFile,
        string $destinationType,
        string $description
    ): File {
        if (!$uploadedFile->isValid()) {
            throw new InvalidInput(_('No file received.'));
        }

        $fileName = $uploadedFile->getClientOriginalName();
        if (!$fileName) {
            $fileName = (new DateTime())->format('Y-m-d-h-i-s');
        }
        $fileName = pathinfo($fileName, PATHINFO_FILENAME);
        $this->baseName = $this->fileService->cleanFileName($fileName);

        $fileExtension = $uploadedFile->getClientOriginalExtension();
        $this->extension = mb_strtolower($fileExtension);

        $this->file = $uploadedFile;

        return $this->processFile($destinationType, $description);
    }

    /**
     * Performe file operations.
     *
     * @throws Exception
     */
    private function processFile(string $destinationType, string $description): File
    {
        $filePath = $this->file->getRealPath();
        if (false === $filePath) {
            throw new Exception('Files doesn\'t exist');
        }

        $width = 0;
        $height = 0;
        if ($this->isImageFile()) {
            $image = new ImageService($filePath);
            $image->setAutoCrop(true);
            $image->setScale(ConfigService::getInt('text_width'));

            if ($this->shouldProcessImage($image, $image->getWidth(), $image->getHeight(), $destinationType)) {
                $this->processImage($image, $destinationType);
            }

            $width = $image->getWidth();
            $height = $image->getHeight();
        } elseif ($this->isVideoFile()) {
            $getID3 = new getID3();
            $fileInfo = $getID3->analyze($filePath);
            $width = $fileInfo['video']['resolution_x'];
            $height = $fileInfo['video']['resolution_y'];
        }

        return $this->insertFile($description, $width, $height);
    }

    /**
     * Check if file is a supported image type.
     */
    private function isImageFile(): bool
    {
        return in_array($this->file->getMimeType(), ['image/jpeg', 'image/gif', 'image/png'], true);
    }

    /**
     * Check if file is type of video.
     */
    private function isVideoFile(): bool
    {
        return 0 === mb_strpos($this->file->getMimeType() ?? '', 'video/');
    }

    /**
     * Should we process the image.
     */
    private function shouldProcessImage(ImageService $image, int $width, int $height, string $destinationType): bool
    {
        if ($destinationType && 'image' !== $destinationType && 'lineimage' !== $destinationType) {
            return false;
        }

        return 'lineimage' === $destinationType
            || 'image/jpeg' !== $this->file->getMimeType()
            || $this->file->getSize() / $width / $height > self::MAX_BYTE_PER_PIXEL
            || !$image->isNoOp();
    }

    /**
     * Crop and resize uploaded image.
     *
     * @throws Exception
     */
    public function processImage(ImageService $image, string $destinationType): void
    {
        $this->checkMemorry($image);

        $format = 'jpeg';
        $this->extension = 'jpg';
        if ('lineimage' === $destinationType) {
            $format = 'png';
            $this->extension = 'png';
        }

        $target = tempnam(sys_get_temp_dir(), 'upload');
        if (!$target) {
            throw new Exception(_('Failed to create temporary file'));
        }

        $image->processImage($target, $format);

        $this->file = new FileHandeler($target, false);
    }

    /**
     * Check if the image is expected to take more memory then we have avalible.
     *
     * @todo substract current usage
     * @todo reestimate limits
     *
     * @throws Exception
     * @throws InvalidInput If we don't have the needed memory avalibe
     */
    private function checkMemorry(ImageService $image): void
    {
        $memoryLimit = ini_get('memory_limit');
        if (!$memoryLimit) {
            throw new Exception(_('Invalid memory limit'));
        }

        $memoryLimit = $this->fileService->returnBytes($memoryLimit) - 270336;
        if ($image->getWidth() * $image->getHeight() > $memoryLimit / 10) {
            throw new InvalidInput(_('Image is too large to be processed.'));
        }
    }

    /**
     * Insert the file in the database and move it to the final location.
     */
    private function insertFile(string $description, int $width, int $height): File
    {
        $path = $this->targetDir . '/' . $this->getFilename();
        $this->fileService->checkPermittedTargetPath($path);

        $file = File::getByPath($path);
        if ($file) {
            $file->delete();
        }

        $this->file->move(app()->basePath($this->targetDir), $this->getFilename());

        return File::fromPath($path)
            ->setDescription($description)
            ->setWidth($width)
            ->setHeight($height)
            ->save();
    }

    /**
     * Get the full destination path.
     */
    private function getFilename(): string
    {
        if (!$this->extension) {
            return $this->baseName;
        }

        return $this->baseName . '.' . $this->extension;
    }
}
