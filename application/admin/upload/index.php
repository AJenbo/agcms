<?php

use AGCMS\Service\UploadHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/../logon.php';

$request = request();

/** @var UploadedFile */
$uploadedFile = $request->files->get('upload');
$targetPath = $request->get('dir', '/images');

$destinationType = $request->get('type', '');
$width = $request->get('x', 0);
$height = $request->get('y', 0);
$description = $request->get('alt', '');

$response = new Response();
$response->headers->set('Content-Type', 'application/json');

try {
    $uploadHandler = new UploadHandler($targetPath);
    $file = $uploadHandler->process($uploadedFile, $destinationType, $description, $width, $height);

    $data = [
        'uploaded' => 1,
        'fileName' => basename($file->getPath()),
        'url' => $file->getPath(),
        'width' => $file->getWidth(),
        'height' => $file->getHeight(),
    ];
    $response->setContent(json_encode($data));
} catch (Throwable $exception) {
    $data = [
        'uploaded' => 0,
        'error' => [
            'message' => _('Error: ') . $exception->getMessage(),
        ],
    ];
    $response->setContent(json_encode($data));
}

$response->send();
