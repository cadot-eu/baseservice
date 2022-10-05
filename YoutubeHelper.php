<?php

namespace App\Service\base;



class YoutubeHelper
{
  /* A function that takes a url of youtube and returns a embed youtube url. */
  static function adaptUrl($url)
  {
    $html = <<<'EOT'
        <div class="ratio ratio-16x9">
        <iframe src="$url" title="YouTube video" allowfullscreen></iframe>
      </div>
      EOT;
    return str_replace('$url', str_replace(['/watch?v=', 'youtu.be/'], ['/embed/', 'youtube.com/embed/'], $url), $html);
    //https://www.youtube.com/embed/...
  }
}
