<?php

namespace App\Service\base;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;


class DevisfactureHelper
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }
    function generateInvoicePDF($datas)
    {
        // Créez une instance Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isPhpEnabled', true);
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);
        $html = $html = $this->twig->render(
            '/base/devisfacture/modele_classique.html.twig',
            $datas
        );
        dd($html);

        // Chargez le contenu HTML dans Dompdf
        $dompdf->loadHtml($html);

        // Réglez la taille du papier et les marges (facultatif)
        $dompdf->setPaper('A4', 'portrait');

        // Générez le PDF
        $dompdf->render();

        // Générez la réponse PDF (affichage ou téléchargement)
        $dompdf->stream('invoice.pdf', ['Attachment' => 0]);
    }
}
