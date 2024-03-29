<?php

namespace App\Service\base;

use Symfony\Component\Config\Definition\Exception\Exception;
use ReflectionClass;

class ParserDocblock
{
    private
        $alias,
        $Entity,
        $reflexion,
        $selects,
        $options,
        $types,
        $properties,
        $baseAlias;


    public function __construct(string $entity = '', array $baseAlias = ['integer', 'adresse', 'simple', 'simplelanguage', 'vide', 'normal', 'full', 'annonce', 'choice', 'choiceenplace', 'onechoiceenplace', 'entity', 'collection', 'color', 'email', 'password', 'hidden', 'hiddenroot', 'invisible', 'readonlyroot', 'image', 'video', 'fichier', 'money', 'telephone', 'siret', 'iban', 'bic', 'array', 'json', 'order', 'search', 'select', 'string', 'drapeau', 'pass', 'importance', 'stars', 'nombre', 'drag', 'tpl', 'andwhere', 'dql', 'predql'])
    {
        $this->setEntity($entity);
        $this->baseAlias = $baseAlias;
        foreach ($this->reflexion->getProperties() as $property) {
            $name = $this->getName($property);
            $this->options[$name] = $this->parseOptions($property);
            $this->alias[$name] = $alias = $this->getAlias($property);
            $this->types[$name] = $type = $this->findType($property);
            //on prend le tableau d'alias sinon le type

            $this->selects[$name] = count($alias) > 0 ? $alias : [$type];
            $this->properties[$name] = $property;
        }
    }

    /**
     * It sets the entity and the reflection of the class.
     *
     * @param string The name of the class that is being called.
     */
    public function setEntity($string): void
    {
        $this->Entity = ucfirst($string);
        $class = 'App\Entity\\' . $this->Entity;
        $this->reflexion = new \ReflectionClass($class);
    }

    /**
     *  Returns the reflection of the class
     *
     * @return \ReflectionClass The reflection class of the class that is being called.
     */
    public function getReflexion(): \ReflectionClass
    {
        return $this->reflexion;
    }
    public function getAttributes(string $name): array
    {
        return $this->reflexion->getProperty($name)->getAttributes();
    }

    public function getAttribute(string $name, $nameAttribute): \ReflectionAttribute
    {
        foreach ($this->getAttributes($name) as $num => $attribute) {
            if (is_string($nameAttribute) && $attribute->getName() == $nameAttribute) {
                return $attribute;
            }
            if (is_integer($nameAttribute) && $num == $nameAttribute) {
                return $attribute;
            }
        };
    }
    public function getArgumentsOfAttributes(string $name, $nameAttribute): array
    {
        return $this->getAttribute($name, $nameAttribute)->getArguments();
    }
    public function getArgumentOfAttributes(string $name, $nameAttribute, $nameArgument)
    {
        if (!isset($this->getAttribute($name, $nameAttribute)->getArguments()[$nameArgument])) {
            return null;
        }
        return $this->getAttribute($name, $nameAttribute)->getArguments()[$nameArgument];
    }
    public function getProperty($name): \ReflectionProperty
    {
        return $this->properties[$name];
    }
    public function getSelect($name): array
    {
        return $this->selects[$name];
    }
    public function getOptions($field = null, $name = null): ?array
    {
        if ($field) {
            if ($name) {
                if (isset($this->options[$field][$name])) {
                    return $this->options[$field][$name];
                } else {
                    return null;
                }
            } elseif (isset($this->options[$field])) {
                return $this->options[$field];
            }
            return null;
        } else {
            return $this->options;
        }
    }
    public function getType(string $name): string
    {
        return $this->types[$name];
    }
    public function getAllAlias(): array
    {
        return $this->alias;
    }

    /**
     * It takes a property and returns an array of options
     *
     * @param \ReflectionProperty property The property to be processed
     */
    private function parseOptions(\ReflectionProperty $property)
    {
        $options = [];
        $docs = $property->getDocComment();
        $tab = explode("\n", $docs);
        foreach (explode("\n", $docs) as $doc) {
            //si on a une valeur
            if ($this->clean($doc) != '') {
                //We look if we have an action and value
                if (($deb = strpos($doc, ':')) === false) {
                    $options[strtolower($this->clean(substr($doc, 0, $deb)))] = $this->clean($doc);
                } else {
                    $key = strtolower($this->clean(substr($doc, 0, $deb)));
                    //merge or create
                    if (isset($options[$key])) {
                        //control presence of key and value
                        if (is_array(json_decode(substr($doc, $deb + 1), true))) {
                            $val = $this->jsondecode(substr($doc, $deb + 1), true);
                        } else {
                            $val = $this->jsondecode('{"' . substr($doc, $deb + 1) . '":""}', true);
                        }
                        $options[$key] =  array_merge($options[$key], $val);
                    } else {
                        //control presence of key and value
                        if (is_array(json_decode(substr($doc, $deb + 1), true))) {
                            $options[$key] = $this->jsondecode(substr($doc, $deb + 1), true);
                        } else {
                            $options[$key] = $this->jsondecode('{"' . substr($doc, $deb + 1) . '":""}', true);
                        }
                    }
                }
            }
        }
        return $options;
    }
    /**
     * It takes a string, decodes it as JSON, and returns the decoded object
     *
     * @param string The string being decoded.
     * @param bool If this is true, then the object will be converted into an associative array.
     *
     * @return the value of the variable .
     */
    public function jsondecode($string, $bool = false)
    {
        $json = json_decode($string, $bool);
        if ($json == null && $string != '') {
            dd('!!erreur de syntaxe sur' . $string);
        }
        return $json;
    }


    /**
     * It returns the type of the property
     *
     * @param \ReflectionProperty property The property to be analyzed
     *
     * @return string The type of the property.
     */
    public function findType(\ReflectionProperty $property): string
    {
        $tab = '';
        foreach ($property->getAttributes() as $attr) {
            $fin = strtolower(array_reverse(explode('\\', $attr->getName()))[0]);
            if ($fin == 'column' && !$tab) {
                $tab = isset($attr) && isset($attr->getArguments()['type']) ? $attr->getArguments()['type'] : '';
            }
            if ($fin != 'column') {
                $tab = strtolower(array_reverse(explode('\\', $attr->getName()))[0]);
            }
        }
        return $tab;
    }

    /**
     * retire les caractères avant qui sont des \n des * des espaces
     *
     * @param string The string to be cleaned.
     *
     * @return string The string with all the whitespace removed.
     */
    public function clean($string): string
    {
        return trim($string, " \t\n\r\0\x0B*\/\\");
    }
    /**
     * This function returns the name of the property
     *
     * @param \ReflectionProperty property The property that is being serialized.
     *
     * @return string The name of the property.
     */
    public function getName(\ReflectionProperty $property): string
    {
        return $property->getName();
    }
    /**
     * It returns the alias of the property
     *
     * @param \ReflectionProperty property The property to be analyzed
     *
     * @return array The alias of the property.
     */
    private function getAlias(\ReflectionProperty $property): array
    {
        $docs = $property->getDocComment();
        $tab = explode("\n", $docs);
        $restab = [];
        if ($property->getName() == 'id') {
            $restab[] = 'id';
        }
        //on boucle sur les $tab 
        //on supprime le tab[0]
        unset($tab[0]);
        foreach ($tab as $num => $item) {
            if (isset($tab[$num]) && strpos($tab[$num], ':') === false && in_array($this->clean($tab[$num]), $this->baseAlias)) {
                $restab[] = $this->clean($tab[$num]);
            } else { //on quite la boucle
                break;
            }
        }
        return $restab;
    }
    /**
     * It gets the select of a property
     *
     * @param \ReflectionProperty property The property to be processed
     *
     * @return string The select of the property.
     */
    public function propertyExist($name)
    {
        foreach ($this->reflexion->getTraits() as $trait) {
            if ($trait->hasProperty($name)) {
                return true;
            }
        }
        return $this->reflexion->hasProperty($name);
    }
}
