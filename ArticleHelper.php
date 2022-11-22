<?php

namespace App\Service\base;

use App\Repository\GlossaireRepository;
use TOC\MarkupFixer;
use TOC\TocGenerator;
use Symfony\Component\DomCrawler\Crawler;


class ArticleHelper
{
    /* Adding a table of contents to the article. */
    static function addSommaire($article, $top = 1, $deep = 2)
    {

        $markupFixer  = new MarkupFixer();
        $tocGenerator = new TocGenerator();
        // This ensures that all header tags have `id` attributes so they can be used as anchor links
        $article  = $markupFixer->fix($article);
        // This generates the Table of Contents in HTML
        return "<div class='toc'>" . $tocGenerator->getHtmlMenu($article, $top, $deep) . "</div>" . $article;
    }

    /* Replacing the oembed tag with the youtube video. */
    static function addLinkVideos($article)
    {
        $crawler = new Crawler($article);
        $videos = $crawler->filter('oembed');
        foreach ($videos as $video) {
            $video = new Crawler($video);
            $url = $video->attr('url');
            $article = str_replace($video->outerHtml(), YoutubeHelper::adaptUrl($url), $article);
        }
        return $article;
    }

    /* Replacing the word "glossaire" with a span tag. */
    static function addLinkGlossaire($article, GlossaireRepository $glossaireRepository)
    {
        $article = str_replace('&nbsp;', '', $article);
        $mots = $glossaireRepository->findBy(['deletedAt' => null, 'etat' => 'en ligne']);
        $crawler = new Crawler($article);
        $ps = $crawler->filter('p');
        foreach ($ps as $p) {
            $p = new Crawler($p);
            $text = $p->html();
            foreach ($mots as $mot) {
                $terme = $mot->getTerme();
                $title = $mot->getDefinition();
                $text = preg_replace('/\b' . preg_quote($terme, "/") . '\b/i', "<span  class=\"glossaire\" glossaire=\"$title\">\$0</span>", $text, 1);
            }
            $article = str_replace($p->outerHtml(), $text, $article);
        }
        return $article;
    }
}
