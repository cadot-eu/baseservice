<?php

namespace App\Service\base;

use App\Repository\GlossaireRepository;
use Imagine\File\Loader;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use TOC\MarkupFixer;
use TOC\TocGenerator;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Bridge\Monolog\Logger as MonologLogger;
use App\Service\base\LoggerTrait as logger;
use Psr\Log\LoggerAwareTrait;

class ArticleHelper
{

    /**
     * It takes an article, and returns a table of contents for that article
     *
     * @param article The article to generate the Table of Contents for.
     * @param top The top level of the table of contents. Default is 1.
     * @param deep The number of levels of headers to include in the Table of Contents.
     *
     * @return the table of contents for the article.
     */
    public static function getSommaire($article, $top = 1, $deep = 3)
    {
        if ($article == null) {
            return $article;
        }
        $tocGenerator = new TocGenerator();
        // This generates the Table of Contents in HTML
        $toc = $tocGenerator->getHtmlMenu($article, $top, $deep);
        if ($toc == null) {
            return null;
        }
        $dom = new \DOMDocument();
        $dom->loadHTML(utf8_decode($toc));
        $dom->removeChild($dom->doctype);
        //$dom->getElementsByTagName('li')[0]->remove();

        return "<div class='toc'>" . $dom->saveHTMl() . '</div>';
    }

    /**
     * It takes an article, finds all the oembed tags, replaces them with the YoutubeHelper::adaptUrl()
     * function, and returns the article
     *
     * @param article the article to be parsed
     *
     * @return The article with the oembed tags replaced with the YoutubeHelper::adaptUrl()
     */
    public static function addLinkVideos($article)
    {
        $crawler = new Crawler($article);
        $videos = $crawler->filter('oembed');
        foreach ($videos as $video) {
            $video = new Crawler($video);
            $url = $video->attr('url');
            $article = str_replace(
                $video->outerHtml(),
                YoutubeHelper::adaptUrl($url),
                $article
            );
        }
        return $article;
    }

    /**
     * It takes an article and a glossary repository, and returns the article with the glossary terms
     * linked
     *
     * @param article the article to be processed
     * @param GlossaireRepository glossaireRepository the repository of the glossary
     *
     * @return A string with the glossary terms wrapped in a span tag.
     */
    public static function addLinkGlossaire($article, GlossaireRepository $glossaireRepository)
    {
        $article = str_replace('&nbsp;', '', $article);
        $mots = $glossaireRepository->findBy([
            'deletedAt' => null,
            'etat' => 'en ligne',
        ]);
        $crawler = new Crawler($article);
        $ps = $crawler->filter('p');
        foreach ($ps as $p) {
            $p = new Crawler($p);
            $text = $p->outerHtml();
            foreach ($mots as $mot) {
                $terme = $mot->getTerme();
                $title = $mot->getDefinition();
                $text = preg_replace(
                    '/\b' . preg_quote($terme, '/') . '\b/i',
                    "<span  class=\"glossaire\" glossaire=\"$title\">\$0</span>",
                    $text,
                    1
                );
            }
            $article = str_replace($p->outerHtml(), $text, $article);
        }
        return $article;
    }

    public static function imgToSrcset($texte, CacheManager $imagineCacheManager, FilterManager $filtermanager): ?string
    {
        $redimensionnement = 0;
        //on vérifie que texte est un html
        if (strpos($texte, '<img') === false) {
            return $texte;
        }
        //on ajoute un filtre en fonction du champ liip ou au pire on met moyen
        $crawler = new Crawler($texte);
        $filters = $filtermanager->getFilterConfiguration()->all();
        foreach ($crawler->filter('img') as $node) {
            //variables
            //on récupère les filtres
            $srcset = [];
            //on récupère le parent et on vérifie qu'il s'agit bien d'une figure et on récupère le redimensionnement et on le supprime et om met en taille auto
            $figure = $node->parentNode->nodeName == 'a' ? $node->parentNode->parentNode : $node->parentNode;
            if ($figure->nodeName == 'figure') { //anttention andré à tendance à mettre des figures dans des figures
                $style = $figure->parentNode->getAttribute('style');
                $redimensionnement = trim(StringHelper::chaine_extract($style, 'width:', '%'));
                $figure->parentNode->setAttribute('style', "width: auto");
                $figure->setAttribute('style', "margin:auto;");
            }
            //@var node $node
            if (strpos('/media/cache', $node->getAttribute('src')) === false) {
                //on passe le src en data-src pour lazy
                $src = $node->getAttribute('src');
                $node->setAttribute('data-src', $src);
                $node->removeAttribute('src');
                $node->setAttribute('class', 'img-fluid lazy');
                $lien = 'uploads/' . explode('uploads/', $src)[1];
                //on donne la taille de l'image
                //on récupère la largeur de l'image
                $file = $node->getAttribute('data-src')[0] == '/' ? substr($node->getAttribute('data-src'), 1) : $node->getAttribute('data-src');
                //on suprrime l'url de l'image
                $imgClean = explode('uploads/', $file)[1];
                // if (!\file_exists('/app/public/uploads/' . $imgClean)) {
                //     $imgClean = urldecode($imgClean);
                // }
                if (file_exists('/app/public/uploads/' . $imgClean)) {
                    $width = getimagesize('/app/public/uploads/' . $imgClean)[0];
                }
                // on vérifie que l'on est dans le cas d'un image chargée par l'utilisateur
                if (strpos($src, '/uploads') !== false) {
                    //si on a un data-size on le récupère
                    if ($node->getAttribute('data-size')) {
                        if (strpos($node->getAttribute('data-size'), 'px') !== false) {
                            $width = intval(explode(',', $node->getAttribute('data-size'))[0]);
                        }
                        if (strpos($node->getAttribute('data-size'), '%') !== false) {
                            $width = 1920 * intval(explode(',', $node->getAttribute('data-size'))[0]) / 100;
                        }
                    }
                    //on trie les filtres par largeur pour ne garder que ceux qui sont plus petits que l'image plus un filtre plus grand
                    foreach ($filters as $name => $value) {
                        if (isset($value['filters']['relative_resize']['widen'])) {
                            $largeurFiltre = $value['filters']['relative_resize']['widen'];
                            $filtres[$name] = $largeurFiltre;
                        }
                    }
                    //on ne garde les valeurs que si elles sont plus petites que l'image
                    $newfiltres = array_filter($filtres, function ($value) use ($width) {
                        return $value <= $width;
                    });
                    asort($filtres);
                    $resfiltres = array_slice($filtres, 0, count($newfiltres) + 1, true);
                    //on supprime les clefs de même largeur
                    $resfiltres = array_unique($resfiltres);

                    foreach ($resfiltres as $name => $value) {
                        //on ne prend que les filtres qui sont plus petit que l'image et qui utilisent la largeur
                        $srcset[] = $imagineCacheManager->getBrowserPath($lien, $name) . " $value" . "w ";
                        //on nep rend pas de largeur d'écran inférieur à 300px

                        $sizes[] = "(min-width: " . ($value + 20) . " px) $value px";
                    }
                }
                $node->removeAttribute('style');
                $node->setAttribute('data-srcset', implode(',', $srcset));
                if (strpos($node->getAttribute('data-size'), 'px') !== false) {
                    $node->setAttribute('style', "width:" . $width . "px;max-width:100%;");
                } elseif (strpos($node->getAttribute('data-size'), '%') !== false) {
                    $node->setAttribute('style', "width:" . explode(',', $node->getAttribute('data-size'))[0] . ";");
                    $node->parentNode->setAttribute('style', "text-align:center;");
                } else {
                    $node->setAttribute('style', "width:" . $width . "px;max-width:100%;");
                }
            }
        }
        if ($crawler->filter('body')->html() == null) {
            return $crawler->html();
        }
        return $crawler->filter('body')->html();
    }

    public static function removeFigureInclusFigure($texte)
    {
        $crawler = new Crawler($texte);
        if ($crawler->count() > 0) {
            foreach ($crawler->filter('figure') as $node) {
                //si on a pas une class table
                if (strpos($node->getAttribute('class'), 'table') !== false) {
                    //on regarde tous les enfants et on supprime les figures enfants
                    foreach ($node->childNodes as $child) {
                        if ($child->nodeName == 'figure') {
                            $node->removeChild($child);
                        }
                    }
                }
            }
            return $crawler->filter('body')->html();
        } else {
            return $texte;
        }
    }


    public static function datasrcToSrc($texte)
    {
        return str_replace('data-src', 'src', $texte);
    }
    public static function addFilterLiip($texte, CacheManager $imagineCacheManager, FilterManager $filterLoader)
    {
        //on ajoute un filtre en fonction du champ liip ou au pire on met moyen
        $crawler = new Crawler($texte);
        foreach ($crawler->filter('img') as $node) {
            //@var node $node
            if (strpos('/media/cache', $node->getAttribute('src')) === false) {
                $pos = strpos($node->getAttribute('src'), '/uploads/');
                $url = substr($node->getAttribute('src'), $pos);
                //en fonction de la taille de l'image on met un filtre
                //on découpe le style pour récupérer la largeur
                $width = trim(StringHelper::chaine_extract($node->getAttribute('style'), 'width:', 'px')) ?: 500;
                //on récupère les filtres de liip
                $filters = $filterLoader->getFilterConfiguration()->all();
                $filtres = [];
                foreach ($filters as $name => $value) {
                    if (isset($value['filters']['relative_resize']['widen'])) {
                        $filtres[$name] = $value['filters']['relative_resize']['widen'];
                    }
                }
                //on sort les filtres par largeur
                asort($filtres);
                //on prend le filtre le plus proche de la largeur de l'image
                $closest = array_reduce(array_keys($filtres), function ($prev, $curr) use ($filtres, $width) {
                    return (abs($filtres[$curr] - $width) < abs($filtres[$prev] - $width)) ? $curr : $prev;
                }, array_keys($filtres)[0]);
            }
        }
        return ($crawler->filter('body')->html());      //on vérifie aussi les img
        return $crawler->html();
    }
    public static function addTableClass($texte, $class = "table table-striped table-bordered align-middle text-center")
    {
        $crawler = new Crawler($texte);
        if ($crawler->count() > 0) {
            foreach ($crawler->filter('table') as $node) {
                $node->setAttribute('class', $class);
            }
            return $crawler->html();
        } else {
            return $texte;
        }
    }
    public static function rmtableStyle($texte)
    {
        $crawler = new Crawler($texte);
        if ($crawler->count() > 0) {
            foreach ($crawler->filter('td,th,td') as $node) {
                $node->removeAttribute('style');
            }
            return $crawler->html();
        } else {
            return $texte;
        }
    }
    public static function convertimgtoclickable($texte)
    {
        $crawler = new Crawler($texte);
        if ($crawler->count() > 0) {
            foreach ($crawler->filter('img') as $node) {
                if ($node->parentNode->nodeName != 'a') {
                    $src = $node->getAttribute('src');
                    $node->setAttribute('data-controller', 'base--bigpicture');
                    $node->setAttribute('data-base--bigpicture-options-value', '{"imgSrc": "' . $src . '"}');
                }
            }
            return $crawler->html();
        } else {
            return $texte;
        }
    }

    /**
     * Calcule le temps approximatif de lecture d'une page HTML.
     *
     * @param string $html Le contenu HTML de la page.
     * @param int $wordsPerMinute Le nombre moyen de mots lus par minute.
     * @return string Le temps approximatif de lecture en minutes.
     */
    public static function tempsDeLecture(?string $html, int $wordsPerMinute = 200): int
    {
        if (empty($html)) {
            return 0;
        }
        // Créer une instance du Crawler pour analyser le HTML
        $crawler = new Crawler($html);
        if ($crawler->count() == 0) {
            return 0;
        }
        // Extraire le texte du contenu HTML
        $text = $crawler->filter('body')->text();

        // Compter le nombre de mots dans le texte
        $wordCount = str_word_count(strip_tags($text));

        // Calculer le temps approximatif de lecture en minutes
        return ceil($wordCount / $wordsPerMinute);
    }
    public static function getrendu($objetSelect)
    {

        $Texte = $objetSelect->getContenu();
        if (strpos($Texte, 'øtitreø') !== false) {
            $isTemplate = true;
        }
        //correction des ids pour les titres
        $markupFixer = new MarkupFixer();
        if ($Texte) {
            $Texte = $markupFixer->fix($Texte);
        }
        // ajout d'un sommaire au début du texte si coché dans les options d'article
        // injection d'un sommaire à la place de øsommaireø dans le texte
        $sommaire = '<p class="fs-2">Sommaire</p>' . ArticleHelper::getSommaire($Texte, 1, 2) . '<hr>';
        //si on a la cahe sommaire coché et si elle existe dans l'objet

        if (\method_exists($objetSelect, 'getSommaire')) {
            if ($objetSelect->getSommaire()) {
                $Texte = $sommaire . $Texte;
            }
            $Texte = \str_replace('øsommaireø', $sommaire, $Texte);
        }
        $Texte = \str_replace('&nbsp;', ' ', $Texte);
        //si øsommaire est dans le texte
        $Texte = \str_replace('øsommaireø', $sommaire, $Texte);
        $Texte = ArticleHelper::addLinkVideos($Texte);
        $Texte = \str_replace('øtitreø', $objetSelect->getTitre(), $Texte);
        $Texte = ArticleHelper::rmTableStyle($Texte);
        $Texte = ArticleHelper::addTableClass($Texte);
        $Texte = ArticleHelper::removeRoot($Texte);
        $Texte = ArticleHelper::datasrcToSrc($Texte);
        $Texte = ArticleHelper::convertimgtoclickable($Texte);



        // /* -------------------------- ajout du selection (1 par page) -------------------------- */
        // $start = 0;
        // foreach (StringHelper::extractAll($Texte, 0, 'selection(', ')¤') as $id) {
        //     $start = strpos($Texte, '¤)', $start) + 2;
        //     if ($start == false) {
        //         $start = strlen($Texte);
        //     }
        //     $graphid = '¤selection(' . $id . ')¤';
        //     if ($selectionRepository->find(intval($id))) {
        //         $Texte = str_replace($graphid, $this->renderSelection($selectionRepository->find(intval($id)), $request), $Texte);
        //     } else {
        //         $Texte = str_replace($graphid, '', $Texte);
        //         $this->logger->error("Selection avec le numéro $id dans l'article avec l'id:" . $objetSelect->getId() . "n'existe pas", [ucfirst($objet) => $Texte,]);
        //     }
        // }
        return $Texte;
    }
}
