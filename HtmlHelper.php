<?php

namespace App\Service\base;

class HtmlHelper
{
    /**
     * It takes a string of HTML and an array of HTML tags as arguments, and returns the string with all
     * of the tags in the array removed
     * 
     * @param html_string The string that contains the HTML tags.
     * @param html_tags An array of HTML tags to be removed.
     * 
     * @return the string with the html tags removed.
     */
    static  function remove_html_tags($html_string, $html_tags)
    {
        $tagStr = "";

        foreach ($html_tags as $key => $value) {
            $tagStr .= $key == count($html_tags) - 1 ? $value : "{$value}|";
        }

        $pat_str = array("/(<\s*\b({$tagStr})\b[^>]*>)/i", "/(<\/\s*\b({$tagStr})\b\s*>)/i");
        $result = preg_replace($pat_str, "", $html_string);
        return $result;
    }
}
