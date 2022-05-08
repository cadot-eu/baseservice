<?php

namespace App\Service\base;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private $slugger;

    private $logger;

    protected $parameterBag;

    public function __construct(SluggerInterface $slugger, LoggerInterface $logger, ParameterBagInterface $parameterBag)
    {
        $this->slugger = $slugger;
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
    }

    public function upload(UploadedFile $file, $dir = '')
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $extension = $this->fileExtension($file->getClientOriginalName());
        $fileName = $safeFilename . '¤' . uniqid() . '.' . $extension;

        try {
            $sdir = $this->slugger->slug($dir);
            $destDir = "/app/public/uploads/" . $sdir;
            @mkdir($destDir, 0777, true);
            $file->move($destDir, $fileName);
        } catch (FileException $e) {
            $this->logger->error('failed to upload image: ' . $e->getMessage());
            throw new FileException('Failed to upload file' . $e->getMessage());
        }

        return $destDir = "uploads/" . $sdir . '/' . $fileName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    private function fileExtension($s)
    {
        $n = strrpos($s, ".");
        return ($n === false) ? "" : substr($s, $n + 1);
    }
}
