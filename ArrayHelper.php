<?php

namespace App\Service\base;


class ArrayHelper
{
    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param  array  $array
     * @return bool
     */
    static public function isAssoc(array $array)
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }
    /**
     * move a element in other position 
     * 
     * @param array The array you want to move an element in.
     * @param a The index of the element to move.
     * @param b The index of the element to move.
     */
    static public function moveElement($array, $a, $b, $champ = 'ordre')
    {
        //on teste si l'element est un objet
        if (is_object($array[$a])) {
            return self::moveElementInObjet($array, $a, $b, $champ);
        }
        $out = array_splice($array, $a, 1);
        array_splice($array, $b, 0, $out);
        return $array;
    }

    static public function moveElementInObjet($array, $dep, $pos, $champ = 'ordre')
    {
        $id = $array[$dep]->getId();
        $get = 'get' . \ucfirst($champ);
        $set = 'set' . \ucfirst($champ);

        // Range le tableau par $champ
        usort($array, function ($a, $b) use ($get) {
            return $a->$get() <=> $b->$get();
        });

        // Modifie la position de l'élément sélectionné
        foreach ($array as $key => $value) {
            if ($value->getId() == $id) {
                array_splice($array, $key, 1); // Retire l'élément du tableau
                array_splice($array, $pos, 0, [$value]); // Insère l'élément à la nouvelle position
                break;
            }
        }

        // Met à jour les positions après le déplacement
        foreach ($array as $key => $value) {
            $value->$set($key);
        }

        return $array; // Retourne le tableau mis à jour
    }
}
