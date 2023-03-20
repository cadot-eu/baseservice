<?php

namespace App\Service\base;

use App\Repository\GlossaireRepository;
use Imagine\File\Loader;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use TOC\MarkupFixer;
use TOC\TocGenerator;
use Symfony\Component\DomCrawler\Crawler;

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
		if ($article == null) return $article;
		$tocGenerator = new TocGenerator();
		// This generates the Table of Contents in HTML
		$toc = $tocGenerator->getHtmlMenu($article, $top, $deep);
		if ($toc == null) return null;
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

	public static function imgToSrcset($texte, CacheManager $imagineCacheManager, FilterManager $filtermanager)
	{
		//on ajoute un filtre en fonction du champ liip ou au pire on met moyen
		$crawler = new Crawler($texte);
		$filters = $filtermanager->getFilterConfiguration()->all();
		foreach ($crawler->filter('img') as $node) {
			//@var node $node
			if (strpos('/media/cache', $node->getAttribute('src')) === false) {
				$src = $node->getAttribute('src');
				$node->setAttribute('data-src', $src);
				$node->removeAttribute('src');
				$node->setAttribute('class', 'lazy img-fluid');
				$srcset = [];
				$width = intval(StringHelper::chaine_extract($node->getAttribute('style'), 'width:', 'px')) ?: (file_exists('/uploads/' . explode('/uploads', $src)[1]) ? getimagesize($src)[0] : 0);
				foreach ($filters as $name => $value) {
					//on ne prend que les filtres qui sont plus petit que l'image et qui utilisent la largeur
					if (isset($value['filters']['relative_resize']['widen']) && ($largeur = $value['filters']['relative_resize']['widen']) <= $width) {
						$srcset[] = $imagineCacheManager->getBrowserPath('uploads/' . explode('uploads/', $src)[1], $name) . " $largeur" . "w ";
					}
				}
				$node->removeAttribute('style');
				$node->setAttribute('data-srcset', implode(',', $srcset));
				$max = $node->getAttribute('origin-size') ? explode(',', $node->getAttribute('origin-size'))[0] : $width;
				$node->setAttribute('sizes', "(max-width: " . $max . "px) 100vw, " . $max . "px");
			}
		}
		return $crawler->filter('body')->html();
	}

	public static function exaddFilterLiip($texte, CacheManager $imagineCacheManager, FilterManager $filterLoader)
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
				dump($width);
				//on récupère les filtres de liip
				$filters = $filterLoader->getFilterConfiguration()->all();
				$filtres = [];
				foreach ($filters as $name => $value) {
					if (isset($value['filters']['relative_resize']['widen']))
						$filtres[$name] = $value['filters']['relative_resize']['widen'];
				}
				//on sort les filtres par largeur
				asort($filtres);
				//on prend le filtre le plus proche de la largeur de l'image
				$closest = array_reduce(array_keys($filtres), function ($prev, $curr) use ($filtres, $width) {
					return (abs($filtres[$curr] - $width) < abs($filtres[$prev] - $width)) ? $curr : $prev;
				}, array_keys($filtres)[0]);
				dump($closest);
				//$node->setAttribute('src', $imagineCacheManager->getBrowserPath($url, $closest));
			}

			// $child[0]->setAttribute('style', $node->getAttribute('liip') . ' ' . $child[0]->getAttribute('style'));
			// $child[0]->setAttribute('class', $node->getAttribute('class') . ' ' . $child[0]->getAttribute('class'));
			// $node->removeAttribute('style');
			// $node->removeAttribute('class');
		}
		return ($crawler->filter('body')->html());		//on vérifie aussi les img
		// foreach ($crawler->filter('img') as $node) {
		// 	$src = $node->getAttribute('src');
		// 	foreach (explode(' ', $node->getAttribute('style')) as $style) {
		// 		if (strpos($style, 'width') !== false) {
		// 			$width = explode(':', $style)[1];
		// 			$liip = str_replace(
		// 				[
		// 					';',
		// 					'%',
		// 					'0',
		// 					'1',
		// 					'2',
		// 					'3',
		// 					'4',
		// 					'5',
		// 					'6',
		// 					'7',
		// 					'8',
		// 					'9',
		// 					'.',
		// 				],
		// 				'',
		// 				$width
		// 			);
		// 			$img = 'uploads/' . explode('uploads/', $src)[1];
		// 			if ($liip) {
		// 				dump('width:' . $width);
		// 				$node->setAttribute(
		// 					'style',
		// 					str_replace(
		// 						'width:' . $width,
		// 						'',
		// 						$node->getAttribute('style')
		// 					)
		// 				);
		// 				$resolvedPath = $imagineCacheManager->getBrowserPath(
		// 					$img,
		// 					$liip
		// 				);
		// 				$node->setAttribute('src', $resolvedPath);
		// 				//dump($resolvedPath);
		// 			}
		// 			//dump($resolvedPath);
		// 		}
		// 	}
		// }
		return $crawler->html();
	}
	public static function addTableClass($texte, $class = "table table-striped table-bordered align-middle text-center")
	{
		$crawler = new Crawler($texte);
		foreach ($crawler->filter('table') as $node) {
			$node->setAttribute('class', $class);
		}
		return $crawler->html();
	}
	public static function rmtableStyle($texte)
	{
		$crawler = new Crawler($texte);
		foreach ($crawler->filter('td,th,td') as $node) {
			$node->removeAttribute('style');
		}

		return $crawler->html();
	}
}
