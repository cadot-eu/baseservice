<?php

namespace App\Service\base;

class StringHelper
{
    /**
     * To extract a string between 2 strings or characters
     *
     * @param string $string    chaine
     * @param string $stringdeb chaine de début
     * @param string $stringfin chaine de fin
     * @return string
     */
    static function chaine_extract($string, $stringdeb, $stringfin): string
    {
        $deb = strpos($string, $stringdeb);
        if ($deb === false) return '';
        $fin = strpos($string, $stringfin, $deb);
        if ($fin === false) $fin = strlen($string);
        return substr($string, $deb + strlen($stringdeb), $fin - $deb - strlen($stringdeb));
    }

    /**
     * chaine_remplace
     * example chaine_remplace($html, 'background-image', ')', 'background-image: url({{file' . $file  . '}})');  
     * @param  string $html
     * @param  string $debs
     * @param  string $fins
     * @param  string $chaine
     * @param  int $start
     * @return string
     */
    static function chaine_remplace(string $html, string $debs, string $fins, string $chaine, int $start = 0): string
    {
        $pos = strpos($html, $debs, $start);
        $fin = strpos($html, $fins, $pos + 1);
        return $html = substr($html, $start, $pos) . $chaine . substr($html, $fin + 1);
    }

    static function extract($str, $pos, $start, $end = '')
    {
        if ($end == '') $end = $start;
        $sub = strpos($str, $start, $pos); //on cherche la position du départ dans la chaine
        $sub += strlen($start); //on ajoute la longueur de la chaine départ
        $size = strpos($str, $end, $sub) - $sub; //on calcule la taille de la chaine-le départ
        return substr($str, $sub, $size); //on retourne la chaine
    }


    static function insert($chaine, $strdeb, $insert, $after = true)
    {
        if ($after) $pos = strpos((string)$chaine, $strdeb) + strlen($strdeb);
        else $pos = strpos((string)$chaine, $strdeb);
        return substr_replace((string)$chaine, $insert, $pos, 0);
    }


    /**
     * Retrieves a text between a tag (EXAMPLE <H1> Text </ H1>
     *
     * @param  string $string
     * @param  string $balise
     * @return array
     */
    static function balise_extract(string $string, string $balise): array
    {
        preg_match("/<$balise [^>]+>(.*)<\/$balise>/", $string, $match);
        return $match;
    }
    /**
     * Retrieves All a text between a tag (EXAMPLE <H1> Text </ H1>
     *
     * @param  string $string
     * @param  string $balise
     * @return array
     */
    static function balise_extract_all($string, $balise): array
    {
        preg_match_all("/<$balise [^>]+>(.*)<\/$balise>/", $string, $match);
        return $match;
    }
    /**
     * Retrieves a text between a tag (EXAMPLE <H1> Text and end tag </ H1>
     *
     * @param  string $string
     * @param  string $balise
     * @param  string $end
     * @return array
     */
    static function balise_extract_begin_end($string, $balise, $end): array
    {
        preg_match_all("/<$balise [^>]+>(.*)<$end/", $string, $match);
        return $match;
    }
}
