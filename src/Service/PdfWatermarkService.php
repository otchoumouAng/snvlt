<?php

namespace App\Service;

use App\Pdf\WatermarkPdf;

class PdfWatermarkService
{
    public function addWatermark(string $filePath, string $watermarkText): void
    {
        $pdf = new WatermarkPdf();
        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            $pdf->SetFont('Helvetica', 'B', 50);
            $pdf->SetTextColor(192, 192, 192);

            // Calculate the position for the watermark
            $pdf->SetXY(($size['width'] - $pdf->GetStringWidth($watermarkText)) / 2, $size['height'] / 2);

            // Rotate and place the watermark
            $pdf->rotate(45);
            $pdf->Text(0, 0, $watermarkText);
            $pdf->rotate(0);
        }

        $pdf->Output($filePath, 'F');
    }
}