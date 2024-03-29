<?php

namespace App\Service\base;

use App\Repository\ParametresRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;

class FileHelper
{
    static public function deleteDirectory_notempty($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . '/' . $object) == 'dir') {
                        FileHelper::deleteDirectory_notempty($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }

            reset($objects);
            rmdir($dir);
        }
    }
    static public function extension($string)
    {
        return pathinfo($string, PATHINFO_EXTENSION);
    }
}
