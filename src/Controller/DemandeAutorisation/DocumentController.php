<?php

namespace App\Controller\DemandeAutorisation;

use App\Entity\DemandeAutorisation\Document;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentController extends AbstractController
{
    /**
     * @Route("/document/download/{id}", name="app_document_download")
     */
    public function download(Document $document): Response
    {
        // This is a placeholder. The actual implementation will depend on how the files are stored.
        // For now, we'll just return a simple response.
        $filePath = $this->getParameter('documents_directory') . '/' . $document->getFilePath();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('The file does not exist');
        }

        return $this->file($filePath, $document->getNom());
    }
}
