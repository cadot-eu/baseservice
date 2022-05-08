<?php

namespace App\Service\base;

use Faker\Factory;
use WW\Faker\Provider\Picture;

class FixtureHelper
{
    /**
     * A function that generates a value for a field.
     *
     *  @param champ float,texte,image,phrase,texte,texte_mark,icone et choix
     *  appelé directement par champ ['type'=>'texte']
     */
    public static function generate(mixed $champ)
    {
        $options = null;
        $label = '';
        $type = '';
        //if array send
        if (is_array($champ)) {
            foreach ($champ as $key => $value) {
                $$key = $value;
            }
        } else {
            $options = $champ->getOptions();
            $label = $champ->getLabel();
            $type = $champ->getType();
        }
        /* ------------------- pour utiliser les images de picsum ------------------- */
        $faker = Factory::create();
        $faker->addProvider(new Picture($faker));

        /* ------------------------ pour utiliser les icones ------------------------ */
        $icones = json_decode(file_get_contents('/app/src/Twig/base/gists/list.json'));
        foreach (explode(';', $options) as $option) {
            $exp = explode(':', $option);
            $exp[1] = isset($exp[1]) ? $exp[1] : '';
            $champoptions[$exp[0]] = $exp[1];
        }
        switch ($type) {
            case 'image':
                return substr($faker->picture('public/uploads/fixtures/', 640, 480), strlen('public/'));
                break;
            case 'phrase':
                //on regarde si le nom du label est spécial youtube...
                switch (true) {
                    case stristr($label, 'youtube'):
                        return $faker->randomElement(['https://www.youtube.com/embed/zpOULjyy-n8?rel=0']);
                        break;
                    default:
                        return $faker->text(10);
                        break;
                }
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
            case 'choix':
                foreach (explode(',', $champoptions['choix']) as $oc) {
                    $exp = explode('=', $oc);
                    $exp[1] = isset($exp[1]) ? $exp[1] : '';
                    $ocs[$exp[0]] = $exp[1];
                }
                return $faker->randomElement($ocs);
                break;
            default:
                return 'erreur sur le type';
                break;
        }
    }

    public static function gen_by_name(string $champ)
    {

        /* ------------------- pour utiliser les images de picsum ------------------- */
        $faker = Factory::create();
        $faker->addProvider(new Picture($faker));
        $fakeren = Factory::create('en_GB');

        /* ------------------------ pour utiliser les icones ------------------------ */
        $icones = json_decode(file_get_contents('./assets/gists_bootstrap_icons/list.json'));
        if (strpos($champ, '_')) {
            switch (explode('_', $champ)[1]) {
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
                default:
                    return null;
                    break;
            }
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
