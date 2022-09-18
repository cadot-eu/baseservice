<?php

namespace App\Service\base;

use App\Repository\ParametresRepository;
use App\Twig\base\AllExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ImageHelper
{
    static function renommerImageByField(EntityManagerInterface $em, array $entities, string $champ)
    {
        $erreurs = [];
        foreach ($entities as $entity => $fields) { //boucle sur les entitées
            $datas = $em->getRepository('App\Entity\\' . ucfirst($entity))->findAll();
            foreach ($datas as $data) { //boucle sur les données des entitées
                foreach (explode(',', $fields) as $field) { //boucle sur les champs des données des entitées
                    $parser = new ParserDocblock($entity);
                    $getField = 'get' . ucfirst($field);
                    $setField = 'set' . ucfirst($field);
                    $getChamp = 'get' . ucfirst($champ);
                    $id = $data->getId();
                    if ($parser->getType($field) == 'text') { //pour un texte on recherche les images
                        $c = new Crawler($data->$getField());
                        $return = array_filter($c->filter('img')->each(function ($node, $i) use ($entity, $id, $setField, $getField, $data, $getChamp) { //boucle sur les images des champs des données des entitées
                            $newnom = "/uploads/$entity/" . FileUploader::encodeFilename($data->$getChamp() . '_' . $i . '.' . FileUploader::FileExtension($node->attr('src')));
                            if (file_exists('/app/public' . $node->attr('src')) && $node->attr('src')  &&  FileUploader::decodeFilename($newnom) !=  FileUploader::decodeFilename($node->attr('src'))) {
                                if (rename('/app/public' . $node->attr('src'), '/app/public' . $newnom)) {
                                    $data->$setField(str_replace($node->attr('src'), $newnom, $data->$getField()));
                                }
                            }
                        }));
                    } else {
                        if (file_exists('/app/public' . $data->$getField()) && $data->$getField()) {
                            $newnom = "/uploads/$entity-pin/" . FileUploader::encodeFilename($data->$getChamp() . '.' . FileUploader::FileExtension($data->$getField()));
                            if (rename('/app/public'  . $data->$getField(), '/app/public' . $newnom)) {
                                $data->$setField($newnom);
                            }
                        }
                    }
                }
                $em->persist($data);
                $em->flush();
            }
        }
    }
}
