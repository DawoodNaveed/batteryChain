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
 * @property AwsService awsService
 * @property $awsLogoFolder
 * @property $co2NeutralSeal
 * @property $insuranceSeal
 */
class PdfService
{
    const PREFIX_BASE64 = 'data:image/png;base64,';

    /**
     * PdfService constructor.
     * @param Environment $twig
     * @param TranslatorInterface $translator
     * @param AwsService $awsService
     * @param $awsLogoFolder
     * @param $co2NeutralSeal
     * @param $insuranceSeal
     */
    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        AwsService $awsService,
        $awsLogoFolder,
        $co2NeutralSeal,
        $insuranceSeal
    ) {
        $this->twig = $twig;
        $this->translator = $translator;
        $this->awsService = $awsService;
        $this->awsLogoFolder = $awsLogoFolder;
        $this->co2NeutralSeal = $co2NeutralSeal;
        $this->insuranceSeal = $insuranceSeal;
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
        $manufacturerLogo = $battery->getManufacturer()->getLogo()
            ? $this->getEncodedImage(
                $this->awsService->getPreSignedUrl(
                    $battery->getManufacturer()->getLogo(),
                    $this->awsLogoFolder
                )
            ) : '';
        $co2NeutralSeal = $battery->getIsClimateNeutral() ?  $this->getEncodedImage($this->co2NeutralSeal) : '';
        $insuranceSeal = $battery->getIsInsured() ?  $this->getEncodedImage($this->insuranceSeal) : '';
        $poweredByLogo = $this->getEncodedImage(CustomHelper::PDF_LOGO_URL);
        /* get barcode images base64 encoding */
        $domPdf = new Dompdf($pdfOptions);
        $html = $this->twig->render('battery/detail_view_download.html.twig', [
            'battery' => $battery,
            'documentTitle' => CustomHelper::PDF_TITLE,
            'createdDate' => date('d.m.Y'),
            'poweredByLogo' => $poweredByLogo,
            'detail' => isset(CustomHelper::BATTERY_STATUSES_DETAILS[$battery->getStatus()])
                ? $this->translator->trans(CustomHelper::BATTERY_STATUSES_DETAILS[$battery->getStatus()])
                : null,
            'transaction' => array_pop($transaction) ?? null,
            'transactions' => $battery->getTransactionLogs()->toArray(),
            'manufacturerLogo' => $manufacturerLogo,
            'co2NeutralLogo' => $co2NeutralSeal,
            'insuranceLogo' => $insuranceSeal,
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

    /**
     * @param Battery $battery
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function createBatteryLabelPdf(Battery $battery)
    {
        $pdfOptions = new Options();
        $pdfOptions->set('isRemoteEnabled', true);
        $manufacturerLogo = $battery->getManufacturer()->getLogo()
            ? $this->getEncodedImage(
                $this->awsService->getPreSignedUrl(
                    $battery->getManufacturer()->getLogo(),
                    $this->awsLogoFolder
                )
            ) : '';
        $poweredByLogo = $this->getEncodedImage(CustomHelper::PDF_LOGO_URL);
        $highVoltageIcon = $this->getEncodedImage(CustomHelper::HIGH_VOLTAGE_ICON_URL);
        $warningIcon = $this->getEncodedImage(CustomHelper::WARNING_ICON_URL);
        $manualIcon = $this->getEncodedImage(CustomHelper::MANUAL_ICON_URL);
        $garbageCanIcon = $this->getEncodedImage(CustomHelper::GARBAGE_CAN_ICON_URL);
        /* get barcode images base64 encoding */
        $domPdf = new Dompdf($pdfOptions);
        $html = $this->twig->render('battery/label_download.html.twig', [
            'battery' => $battery,
            'createdDate' => date('d.m.Y'),
            'poweredByLogo' => $poweredByLogo,
            'manufacturerLogo' => $manufacturerLogo,
            'highVoltageIcon' => $highVoltageIcon,
            'warningIcon' => $warningIcon,
            'manualIcon' => $manualIcon,
            'garbageCanIcon' => $garbageCanIcon,
        ]);

        $domPdf->loadHtml($html);
        $domPdf->setPaper('A5', 'portrait');
        $domPdf->render();
        $domPdf->stream('label.pdf', [
            "Attachment" => true
        ]);
    }
}