<?php

ob_start();

function removeArgs(string $path): string
{
    $elements = explode('?', $path, 2);
    return $elements[0];
}

function parsePath(string $path): string
{
    $elements = array();
    foreach (explode(DIRECTORY_SEPARATOR, str_replace("\\", DIRECTORY_SEPARATOR, str_replace('/', DIRECTORY_SEPARATOR, $path))) as $element)
    {
        if (empty($element))
            continue;

        if ($element == '.')
            continue;

        if ($element == '..')
            array_pop($elements);

        $elements[] = $element;
    }

    return implode(DIRECTORY_SEPARATOR, $elements);
}

function getBaseImagePath(string $path): string
{
    $name = explode('-', pathinfo($path, PATHINFO_FILENAME), 2);

    return parsePath(
            pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR .
            $name[0] . '.' .
            pathinfo($path, PATHINFO_EXTENSION)
    );
}

function getImageSizeFromName(string $path): array
{
    $name = explode('-', pathinfo($path, PATHINFO_FILENAME), 2);

    if (count($name) != 2)
        return null;

    $wh = explode('x', strtolower($name[1]), 2);

    if (count($wh) != 2)
        return null;

    return array(
        'width' => $wh[0],
        'height' => $wh[1]
    );
}

function sendImage(string $path): void
{
    ob_clean();
    header('Content-Size: ' . filesize($path));
    header('Content-Type: image');
    readfile($path);
    exit();
}

function sendImagick(\Imagick $image): void
{
    ob_clean();
    header('Content-Size: ' . $image->getImageLength());
    header('Content-Type: image/' . $image->getImageFormat());
    echo $image;
}

$SITE_ROOT = realpath(parsePath(filter_input(INPUT_SERVER, 'DOCUMENT_ROOT')));
//error_log($SITE_ROOT);

$REQUEST_PATH = parsePath(removeArgs(filter_input(INPUT_SERVER, 'REQUEST_URI')));
//error_log($REQUEST_PATH);

$DST = parsePath($SITE_ROOT . DIRECTORY_SEPARATOR . $REQUEST_PATH);
//error_log($DST);

$SIZE = getImageSizeFromName($DST);
//error_log($SIZE);

$SRC = getBaseImagePath($DST);
//error_log($SRC);

if (!file_exists($SRC))
{
    http_response_code(404);
    die();
}

if (file_exists($DST) && filemtime($DST) > filemtime($SRC))
{
    sendImage($DST);
}

$image = new \Imagick($SRC);
$image->scaleImage($SIZE['width'], $SIZE['height']);

if (is_writable(dirname($DST)))
{
    $image->writeImage($DST);
    $image->clear();
    sendImage($DST);
}
else
{
    sendImagick($image);
}
