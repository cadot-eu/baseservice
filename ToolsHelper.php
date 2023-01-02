<?php

namespace App\Service\base;

use App\Twig\base\AllExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;
use App\Entity\Parametres;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use ReflectionClass;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Common\Collections\Criteria;

class ToolsHelper
{
    /**
     * Get the list of enabled locales from the translation.yaml file
     *
     * @return An array of enabled locales.
     */
    static public function get_langs()
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
    static public function wikipedia_article_random()
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
    static public function params(EntityManagerInterface $em)
    {
        $tab = [];
        foreach ($em->getRepository(Parametres::class)->findAll() as $parametre) {
            $tab[AllExtension::ckclean($parametre->getSlug())] = AllExtension::ckclean($parametre->getValeur());
        }
        return $tab;
    }
    static public function get_git_log()
    {
        $process = new Process(['git', 'log', '--pretty=format:"%h":{  "subject": "%s",%n  "date": "%aD"%n },', '--no-merges', '--reverse', '--no-color', '--no-patch', '--abbrev-commit', '--abbrev=7', '--date=short', '--decorate=full', '--all', '--']);
        $process->run();
        //--pretty=format:'"%h":{  "subject": "%s",%n  "date": "%aD"%n },'
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            return '{}';
            //throw new ProcessFailedException($process);
        }
        return '{' . substr($process->getOutput(), 0, -1) . '}'; //on retire la dernière virgule
    }
    static function get_git_log_array($number)
    {
        $ancien = "";
        foreach (array_reverse(json_decode(ToolsHelper::get_git_log(), true)) as $log) {
            if ($log['subject'] != $ancien) {
                $logs[] = $log;
                $ancien = $log['subject'];
            }
            if (count($logs) == $number) {
                return $logs;
            }
        }
    }

    static public function knpChampsRecherche($entity)
    {
        $objetEntity = 'App\Entity\\' . ucfirst($entity);
        $reflexion = new ReflectionClass(new $objetEntity);
        if ($reflexion->hasProperty('ordre')) {
            $ordre = 'a.ordre';
        } else {
            $ordre = 'a.vues';
        }
        $champs = [];
        foreach ($reflexion->getProperties() as $propertie) {
            if (
                in_array($propertie->getName(), ['titre', 'description', 'texte', 'article', 'name', 'reponse', 'nom', 'explication',])
            ) {
                $champs[] = $propertie->getName();
            }
        }
        return implode(',', $champs);
    }
    static public function setSlug(EntityManagerInterface $em,  $entity)
    {
        //$this->logActivity('persist', $args);
        //$class = get_class($args->getObject());
        //$class = 'App\Entity\\' . \ucfirst($entitystr);
        $reflexion = new \ReflectionClass(get_class($entity));
        //on vérifie si un slug est donné dans id
        $propertieSlug = $reflexion->getProperty('id');
        foreach (explode("\n", $propertieSlug->getDocComment()) as $doc) {
            $comment = trim(substr(trim($doc), 1));
            if (explode(':', $comment)[0] == 'slug') {
                $slug = trim(explode(':', $comment)[1]);
                break;
            }
        }
        //si on a pas de slug définis dans id
        if (!isset($slug)) {
            foreach ($reflexion->getProperties() as $prop) {
                $propertiesName[] = $prop->getName();
            }
            foreach (['titre',  'title', 'nom', 'name', 'label', 'id'] as $motParImportance) {
                if (
                    in_array($motParImportance, $propertiesName)
                ) {
                    $slug = $motParImportance;
                    break;
                }
            }
        }
        //on récupère la taille maxi du slug
        $longueur = $reflexion->getProperty('slug')->getAttributes()[0]->getArguments()['length'];
        //génération du slug
        $slugger = new AsciiSlugger();
        $method = 'get' . ucfirst($slug);
        $lugGenerated = $slugger->slug(\strip_tags($entity->$method()))->lower();
        //si le slug est trop long
        if (strlen($lugGenerated) > $longueur - 5) { //5 est nombres de chiffres ajoutés au slug maxi
            $lugGenerated = substr($lugGenerated, 0, strrpos(substr($lugGenerated, 0, $longueur), '-'));
        }
        //on récupère le dernier slug avec le même préfixe
        $repo = $em->getRepository(get_class($entity));
        $entityRepository = $repo->createQueryBuilder('e');
        $entityRepository->where('e.slug LIKE :slug');
        $entityRepository->setParameter('slug', $lugGenerated . '%');
        $entityRepository->orderBy('e.slug', Criteria::DESC);
        $res = $entityRepository->getQuery()->setMaxResults(1)->getOneOrNullResult();
        //si on a un slug avec ce préfixe
        if ($res and $res->getId() != $entity->getId()) {
            $inc = (int)array_reverse(explode('-', $res->getSlug()))[0] + 1;
            //on set le slug    
            if (strlen($lugGenerated . '-' . $inc) > $longueur) {
                throw new \Exception('Le slug généré est trop long');
            } else {
                $entity->setSlug($lugGenerated . '-' . $inc);
            }
        } else {
            $entity->setSlug($lugGenerated);
        }
        return $entity;
    }
}
