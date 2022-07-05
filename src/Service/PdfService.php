<?php

namespace App\Service;

use App\Entity\Battery;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class PdfService
 * @package App\Service
 * @property Environment twig
 */
class PdfService
{
    const PREFIX_BASE64 = 'data:image/png;base64,';

    /**
     * PdfService constructor.
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param Battery $battery
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function createBatteryPdf(Battery $battery)
    {
        $pdfOptions = new Options();
        $pdfOptions->set('isRemoteEnabled', true);
        $poweredByLogo = $this->getEncodedImage('resources/pdf_logo.png');
        /* get barcode images base64 encoding */
        $domPdf = new Dompdf($pdfOptions);
        $html = $this->twig->render('battery/detail_view_download.html.twig', [
            'battery' => $battery,
            'documentTitle' => "Battery Passport",
            'createdDate' => date('d.m.Y'),
            'poweredByLogo' => $poweredByLogo,
        ]);

        $domPdf->loadHtml($html);
        $domPdf->setPaper('A4', 'portrait');
        $domPdf->render();
        $domPdf->stream('battery.pdf', [
            "Attachment" => true
        ]);
    }

    /**
     * @param string|null $reference
     * @return string|null
     */
    public function getEncodedImage(?string $reference): ?string
    {
        return self::PREFIX_BASE64 . base64_encode(file_get_contents($reference));
    }
}