<?php

namespace App\Service\base;

use Faker\Factory;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;

class TestHelper
{



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

    private function strInArray($string, $arrayOfSubstring)
    {
        foreach ($arrayOfSubstring as $sub) {
            if (strpos($string, $sub) !== false) {
                return true;
            }
        }
        return false;
    }
}
