<?php

namespace App\Service\base;

use App\Repository\ParametresRepository;
use App\Twig\base\AllExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;

class ToolsHelper
{
    /**
     * Get the list of enabled locales from the translation.yaml file
     *
     * @return An array of enabled locales.
     */
    public static function get_langs()
    {
        $yaml = new Yaml();
        if (file_exists('/app/config/packages/translation.yaml')) {
            $trans = $yaml->parseFile('/app/config/packages/translation.yaml')['framework'];
            if (isset($trans['enabled_locales'])) {
                return $trans['enabled_locales'];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * This function will return a random article from Wikipedia
     *
     * @return the content of the page.
     */
    public static function wikipedia_article_random()
    {
        if (!function_exists('curl_init')) {
            return false;
        }
        $url = 'https://en.wikipedia.org/api/rest_v1/page/random/html';
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            // return web page
            CURLOPT_HEADER => false,
            // don't return headers
            CURLOPT_FOLLOWLOCATION => true,
            // follow redirects
            CURLOPT_ENCODING => "",
            // handle all encodings
            CURLOPT_USERAGENT => "spider",
            // who am i
            CURLOPT_AUTOREFERER => true,
            // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 30,
            // timeout on connect
            CURLOPT_TIMEOUT => 30,
            // timeout on response
            CURLOPT_MAXREDIRS => 3,
            // stop after 10 redirects
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);
        return $content;
        if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
            $title = str_replace(' - Wikipedia, the free encyclopedia', '', $matches[1]);
        }

        return '<a href="' . $header['url'] . '">' . $title . '</a>';
    }

    /**
     * It returns an array of all the parameters in the database
     *
     * @param EntityManagerInterface em The entity manager
     *
     * @return An array of all the parameters in the database.
     */
    public static function params(EntityManagerInterface $em)
    {
        $tab = [];
        foreach ($em->getRepository('App:Parametres')->findAll() as $parametre) {
            $tab[AllExtension::ckclean($parametre->getNom())] = AllExtension::ckclean($parametre->getValeur());
        }
        return $tab;
    }
}
