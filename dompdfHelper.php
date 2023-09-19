<?php

namespace App\Service\base;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Dompdf\Dompdf;
use Dompdf\Options;

class dompdfHelper
{

    /**
     * The function converts HTML to PDF using the Dompdf library and streams the PDF file for download.
     * 
     * @param html The HTML content that you want to convert to a PDF.
     * @param nameFichier The parameter "nameFichier" is used to specify the name of the PDF file that
     * will be generated. If no name is provided, a unique identifier followed by the ".pdf" extension
     * will be used as the file name.
     * @param bool attachment The "attachment" parameter determines whether the PDF file should be
     * downloaded as an attachment or displayed in the browser. If set to true, the PDF will be
     * downloaded as an attachment. If set to false, the PDF will be displayed in the browser.
     * 
     * @return the result of the `stream()` method of the `` object.
     */
    public function toPdf($html, $nameFichier = null, bool $attachment = true)
    {
        if (!$nameFichier) {
            $nameFichier = \uniqid() . '.pdf';
        }
        $tmp = sys_get_temp_dir();
        $dompdf = new Dompdf();
        $dompdf->setOptions(new Options([


            'isRemoteEnabled' => true,
            'fontDir' => $tmp,
            'fontCache' => $tmp,
            'tempDir' => $tmp,
            'chroot' => $tmp
        ]));
        $dompdf->set_option('isFontSubsettingEnabled', true);
        $dompdf->set_option('defaultMediaType', 'all');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();
        return $dompdf->stream($nameFichier, ['Attachment' => $attachment, 'compress' => true,]);
    }
}
