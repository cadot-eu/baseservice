<?php

namespace App\Service\base;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem;

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

    public function upload(File $file, $dir = '', $copy_no_move = false)
    {
        if ($copy_no_move) {
            $fs = new Filesystem();
            $targetPath = sys_get_temp_dir() . '/' . uniqid();
            $fs->copy($file, $targetPath, true);
            $file = new File($targetPath);
        }
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }
        //majsucule minuscule, accents conservés et ' remplacé par _ et les autres remplacé par - idem pour _

        $safeFilename = $this->slugger->slug(str_replace([' ', '_', '.', "'", '-'], ['ZYSPACEYZ', 'ZYUNDERSCOREYZ', 'ZYPOINTYZ', 'ZYAPOSTROPHEYZ', 'ZYTIRETYZ'], $this->fileName($originalFilename)));
        $extension = $this->fileExtension($originalFilename);
        $fileName = $safeFilename . '.' . uniqid() . '.' . $extension;
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

    static function fileExtension($s)
    {
        $n = strrpos($s, ".");
        return ($n === false) ? "" : substr($s, $n + 1);
    }
    static function fileName($s)
    {
        $n = strrpos($s, ".");
        return ($n === false) ? $s : substr($s, 0, $n);
    }
    static function cleanname($string)
    {
        $info = pathinfo($string);
        $point = strrpos($info['filename'], ".");
        $filename = substr($info['filename'], 0, $point);
        return str_replace(['ZYSPACEYZ', 'ZYUNDERSCOREYZ', 'ZYPOINTYZ', 'ZYAPOSTROPHEYZ', 'ZYTIRETYZ'], [' ', '_', '.', "'", '-'],  $filename . '.' . $info['extension']);
    }
}
