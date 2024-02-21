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
    static public function moveElement($array, $a, $b)
    {
        $out = array_splice($array, $a, 1);
        array_splice($array, $b, 0, $out);
        return $array;
    }

    static public function moveElementInObjet($array, $a, $b, $champ = 'ordre')
    {
        $get = 'get' . \ucfirst($champ);
        $set = 'set' . \ucfirst($champ);

        // Trouver l'objet à déplacer
        $objetA = null;
        $indexA = null;
        foreach ($array as $index => $objet) {
            if ($objet->$get() == $a) {
                $objetA = $objet;
                $indexA = $index;
                break;
            }
        }

        if ($objetA) {
            // Mettre à jour l'ordre de l'objet déplacé
            $objetA->$set($b);

            // Mettre à jour l'ordre des autres objets
            foreach ($array as $index => $objet) {
                if ($index != $indexA) {
                    $currentOrder = $objet->$get();
                    if ($a < $b) {
                        // Si l'objet a été déplacé vers le bas, décrémenter l'ordre des objets situés entre l'ancienne et la nouvelle position
                        if ($currentOrder > $a && $currentOrder <= $b) {
                            $objet->$set($currentOrder - 1);
                        }
                    } else {
                        // Si l'objet a été déplacé vers le haut, incrémenter l'ordre des objets situés entre l'ancienne et la nouvelle position
                        if ($currentOrder < $a && $currentOrder >= $b) {
                            $objet->$set($currentOrder + 1);
                        }
                    }
                }
            }
        }

        return ($array); // Vérifier si les ordres ont été mis à jour correctement
    }
}
