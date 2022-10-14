<?php

namespace App\Service\base;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Gedmo\Sluggable\Util\Urlizer;
use Liip\ImagineBundle\Service\FilterService;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileUploader
{
    private $slugger;

    private $logger;

    protected $parameterBag;
    private $filterService;

    public function __construct(FilterService $filterService, SluggerInterface $slugger, LoggerInterface $logger, ParameterBagInterface $parameterBag)
    {
        $this->filterService = $filterService;
        $this->slugger = $slugger;
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
    }

    public function upload(UploadedFile $file, $dir = '', $filter = null)
    {

        if ($filter != null) {
            $fileName = FileUploader::encodeFilename(FileUploader::fileName($file->getClientOriginalName()) . "_$filter" . "." . FileUploader::fileExtension($file->getClientOriginalName()));
        } else
            $fileName = FileUploader::encodeFilename($file->getClientOriginalName());
        try {
            $sdir = $this->slugger->slug($dir);
            $destDir = "/app/public/uploads/" . $sdir;
            @mkdir($destDir, 0777, true);
            $destfile = $file->move($destDir, $fileName);
        } catch (FileException $e) {
            $this->logger->error('failed to upload image: ' . $e->getMessage());
            throw new FileException('Failed to upload file' . $e->getMessage());
        }
        if ($filter) {
            rename('/app/public/' . parse_url($this->filterService->getUrlOfFilteredImage(substr($destfile->getPathName(), strlen('/app/public/')), $filter))['path'], $destfile->getPathName());
        }
        if (file_exists($destfile->getPathName())) {
            return $destDir = "uploads/" . $sdir . '/' . $fileName;
        }
        return null;
    }
    /* A function that is used to encode the filename. */
    static function encodeFilename($originalFilename)
    {
        return Urlizer::urlize(FileUploader::fileName($originalFilename)) . '_' . uniqid() . '.' . FileUploader::fileExtension($originalFilename);
    }
    /* A function that is used to decode the filename. */
    static function decodeFilename($encodeFilename)
    {
        return explode('_', $encodeFilename)[0] . '.' . FileUploader::fileExtension($encodeFilename);
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    static function fileExtension($s)
    {
        $n = strrpos($s, ".");
        return ($n === false) ? "" : strtolower(substr($s, $n + 1));
    }
    static function fileName($s)
    {
        $n = strrpos($s, ".");
        return ($n === false) ? $s : substr($s, 0, $n);
    }
    static function cleanname($string)
    {
        //return $string;
        $info = pathinfo($string);
        $point = strrpos($info['filename'], ".");
        $filename = substr($info['filename'], 0, $point);
        return 'dd' . $filename; // . '.' . $info['extension'];
    }
}
