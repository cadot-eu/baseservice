<?php

namespace App\Service\base;

use App\Repository\GlossaireRepository;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use TOC\MarkupFixer;
use TOC\TocGenerator;
use Symfony\Component\DomCrawler\Crawler;

class ArticleHelper
{

	/**
	 * It takes an article, adds an ID to all the headers, and then generates a table of contents
	 * 
	 * @param article The article to add the Table of Contents to.
	 * @param top The top level of the table of contents.
	 * @param deep The number of levels of headings to include in the Table of Contents.
	 * 
	 * @return The article with the table of contents added to the top of the article.
	 */
	public static function addSommaire($article, $top = 1, $deep = 2)
	{
		if ($article == null) return $article;
		$markupFixer = new MarkupFixer();
		$tocGenerator = new TocGenerator();
		// This ensures that all header tags have `id` attributes so they can be used as anchor links
		$article = $markupFixer->fix($article);
		// This generates the Table of Contents in HTML
		return "<div class='toc'>" . $tocGenerator->getHtmlMenu($article, $top, $deep) . '</div>' . $article;
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

	// public static function addFilterLiip($texte, CacheManager $imagineCacheManager)
	// {
	// 	//on ajoute un filtre en fonction du champ liip
	// 	$crawler = new Crawler($texte);
	// 	foreach ($crawler->filter('img') as $node) {
	// 		//@var node $node
	// 		dd($node);
	// 		// $child[0]->setAttribute('style', $node->getAttribute('liip') . ' ' . $child[0]->getAttribute('style'));
	// 		// $child[0]->setAttribute('class', $node->getAttribute('class') . ' ' . $child[0]->getAttribute('class'));
	// 		// $node->removeAttribute('style');
	// 		// $node->removeAttribute('class');
	// 	}
	// 	//on vérifie aussi les img
	// 	// foreach ($crawler->filter('img') as $node) {
	// 	// 	$src = $node->getAttribute('src');
	// 	// 	foreach (explode(' ', $node->getAttribute('style')) as $style) {
	// 	// 		if (strpos($style, 'width') !== false) {
	// 	// 			$width = explode(':', $style)[1];
	// 	// 			$liip = str_replace(
	// 	// 				[
	// 	// 					';',
	// 	// 					'%',
	// 	// 					'0',
	// 	// 					'1',
	// 	// 					'2',
	// 	// 					'3',
	// 	// 					'4',
	// 	// 					'5',
	// 	// 					'6',
	// 	// 					'7',
	// 	// 					'8',
	// 	// 					'9',
	// 	// 					'.',
	// 	// 				],
	// 	// 				'',
	// 	// 				$width
	// 	// 			);
	// 	// 			$img = 'uploads/' . explode('uploads/', $src)[1];
	// 	// 			if ($liip) {
	// 	// 				dump('width:' . $width);
	// 	// 				$node->setAttribute(
	// 	// 					'style',
	// 	// 					str_replace(
	// 	// 						'width:' . $width,
	// 	// 						'',
	// 	// 						$node->getAttribute('style')
	// 	// 					)
	// 	// 				);
	// 	// 				$resolvedPath = $imagineCacheManager->getBrowserPath(
	// 	// 					$img,
	// 	// 					$liip
	// 	// 				);
	// 	// 				$node->setAttribute('src', $resolvedPath);
	// 	// 				//dump($resolvedPath);
	// 	// 			}
	// 	// 			//dump($resolvedPath);
	// 	// 		}
	// 	// 	}
	// 	// }
	// 	return $crawler->html();
	// }
}
