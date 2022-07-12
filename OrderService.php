<?php

namespace App\Service\base;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\Definition\Exception\Exception;

class OrderService
{
    static public function reorder(String $entity, int $id, String $action, EntityManagerInterface $em)
    {
        $faqs = $em->getRepository('App\\Entity\\' . ucwords($entity))->findBy([], ['ordre' => 'ASC']);
        foreach ($faqs as $num => $faq) {
            if ($faq->getId() == $id) {
                $pos = $num;
            }
        }
        switch ($action) {
            case 'up':
                $dest = $pos - 1;
                break;
            case 'down':
                $dest = $pos + 1;
                break;
            case 'top':
                $dest = 0;
                break;
            case 'bottom':
                $dest = count($faqs) - 1;
                break;
            default:
                throw new Exception('Mouvement inconnu, up, top, down, bottom');
                break;
        }
        foreach (ArrayHelper::moveElement($faqs, $pos, $dest) as $num => $faq) {
            $faq->setOrdre($num);
            $em->persist($faq);
        }
        $em->flush();
    }
}
