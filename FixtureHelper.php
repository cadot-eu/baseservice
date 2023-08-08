<?php

namespace App\Service\base;

use Faker\Factory;
use WW\Faker\Provider\Picture;
use App\Service\base\ToolsHelper;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Console\Descriptor\Descriptor;
use App\Service\base\FixtureHelper;
use LanguageDetector\LanguageDetector;
use Faker\Provider\Youtube;
use App\Service\base\FileHelper;
use Symfony\Component\Panther\DomCrawler\Crawler as DomCrawlerCrawler;
use Goutte\Client;

class FixtureHelper
{
    /**
     * This PHP function generates random data based on the input field type.
     *
     * @param string image,youtube,phrase,float,icon,texte,texte_mark
     * @param lang The language in which the generated data should be returned. It has a default value
     * of 'fr_FR' if not specified.
     *
     * @return different values depending on the value of the `` parameter. If `` is
     * 'image', it returns a URL to a randomly generated image. If it is 'youtube', it returns a URL to
     * a YouTube video. If it is 'phrase', it returns a random text string. If it is 'float', it
     * returns a random float number. If it is
     */
    public static function generate(string $champ, $lang = 'fr_FR', $options = [])
    {
        /* ------------------- pour utiliser les images de picsum ------------------- */
        $faker = Factory::create($lang);
        $faker->addProvider(new Picture($faker));

        /* ------------------------ pour utiliser les icones ------------------------ */
        $icones = json_decode(file_get_contents('/app/src/Twig/base/gists/list.json'));
        switch ($champ) {
            case 'image':
                @mkdir('/app/public/uploads/fixtures', 0777, true);
                return substr($faker->picture('/app/public/uploads/fixtures/', 640, 480), strlen('/app/public/'));
                break;
            case 'video':
                @mkdir('/app/public/uploads/fixtures', 0777, true);
                //on télécharge la vidéo de l'url de https://sample-videos.com/ 
                // vidéos de 1 Mo en plusieurs formats
                $videos = ['https://sample-videos.com/video123/mp4/720/big_buck_bunny_720p_1mb.mp4', 'https://sample-videos.com/video123/mkv/720/big_buck_bunny_720p_1mb.mkv', 'https://sample-videos.com/video123/3gp/240/big_buck_bunny_240p_1mb.3gp'];
                $video = $videos[rand(0, count($videos) - 1)];
                file_put_contents($name = '/app/public/uploads/fixtures/video' . \uniqid() . '.' . FileHelper::extension($video), file_get_contents($video));
                return substr($name, strlen('/app/public/'));
                break;

            case 'youtube':
                return $faker->randomElement(['https://www.youtube.com/embed/zpOULjyy-n8?rel=0']);
                break;
            case 'phrase':
                return $faker->text(10);
                break;
            case 'float':
                return $faker->randomFloat(2);
                break;
            case 'texte':
                return $faker->text();
                break;
            case 'texte_mark':
                return $faker->text(20) . '<mark>' . $faker->colorName() . '</mark>' . $faker->text(20);
                break;
            case 'icone':
                return 'bi-' . $faker->randomElement($icones);
                break;
            case 'article':
                return ToolsHelper::wikipedia_article_random();
                break;
            case 'adresse':
                if (isset($options['q'])) {
                    $q = urlencode($options['q']);
                } else {
                    $q = "rue";
                }
                //on prend une adresse au hasard dans la liste des adresses par curl
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, "https://api-adresse.data.gouv.fr/search/?q=$q&limit=50");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($curl);
                curl_close($curl);
                $result = json_decode($result);
                return $result->features[rand(0, 49)];
                break;
            case 'immobilier':
                //srapping pour récupérer une annonce immobilière au hasard
                return FixtureHelper::generateImmobilier();
                break;
            default:
                return null;
                break;
        }
    }
    public static function generateImmobilier()
    {
        $client = new Client();
        $crawler = $client->request('GET', 'https://www.immoweb.be/fr/recherche/maison/a-vendre');
        $annonces = $crawler->filter('.card--result__title');
        $annonce = $annonces->eq(rand(0, $annonces->count() - 1));
        $annonce->filter('a')->attr('href');
        $crawler = $client->request('GET', $annonce->filter('a')->attr('href'));
        $description = $crawler->filter('.classified__description')->text();
        $detector = new LanguageDetector();
        $language = $detector->evaluate($description)->getLanguage();

        if ($language != 'fr') {
            return FixtureHelper::generateImmobilier();
        } else {
            return $description;
        }
    }
    static function exEtudiantsInTown($city)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://nominatim.cadot.eu//search?q=" . urlencode($city) . "&format=json&limit=1");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        dd($response);
        $data = json_decode($response, true);
        dd($data);
        if (!empty($data)) {
            $lat = $data[0]['lat'];
            $lon = $data[0]['lon'];
            $url = "https://overpass-api.de/api/interpreter?data=[out:json];node(around:10000,$lat,$lon)[amenity=university];out;";
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if (!empty($data['elements'])) {
                return true;
            } else {
                return false;
            }
        } else {
            return "no data";
        }
    }

    static function EtudiantsInTown($town)
    {
        $url = "https://nominatim.cadot.eu/search?q=" . urlencode($town) . "&format=json&limit=1";
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (!empty($data)) {
            $lat = $data[0]['lat'];
            $lon = $data[0]['lon'];

            // $reverse_url =  "https://overpass-api.de/api/interpreter?data=[out:json];node(around:10000,$lat,$lon)[amenity=university];out;";
            // $reverse_response = file_get_contents($reverse_url);
            // $reverse_data = json_decode($reverse_response, true);

            if (!empty($reverse_data['address'])) {
                dd($reverse_data['address']);
                if (strpos(strtolower($reverse_data['address']), 'university') !== false) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return "no data";
            }
        } else {
            return "no data";
        }
    }

    static function fromCrud($entity, $field)
    {
        $doc = new ParserDocblock($entity);
        $options = $doc->getOptions()[$field];
        switch ($doc->getSelect($field)[0]) { //on prend que le premier alias
            case 'choice':
                $array = $options['options'];
                if (isset($options['opt']) && isset($options['opt']['multiple']) && $options['opt']['multiple'] == true) {
                    $size = \count($array) > 5 ? 5 : \count($array);
                    $resarray = [];
                    for ($i = 0; $i < \rand(0, $size); $i++) {
                        $resarray[] = $array[\rand(0, \count($array) - 1)];
                    }
                    return $resarray;
                } else {
                    return $array[rand(0, count($array) - 1)];
                }
                break;
        }
    }



    /**
     * The function adds a number of values to a page based on the number of values in the template
     *
     * @param temppage The page object that you want to add the values to.
     * @param template The template object
     * @param nombre_a_creer An array of the number of blocks to create for each template.
     * @param valeurs An array of values to be inserted.
     */
    public static function add_valeurs($temppage, $template, $nombre_a_creer = [], $valeurs = [])
    {
        $numvaleurs = 0;
        /* ------------------- pour utiliser les images de picsum ------------------- */
        $faker = Factory::create();
        $faker->addProvider(new Picture($faker));

        /* ---------------------------- ajout de valeurs dans la page---------------- */
        foreach ($temppage->getTemplatenbrs() as $tempnbr) { //on boucle sur les templates de la page
            if ($tempnbr->getTemplate()->getId() == $template->getId()) { // si le template.id est celui du template rechercher
                $maxi = isset($nombre_a_creer[$template->getNom()]) ? $nombre_a_creer[$template->getNom()] : $tempnbr->getMaxi();
                for ($i = 0; $i < $maxi; $i++) { //on cré le maximum de blocks
                    foreach ($template->getChamps() as $champ) { //on liste les champs
                        $data = isset($valeurs[$numvaleurs]) ? $valeurs[$numvaleurs] : self::generate($champ); //on génère un faker
                        TempvaleurFactory::createOne([
                            //on cré la valeur
                            'valeur' => $data,
                            'idpage' => $temppage->getId(),
                            'idtemplate' => $template->getId(),
                            'slugpage' => $temppage->getSlug(),
                            'slugtemplate' => $template->getNom(),
                            'labelchamp' => $champ->getLabel(),
                            'idchamp' => $champ->getId(),
                            'ordre' => $i + 1,
                            'createdAt' => $faker->dateTimeThisCentury(),
                        ]);
                        $numvaleurs++;
                    }
                }
            }
        }
    }

    /**
     * It creates a page with a given name and a given number of templates.
     *
     * @param string nompage The name of the page.
     * @param array templates an array of templates
     * @param array maxi The maximum number of articles that can be displayed on a page.
     * @param bool article boolean, if true, the page will be an article page, if false, it will be a
     * normal page.
     * @param Temppage parent The parent page.
     *
     * @return The factory returns an instance of the class it was called on.
     */
    public static function createpage(string $nompage, array $templates, array $maxi = [6], bool $article = false, Temppage $parent = null)
    {
        /* ------------------- pour utiliser les images de picsum ------------------- */
        $faker = Factory::create();
        $faker->addProvider(new Picture($faker));

        $templatenbr = [];
        foreach ($templates as $num => $template) {
            /** @var TempnbrtemplateFactory */
            $templatenbr[] = TempnbrtemplateFactory::createone(
                [
                    'setTemplate' => $template,
                    'maxi' => $maxi[$num],
                ]
            );
        }
        return TemppageFactory::createOne(
            [
                'langue' => 'fr',
                'nom' => $nompage,
                'etat' => 'en ligne',
                'Articles' => $article,
                'addTemplatenbrs' => $templatenbr,
                'createdAt' => $faker->dateTimeThisCentury(),
                'parent' => $parent,
            ]
        );
    }
}
