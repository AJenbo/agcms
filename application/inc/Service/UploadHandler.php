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

    private $targetPath = '';
    private $baseName = '';
    private $extension = '';

    /** @var FileHandeler */
    private $file;

    public function __construct(string $targetPath)
    {
        $this->setTargetPath($targetPath);
    }

    public function setTargetPath(string $targetPath): void
    {
        $targetPath = (string) realpath(_ROOT_ . $targetPath); // Check path exists
        $targetPath = mb_substr($targetPath, mb_strlen(_ROOT_)); // Remove _ROOT_
        if ('/files' !== mb_substr($targetPath, 0, 6)
            && '/images' !== mb_substr($targetPath, 0, 7)
        ) {
            throw new Exception(_('Invalid destination.'));
        }

        $this->targetPath = $targetPath;
    }

    public function process(
        UploadedFile $uploadedFile,
        string $destinationType,
        string $description
    ): File {
        if (!$uploadedFile->isValid()) {
            throw new Exception(_('No file recived.'));
        }

        $fileName = $uploadedFile->getClientOriginalName();
        $fileName = pathinfo($fileName, PATHINFO_FILENAME);
        $this->baseName = genfilename($fileName);

        $fileExtension = $uploadedFile->getClientOriginalExtension();
        $this->extension = mb_strtolower($fileExtension);

        $this->file = $uploadedFile;

        return $this->processFile($destinationType, $description);
    }

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

    private function isImageFile(): bool
    {
        return in_array($this->file->getMimeType(), ['image/jpeg', 'image/gif', 'image/png'], true);
    }

    private function isVideoFile(): bool
    {
        return mb_strpos($this->file->getMimeType(), 'video/') === 0;
    }

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

        $format = 'jpg' === $this->extension ? 'jpeg' : $this->extension;
        $image->save($target, $format);

        $this->file = new FileHandeler($target, false);
    }

    /**
     * @throws Exception If we don't have the needed memory avalibe
     */
    private function checkMemorry(Image $image): void
    {
        $memoryLimit = returnBytes(ini_get('memory_limit')) - 270336; // Estimated overhead, TODO substract current usage
        if ($image->getWidth() * $image->getHeight() > $memoryLimit / 10) {
            throw new Exception(_('Image is to large to be processed.'));
        }
    }

    private function insertFile(string $description, int $width, int $height): File
    {
        $file = File::getByPath($this->getDestination());
        if ($file) {
            $file->delete();
        }

        $this->file->move(_ROOT_ . $this->targetPath, $this->baseName . '.' . $this->extension);

        return File::fromPath($this->getDestination())
            ->setDescription($description)
            ->setWidth($width)
            ->setHeight($height)
            ->save();
    }

    private function getDestination(): string
    {
        return $this->targetPath . '/' . $this->baseName . '.' . $this->extension;
    }
}
