<?php

namespace App\Service\base;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\DomCrawler\Crawler;
use App\Service\base\StringHelper;

class StringHelper
{

    static function addLink($string, $search, $link)
    {
        $start = 0;
        while (($pos = strpos(strtolower($string), strtolower($search), $start)) !== false) {
            $new = '<a href="' . $link . '">' . substr($string, $pos, strlen($search)) . '</a>';
            $string = substr($string, 0, $pos) . $new . substr($string, $pos + strlen($search));
            $start = $pos + strlen($new);
        }
        return $string;
    }

    /**
     * It extracts a string from a string
     * 
     * @param string The string to search in.
     * @param stringdeb The string that marks the beginning of the string you want to extract.
     * @param stringfin The string that marks the end of the string you want to extract.
     * 
     * @return string a string.
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
     * It replaces a string between two other strings
     * 
     * @param string html the string to be modified
     * @param string debs The string that will be replaced
     * @param string fins The end of the string to be replaced.
     * @param string chaine The string to be replaced
     * @param int start the position to start searching from
     * @param bool casse if true, the search is case insensitive
     * 
     * @return string the string with the replaced text.
     */
    static function chaine_remplace(string $html, string $debs, string $fins, string $chaine, int $start = 0, bool $casse = false): string
    {
        $pos = strpos($casse ? strtolower($html) : $html, $casse ? strtolower($debs) : $debs, $start);
        $fin = strpos($casse ? strtolower($html) : $html, $casse ? strtolower($fins) : $fins, $pos + 1);
        return $html = substr($html, $start, $pos) . $chaine . substr($html, $fin + 1);
    }

    /**
     * It returns the string between two other strings
     * 
     * @param str The string to search in.
     * @param pos The position to start searching from.
     * @param start The string to start from.
     * @param end The end of the string to extract.
     * 
     * @return the string between the start and end strings.
     */
    static function extract($str, $pos, $start, $end = '')
    {
        if ($end == '') $end = $start;
        $sub = strpos($str, $start, $pos); //on cherche la position du départ dans la chaine
        $sub += strlen($start); //on ajoute la longueur de la chaine départ
        $size = strpos($str, $end, $sub) - $sub; //on calcule la taille de la chaine-le départ
        return substr($str, $sub, $size); //on retourne la chaine
    }


    /**
     * It inserts a string into another string at a specified position
     * 
     * @param chaine the string to be modified
     * @param strdeb The string to search for.
     * @param insert The string to insert
     * @param after true = insert after the string, false = insert before the string
     * 
     * @return The string with the inserted string.
     */
    static function insert($chaine, $strdeb, $insert, $after = true)
    {
        if ($after) $pos = strpos((string)$chaine, $strdeb) + strlen($strdeb);
        else $pos = strpos((string)$chaine, $strdeb);
        return substr_replace((string)$chaine, $insert, $pos, 0);
    }


    /** Retrieves a text between a tag */
    static function getTag($html, $tag)
    {
        $start = strpos($html, "<$tag");
        $endstart = strpos($html, ">", $start);
        $end = strpos($html, "</$tag", $start);
        return substr($html, $endstart + 1, $end - $endstart - 1);
    }


    /**
     * It extracts all the occurences of a given HTML tag and returns an array of arrays containing the
     * tag and its content
     * 
     * @param string html the html code
     * @param string balise the name of the tag you want to extract
     * 
     * @return array An array of arrays.
     */
    static function balise_extract_all(string $html, string $balise): array
    {
        $start = 0;
        $result = [];
        while (($pos = strpos($html, "<$balise", $start)) !== false) {
            $tab = [];
            $endstart = strpos($html, ">", $pos);
            $end = strpos($html, "</$balise", $pos);
            $tab[0] = substr($html, $pos, $endstart - $pos + 1);
            $startnext = strpos($html, "<$balise", $end);
            $tab[1] = substr($html, $end + strlen($balise) + 3, $startnext !== false ? $startnext - $end - 3 - strlen($balise) : strlen($html) - $end - 3 - strlen($balise));
            $result[] = $tab;
            $start = $end;
        }
        return $result;
    }

    /**
     * It takes a string, a tag, and a starting position, and returns an array containing the first tag
     * and the text between it and the next tag
     * 
     * @param html the HTML code to parse
     * @param tag the tag you want to extract
     * @param start the position of the first character of the tag
     * 
     * @return array an array with two elements. The first element is the tag and its content, the
     * second element is the content of the tag.
     */
    static function balise_extract($html, $tag, $start = 0): array
    {
        $start = strpos($html, "<$tag");
        $tab = [];
        if ($start !== false) {
            $end = strpos($html, "</$tag", $start);
            $endend = strpos($html, ">", $end);
            $tab[0] = substr($html, $start, $endend - $start);
            $startnext = strpos($html, "<$tag", $endend);
            $tab[1] = substr($html, $endend + 1, $startnext !== false ? $startnext - $endend - 1 : strlen($html) - $endend - 1);
        }
        return $tab;
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
    /**
     * If the string starts with the substring, remove the substring from the string
     * 
     * @param string The string to be trimmed.
     * @param substring The substring to remove from the start of the string.
     * @param trimonsubstring If set to true, the function will trim the substring before comparing it
     * to the string.
     * 
     * @return The string without the substring at the beginning.
     */
    static function  removeStart($string, $substring, $trimonsubstring = false)
    {
        $substring = $trimonsubstring ? trim($substring) : $substring;
        if (substr(trim($string), 0, strlen($substring)) == $substring)
            return substr(trim($string), strlen($substring));
        else return $string;
    }
    /**
     * It removes the last occurrence of a substring from a string
     * 
     * @param string The string to be trimmed
     * @param substring The substring to remove from the end of the string.
     * @param trimonsubstring If set to true, the function will trim the  before comparing
     * it to the end of the .
     * 
     * @return The string without the substring at the end.
     */
    static function  removeEnd($string, $substring, $trimonsubstring = false)
    {
        $substring = $trimonsubstring ? trim($substring) : $substring;
        if (substr(trim($string), -strlen($substring) - 1) == $substring)
            return substr(trim($string), 0, strlen(trim($string)) - strlen($substring));
        else return $string;
    }
    /**
     * It removes the first and last occurrence of a substring from a string
     * 
     * @param string The string to be modified
     * @param substring The substring to remove from the start and end of the string.
     * @param endsubstring The substring to remove from the end of the string. If this is not
     * specified, it will default to the same value as .
     * @param trimonsubstring If true, the string will be trimmed on the substring.
     * 
     * @return The string with the start and end substrings removed.
     */
    static function removeStartAndEnd($string, $substring, $endsubstring = '', $trimonsubstring = false)
    {
        $endsubstring = $endsubstring == '' ? $substring : $endsubstring;
        return StringHelper::removeEnd(StringHelper::removeStart($string, $substring, $trimonsubstring), $endsubstring, $trimonsubstring);
    }
    /**
     * It takes a string, removes all HTML tags, removes all stop words, removes all words that are
     * too short, removes all numbers, and then returns an array of the most common words in the
     * string
     * 
     * @param string The string to extract keywords from.
     * @param number The number of keywords you want to return.
     * 
     * @return array An array of keywords
     */
    static function keywords($string, $number = 10): array
    {
        $string = strip_tags($string);
        $stopwords = array();
        //$string = preg_replace('/[\pP]/u', '', trim(preg_replace('/\s\s+/iu', '', mb_strtolower($string))));
        $matchWords = array_filter(explode(' ', $string), function ($item) use ($stopwords) {
            return !($item == '' || in_array($item, $stopwords) || mb_strlen($item) <= 2 || is_numeric($item));
        });
        $wordCountArr = array_count_values($matchWords);
        arsort($wordCountArr);
        $mots = "des,les,est,un,une,le,de,pour,qui,que,quoi,ou,donc,or,ni,car,parce,lequel,laquelle,lesquelles,sur,par,je,tu,il,nous,vous,il,ils,elles,plus,pas,ne,ni,sont,dans,tous,tout,ont,avec,pour,contre,mais,sans,au,à,qu'une,qu'un,qu',ce,ces,se,ses,comme,d'un,d'une,fois,leur,leurs,oui,non,moins,dont,aux,n'est,lorsque,faire,son,fais,fait,la,les,entre,doivent,être,à, a, soit, mais, ou, et, car, ni, puisque, que, parce que, ainsi que, alors que, afin que, à moins que, si comme, pendant, à condition, c’est-à-dire, alors que, dès, le, la, les, un, une, des, du, de la, des, peut, effet, toutes,très, verte, font, même";
        $tab = [];
        foreach ($wordCountArr as $w => $val) {
            $w = strtolower(trim($w));
            if (preg_match('/<|>|&/i', $w) == 0)
                if (!in_array($w, explode(',', $mots))) {
                    if ($w) $tab[$w] = $val;
                }
        }
        return array_keys(array_slice($tab, 0, $number));
    }

    static function generateIndex($html, $depth = 6)
    {
        //get the headings down to the specified depth
        $pattern = '/<h[2-' . $depth . ']*[^>]*>.*?<\/h[2-' . $depth . ']>/';
        $whocares = preg_match_all($pattern, $html, $winners);

        //reformat the results to be more usable
        $heads = implode("\n", $winners[0]);
        $heads = str_replace('<a name="', '<a href="#', $heads);
        $heads = str_replace('</a>', '', $heads);
        $heads = preg_replace('/<h([1-' . $depth . '])>/', '<li class="toc$1">', $heads);
        $heads = preg_replace('/<\/h[1-' . $depth . ']>/', '</a></li>', $heads);

        //plug the results into appropriate HTML tags
        $contents = '<div id="toc"> 
	<p id="toc-header">Contents</p>
	<ul>
	' . $heads . '
	</ul>
	</div>';
        return $contents;
    }
    /**
     * It takes a HTML string and a directory as parameters, and it downloads all the images from the
     * HTML string to the directory, and it returns the HTML string with the images' src attributes
     * updated to point to the downloaded images
     * 
     * @param html the html code to parse
     * @param dir the directory where the images will be saved
     * 
     * @return The HTML of the page with the images downloaded and the src attribute changed to the
     * local path.
     */
    static function UrlDistanteToDir($html, $dir, $type = 'img')
    {
        $link = $type == 'img' ? 'src' : 'href';
        $slugger = new AsciiSlugger();
        $crawler = new Crawler($html);
        $host = parse_url($_SERVER['HTTP_REFERER'])['host'];
        foreach ($crawler->filter($type) as $img) {
            /** @var Node $img */
            if (isset(parse_url($img->getAttribute($link))['host']) && $host != parse_url($img->getAttribute($link))['host']) {
                //on télécharge l'image
                $decompose = explode('/', parse_url($img->getAttribute($link))['path']);
                @mkdir($dir, 0777, true);
                $nomFichier = $dir  . "/" . $slugger->slug(end($decompose));
                if (copy($img->getAttribute($link), $nomFichier) == false) {
                    return new \Exception("Impossible de télécharger l'image " . $img->getAttribute($link));
                } else {
                    $img->setAttribute($link, '/' . $nomFichier);
                }
            };
        }
        return $crawler->html();
    }
}
