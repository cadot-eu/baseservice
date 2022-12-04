<?php

namespace App\Service\base;

use Faker\Factory;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;
use Doctrine\ORM\EntityManagerInterface;

class TestHelper
{

    /**
     * It fills a form with random data, and then clicks the submit button
     * 
     * @param B the browser object
     * @param url the url of the page you want to test
     * @param erreurCaptcha if true, the captcha will be filled with the wrong value.
     * @param champEmailVrai if you want to fill the email field with a real email address, pass it
     * here.
     * 
     * @return The return value of the last statement executed in the function.
     */
    static public function remplisFormulaire($B, $url, $erreurCaptcha = false, $champEmailVrai = false)
    {
        $faker = Factory::create('fr_FR');
        $visit = $B->visit($url);
        $crawler = $visit->crawler();
        $form = $crawler->selectButton('bouton_submit')->form();
        $champs = $form->getValues();
        foreach ($champs as $nom => $value) {

            /** @var DOMElement $node */
            $node = $crawler->selectButton($nom)->getNode(0);
            if ($node) {
                $type = $node->getAttribute('type');
                switch ($type) {
                    case 'text':
                        switch (true) {
                            case self::strInArray($nom, ['nom', 'name']):
                                $visit->fillField($nom, $faker->name());
                                break;
                            case 'captcha':
                                if ($erreurCaptcha) {
                                    $valCaptcha = $crawler->selectButton('try')->getNode(0)->getAttribute('value');
                                } else {
                                    $valCaptcha = strrev($crawler->selectButton('try')->getNode(0)->getAttribute('value'));
                                }
                                $visit->fillField('captcha', $valCaptcha);
                                break;
                            default:
                                dump("type de champ ($nom) non défini à ajouter dans le switch de text");
                                break;
                        }

                        break;

                    case 'email':
                        if ($champEmailVrai) {
                            $visit->fillField($nom, $champEmailVrai);
                        } else {
                            $visit->fillField($nom, $faker->safeEmail());
                        }
                        break;
                    case 'inconnu':
                    case 'hidden':
                        break;
                    default:
                        dd("type de champ ($type)inconnu à ajouter dans le switch");
                        break;
                }
            } else { //pour les textareas
                $visit->fillField($nom, $faker->realText(50));
            }
        }
        return $visit->click('bouton_submit'); //verify 200
    }

    /**
     * It takes a string and an array of substrings, and returns true if the string contains any of the
     * substrings
     * 
     * @param string The string to search in.
     * @param arrayOfSubstring An array of substrings to search for.
     * 
     * @return True or False
     */
    static public function strInArray($string, $arrayOfSubstring)
    {
        foreach ($arrayOfSubstring as $sub) {
            if (strpos($string, $sub) !== false) {
                return true;
            }
        }
        return false;
    }
    /**
     * verif if in field of entity picture src exist in the file
     * 
     * @param string $entities array of entities with fields in value
     * 
     * @return Array of src don't exist in the hd
     */
    static public function verifImageExiste(EntityManagerInterface $em, array $entities): array
    {
        $nontrouve = [];
        foreach ($entities as $entity => $fields) { //boucle sur les entitées
            $datas = $em->getRepository('App\Entity\\' . ucfirst($entity))->findAll();
            foreach ($datas as $data) { //boucle sur les données des entitées
                foreach (explode(',', $fields) as $field) { //boucle sur les champs des données des entitées
                    $parser = new ParserDocblock($entity);
                    $getField = 'get' . ucfirst($field);
                    $id = $data->getId();
                    if ($parser->getType($field) == 'text') { //pour un texte on recherche les images
                        $c = new Crawler($data->$getField());
                        $return = array_filter($c->filter('img')->each(function ($node, $i) use ($entity, $id, $field) { //boucle sur les images des champs des données des entitées
                            if (!file_exists('/app/public' . $node->attr('src'))) {
                                return "[$entity($id)-$field]=>" . '/app/public' . $node->attr('src');
                            }
                        }));
                    } else {
                        if (!file_exists('/app/public' . $data->$getField())) {
                            $return = ["[$entity($id)-$field]=>" . '/app/public' . $data->$getField()];
                        }
                    }
                    if ($return) $nontrouve = array_merge($return, $nontrouve);
                }
            }
        }
        return $nontrouve;
    }
}
