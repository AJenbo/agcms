<?php

use AGCMS\Service\UploadHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/../logon.php';

$request = request();

/** @var UploadedFile */
$uploadedFile = $request->files->get('Filedata');
$targetPath = $request->get('dir', '/images');

$destinationType = $request->get('type');
$width = $request->get('x', 0);
$height = $request->get('y', 0);
$aspect = $request->get('aspect');
$description = $request->get('alt', '');

$response = new Response();
$response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

try {
    $uploadHandler = new UploadHandler($targetPath);
    $uploadHandler->process(
        $uploadedFile,
        $destinationType,
        $description,
        $width,
        $height,
        $aspect
    );
} catch (Throwable $exception) {
    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    $response->setContent($exception->getMessage());
}

$response->send();
