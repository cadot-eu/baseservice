<?php

namespace App\Service\base;

use TOC\MarkupFixer;
use TOC\TocGenerator;
use Symfony\Component\DomCrawler\Crawler;


class ArticleHelper
{
    static function addSommaire($article, $top = 1, $deep = 2)
    {

        $markupFixer  = new MarkupFixer();
        $tocGenerator = new TocGenerator();
        // This ensures that all header tags have `id` attributes so they can be used as anchor links
        $article  = $markupFixer->fix($article);
        // This generates the Table of Contents in HTML
        return "<div class='toc'>" . $tocGenerator->getHtmlMenu($article, $top, $deep) . "</div>" . $article;
    }

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
}
