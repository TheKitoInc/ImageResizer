<?php

function removeArgs(string $path): string
{
    $elements = explode('?', $path, 2);
    return $elements[0];
}

function parsePath(string $path): string
{
    $elements = array();
    foreach (explode('/', str_replace("\\", '/', $path)) as $element)
    {
        if (empty($element))
            continue;

        if ($element == '.')
            continue;

        if ($element == '..')
            array_pop($elements);

        $elements[] = $element;
    }

    return implode('/', $elements);
}

function getVirtualPath(string $uri, string $script): string
{
    $elementsURI = explode('/', $uri);
    $elementsSCRIPT = explode('/', $script);

    while (count($elementsSCRIPT) > 0 && count($elementsURI) > 0)
    {
        $elementSCRIPT = array_shift($elementsSCRIPT);
        if ($elementsURI[0] == $elementSCRIPT)
        {
            array_shift($elementsURI);
        }
        else
            break;
    }


    return implode('/', $elementsURI);
}

function getBaseImagePath(string $path): string
{
    $name = explode('-', pathinfo($path, PATHINFO_FILENAME), 2);

    return parsePath(
            pathinfo($path, PATHINFO_DIRNAME) . '/' .
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

function getFullPath(string $path): string
{
    return __DIR__ . '/' . $path;
}

function scaleImage(string $src, int $width, int $height): string
{
    $dst = pathinfo($src, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($src, PATHINFO_FILENAME) . '-' . $width . 'x' . $height . '.' . pathinfo($src, PATHINFO_EXTENSION);

    $image = new \Imagick($src);
    $image->scaleImage($width, $height);
    $image->writeImage($dst);
    $image->clear();

    return $dst;
}

$VIRTUAL = getVirtualPath(
        parsePath(removeArgs(filter_input(INPUT_SERVER, 'DOCUMENT_ROOT') . '/' . filter_input(INPUT_SERVER, 'REQUEST_URI'))),
        parsePath(__FILE__)
);

$SRC = getFullPath(getBaseImagePath($VIRTUAL));


if (!file_exists($SRC))
{
    http_response_code(404);
    print_r(array(
        parsePath(removeArgs(filter_input(INPUT_SERVER, 'REQUEST_URI'))),
        parsePath(removeArgs(filter_input(INPUT_SERVER, 'SCRIPT_NAME')))
    ));
    die();
}

$SIZE = getImageSizeFromName($VIRTUAL);

$DST = scaleImage($SRC, $SIZE['width'], $SIZE['height']);

if (!file_exists($DST))
{
    http_response_code(508);
    die();
}

header('Content-Size: ' . filesize($DST));
header('Content-Type: image');
readfile($DST);
