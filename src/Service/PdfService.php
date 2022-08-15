<?php

namespace App\Service;

use App\Entity\Battery;
use App\Helper\CustomHelper;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class PdfService
 * @package App\Service
 * @property Environment twig
 * @property TranslatorInterface translator
 */
class PdfService
{
    const PREFIX_BASE64 = 'data:image/png;base64,';

    /**
     * PdfService constructor.
     * @param Environment $twig
     * @param TranslatorInterface $translator
     */
    public function __construct(Environment $twig, TranslatorInterface $translator)
    {
        $this->twig = $twig;
        $this->translator = $translator;
    }

    /**
     * @param Battery $battery
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function createBatteryPdf(Battery $battery)
    {
        $transaction = array_filter(($battery->getTransactionLogs()->toArray()), function ($trans) {
            return $trans->getTransactionType() === $trans->getBattery()->getStatus();
        });
        $pdfOptions = new Options();
        $pdfOptions->set('isRemoteEnabled', true);
        $manufacturerLogo = $battery->getManufacturer()->getLogo() ? $this->getEncodedImage('resources/uploads/logos/' . $battery->getManufacturer()->getLogo()) : '';
        $co2NeutralLogo = $battery->getManufacturer()->getCo2Logo() ? $this->getEncodedImage('resources/uploads/icons/co2/' . $battery->getManufacturer()->getCo2Logo()) : '';
        $insuranceLogo = $battery->getManufacturer()->getInsuranceLogo() ? $this->getEncodedImage('resources/uploads/icons/insurance/' . $battery->getManufacturer()->getInsuranceLogo()) : '';
        $poweredByLogo = $this->getEncodedImage('https://4art-marketplace-thumb-prelive.s3.eu-central-1.amazonaws.com/thumbnail/batterychain/pdf_logo.png');
        /* get barcode images base64 encoding */
        $domPdf = new Dompdf($pdfOptions);
        $html = $this->twig->render('battery/detail_view_download.html.twig', [
            'battery' => $battery,
            'documentTitle' => "Battery Passport",
            'createdDate' => date('d.m.Y'),
            'poweredByLogo' => $poweredByLogo,
            'detail' => isset(CustomHelper::BATTERY_STATUSES_DETAILS[$battery->getStatus()])
                ? $this->translator->trans(CustomHelper::BATTERY_STATUSES_DETAILS[$battery->getStatus()])
                : null,
            'transaction' => array_pop($transaction) ?? null,
            'transactions' => $battery->getTransactionLogs()->toArray(),
            'manufacturerLogo' => $manufacturerLogo,
            'co2NeutralLogo' => $co2NeutralLogo,
            'insuranceLogo' => $insuranceLogo,
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

    /**
     * @param $batteries
     * @param string $filename
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function createBatteriesReportPdf($batteries, string $filename)
    {
        $pdfOptions = new Options();
        $pdfOptions->set('isRemoteEnabled', true);
        $poweredByLogo = $this->getEncodedImage(CustomHelper::LOGO_URL);
        /* get barcode images base64 encoding */
        $domPdf = new Dompdf($pdfOptions);
        $html = $this->twig->render('battery/report_download.html.twig', [
            'batteries' => $batteries,
            'documentTitle' => $filename,
            'createdDate' => date('d.m.Y'),
            'poweredByLogo' => $poweredByLogo
        ]);

        $domPdf->loadHtml($html);
        $domPdf->setPaper('A4', 'landscape');
        $domPdf->render();
        $domPdf->stream($filename, [
            "Attachment" => true
        ]);
    }
}