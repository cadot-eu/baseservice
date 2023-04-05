<?php

namespace App\Service\base;

use Faker\Factory;
use WW\Faker\Provider\Picture;
use App\Service\base\ToolsHelper;

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
    static public function generate(string $champ, $lang = 'fr_FR')
    {
        /* ------------------- pour utiliser les images de picsum ------------------- */
        $faker = Factory::create($lang);
        $faker->addProvider(new Picture($faker));

        /* ------------------------ pour utiliser les icones ------------------------ */
        $icones = json_decode(file_get_contents('/app/src/Twig/base/gists/list.json'));
        switch ($champ) {
            case 'image':
                return substr($faker->picture('public/uploads/fixtures/', 640, 480), strlen('public/'));
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
            default:
                return null;
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
    static public function add_valeurs($temppage, $template, $nombre_a_creer = [], $valeurs = [])
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
    static public function createpage(string $nompage, array $templates, array $maxi = [6], bool $article = false, Temppage $parent = null)
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
