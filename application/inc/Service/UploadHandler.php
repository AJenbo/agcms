<?php namespace AGCMS\Service;

use AGCMS\Config;
use AGCMS\Entity\File;
use AJenbo\Image;
use Exception;
use getID3;
use Symfony\Component\HttpFoundation\File\File as FileHandeler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHandler
{
    /** A well compressed JPEG */
    const MAX_BYTE_PER_PIXEL = 0.7;

    /** @var string Foler where the current upload will be saved. */
    private $targetDir = '';
    /** @var string File name with out extension. */
    private $baseName = '';
    /** @var string File extension. */
    private $extension = '';
    /** @var FileService */
    private $fileService;
    /** @var FileHandeler */
    private $file;

    /**
     * Initialize the service.
     *
     * @param string $targetDir
     */
    public function __construct(string $targetDir)
    {
        $this->fileService = new FileService();
        $this->fileService->checkPermittedPath($targetDir);
        $this->targetDir = $targetDir;
    }

    /**
     * Set the target folder.
     *
     * @param string $targetDir
     *
     * @return void
     */
    public function settargetDir(string $targetDir): void
    {
        $this->targetDir = $targetDir;
    }

    /**
     * Process a given file upload.
     *
     * @param UploadedFile $uploadedFile
     * @param string       $destinationType
     * @param string       $description
     *
     * @return File
     */
    public function process(
        UploadedFile $uploadedFile,
        string $destinationType,
        string $description
    ): File {
        if (!$uploadedFile->isValid()) {
            throw new Exception(_('No file recived.'));
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
     * @param string $destinationType
     * @param string $description
     *
     * @return File
     */
    private function processFile(string $destinationType, string $description): File
    {
        $width = 0;
        $height = 0;
        if ($this->isImageFile()) {
            $image = new Image($this->file->getRealPath());

            $imageContent = $image->findContent(0);
            $maxW = min(Config::get('text_width'), $imageContent['width']);

            if ($this->shouldProcessImage($image, $maxW, $imageContent['height'], $destinationType)) {
                $this->processImage($image, $imageContent, $maxW, $destinationType);
            }

            $width = $image->getWidth();
            $height = $image->getHeight();
        } elseif ($this->isVideoFile()) {
            $getID3 = new getID3();
            $fileInfo = $getID3->analyze($this->file->getRealPath());
            $width = $fileInfo['video']['resolution_x'];
            $height = $fileInfo['video']['resolution_y'];
        }

        return $this->insertFile($description, $width, $height);
    }

    /**
     * Check if file is a supported image type.
     *
     * @return bool
     */
    private function isImageFile(): bool
    {
        return in_array($this->file->getMimeType(), ['image/jpeg', 'image/gif', 'image/png'], true);
    }

    /**
     * Check if file is type of video.
     *
     * @return bool
     */
    private function isVideoFile(): bool
    {
        return 0 === mb_strpos($this->file->getMimeType() ?? '', 'video/');
    }

    /**
     * Should we process the image.
     *
     * @param Image  $image
     * @param int    $width
     * @param int    $height
     * @param string $destinationType
     *
     * @return bool
     */
    private function shouldProcessImage(Image $image, int $width, int $height, string $destinationType): bool
    {
        if ($destinationType && 'image' !== $destinationType && 'lineimage' !== $destinationType) {
            return false;
        }

        if ('lineimage' === $destinationType
            || $image->getWidth() !== $width
            || $image->getHeight() !== $height
            || $this->file->getSize() / $width / $height > self::MAX_BYTE_PER_PIXEL
            || 'image/jpeg' !== $this->file->getMimeType()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Crop and resize uploaded image.
     *
     * @param Image  $image
     * @param array  $imageContent
     * @param int    $maxW
     * @param string $destinationType
     *
     * @return void
     */
    public function processImage(Image $image, array $imageContent, int $maxW, string $destinationType): void
    {
        $this->checkMemorry($image);

        $this->extension = 'jpg';
        if ('lineimage' === $destinationType) {
            $this->extension = 'png';
        }

        $image->crop($imageContent['x'], $imageContent['y'], $imageContent['width'], $imageContent['height']);
        $image->resize($maxW, $image->getHeight());

        $target = tempnam(sys_get_temp_dir(), 'upload');
        if (!$target) {
            throw new Exception('Failed to create temporary file');
        }

        $format = 'jpg' === $this->extension ? 'jpeg' : $this->extension;
        $image->save($target, $format);

        $this->file = new FileHandeler($target, false);
    }

    /**
     * Check if the image is expected to take more memory then we have avalible.
     *
     * @throws Exception If we don't have the needed memory avalibe
     */
    private function checkMemorry(Image $image): void
    {
        $memoryLimit = $this->fileService->returnBytes(ini_get('memory_limit')) - 270336; // Estimated overhead, TODO substract current usage
        if ($image->getWidth() * $image->getHeight() > $memoryLimit / 10) {
            throw new Exception(_('Image is to large to be processed.'));
        }
    }

    /**
     * Insert the file in the database and move it to the final location.
     *
     * @param string $description
     * @param int    $width
     * @param int    $height
     *
     * @return File
     */
    private function insertFile(string $description, int $width, int $height): File
    {
        $path = $this->targetDir . '/' . $this->getFilename();
        $this->fileService->checkPermittedTargetPath($path);

        $file = File::getByPath($path);
        if ($file) {
            $file->delete();
        }

        $this->file->move(_ROOT_ . $this->targetDir, $this->getFilename());

        return File::fromPath($path)
            ->setDescription($description)
            ->setWidth($width)
            ->setHeight($height)
            ->save();
    }

    /**
     * Get the full destination path.
     *
     * @return string
     */
    private function getFilename(): string
    {
        if (!$this->extension) {
            return $this->baseName;
        }

        return $this->baseName . '.' . $this->extension;
    }
}
