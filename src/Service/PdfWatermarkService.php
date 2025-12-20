<?php

namespace App\Service;

use App\Service\Pdf\WatermarkablePdf; // MODIFICATION 1 : Utiliser notre nouvelle classe

class PdfWatermarkService
{
    private string $signedDocumentsDir;

    public function __construct(string $documents_signe_directory)
    {
        $this->signedDocumentsDir = $documents_signe_directory;
    }

    public function addWatermark(string $originalFilename): string
    {
        $pdfPath = $this->signedDocumentsDir . '/' . $originalFilename;

        if (!file_exists($pdfPath)) {
            throw new \Exception("Le fichier PDF original n'a pas été trouvé : " . $pdfPath);
        }

        $pdf = new WatermarkablePdf();
        $pageCount = $pdf->setSourceFile($pdfPath);

        // Définir les propriétés du filigrane une seule fois avant la boucle
        $pdf->SetFont('Helvetica', 'B', 40);
        $pdf->SetTextColor(16, 200, 0); 

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Appliquer la rotation autour du centre de la page
            $pdf->rotate(45, $size['width'] / 2, $size['height'] / 2);

            // CORRECTION : Positionner le texte verticalement uniquement
            $pdf->SetY($size['height'] / 2);
            
            // La cellule utilise toute la largeur (0) pour centrer le texte ('C')
            $pdf->Cell(0, 10, 'Consultation SNVLT Uniquement', 0, 0, 'C');
            
            // Annuler la rotation pour les opérations futures sur la page
            $pdf->rotate(0);
        }

        // Sauvegarder le nouveau fichier avec le filigrane
        $watermarkedFilename = 'wm_' . $originalFilename;
        $newFilePath = $this->signedDocumentsDir . '/' . $watermarkedFilename;
        $pdf->Output($newFilePath, 'F'); // 'F' sauvegarde le fichier sur le serveur
        
        return $watermarkedFilename;
    }
}