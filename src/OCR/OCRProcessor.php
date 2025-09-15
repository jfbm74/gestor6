<?php

class OCRProcessor
{
    private $tempDir;
    private $tesseractPath;
    private $convertPath;

    public function __construct($tempDir = null)
    {
        // Detectar sistema operativo y configurar rutas
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $this->tempDir = $tempDir ?: sys_get_temp_dir();
            $this->tesseractPath = $this->findWindowsExecutable('tesseract');
            $this->convertPath = $this->findWindowsExecutable('magick');
        } else {
            $this->tempDir = $tempDir ?: '/tmp';
            $this->tesseractPath = '/opt/homebrew/bin/tesseract';
            $this->convertPath = '/opt/homebrew/bin/magick';
        }

        $this->tempDir = rtrim($this->tempDir, '/\\');

        // Verificar que las herramientas estén disponibles
        if (!is_executable($this->tesseractPath)) {
            throw new Exception("Tesseract no está instalado o no es ejecutable: {$this->tesseractPath}");
        }

        if (!is_executable($this->convertPath)) {
            throw new Exception("ImageMagick no está instalado o no es ejecutable: {$this->convertPath}");
        }

        // Crear directorio temporal si no existe
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Procesa un PDF y extrae texto usando OCR
     */
    public function processPDF($pdfPath, $language = 'spa+eng')
    {
        if (!file_exists($pdfPath)) {
            throw new Exception("Archivo PDF no encontrado: {$pdfPath}");
        }

        $fileHash = md5($pdfPath . filemtime($pdfPath));
        $tempImagePrefix = $this->tempDir . '/ocr_' . $fileHash;
        $tempTextFile = $this->tempDir . '/ocr_text_' . $fileHash . '.txt';

        try {
            // Paso 1: Convertir PDF a imágenes
            $this->convertPDFToImages($pdfPath, $tempImagePrefix);

            // Paso 2: Procesar cada página con OCR
            $extractedText = $this->processImagesWithOCR($tempImagePrefix, $tempTextFile, $language);

            // Paso 3: Limpiar archivos temporales
            $this->cleanupTempFiles($tempImagePrefix);

            return [
                'success' => true,
                'text' => $extractedText,
                'page_count' => $this->getPageCount($tempImagePrefix),
                'language' => $language
            ];

        } catch (Exception $e) {
            // Limpiar archivos temporales en caso de error
            $this->cleanupTempFiles($tempImagePrefix);
            throw $e;
        }
    }

    /**
     * Convierte PDF a imágenes usando ImageMagick
     */
    private function convertPDFToImages($pdfPath, $outputPrefix)
    {
        // Configurar densidad para mejor calidad OCR
        $density = 300; // DPI
        $quality = 100;

        $command = sprintf(
            '%s -density %d -quality %d "%s" "%s-%%03d.png" 2>&1',
            escapeshellcmd($this->convertPath),
            $density,
            $quality,
            escapeshellarg($pdfPath),
            escapeshellarg($outputPrefix)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Error al convertir PDF a imágenes: " . implode("\n", $output));
        }

        // Verificar que se crearon las imágenes
        $firstImage = $outputPrefix . '-000.png';
        if (!file_exists($firstImage)) {
            throw new Exception("No se pudieron crear las imágenes del PDF");
        }
    }

    /**
     * Procesa las imágenes con Tesseract OCR
     */
    private function processImagesWithOCR($imagePrefix, $textFile, $language)
    {
        $allText = '';
        $pageNumber = 0;

        // Procesar cada página
        while (true) {
            $imagePath = sprintf('%s-%03d.png', $imagePrefix, $pageNumber);

            if (!file_exists($imagePath)) {
                break; // No hay más páginas
            }

            $pageTextFile = $textFile . '_page_' . $pageNumber;

            // Ejecutar Tesseract en esta página
            $command = sprintf(
                '%s "%s" "%s" -l %s 2>&1',
                escapeshellcmd($this->tesseractPath),
                escapeshellarg($imagePath),
                escapeshellarg($pageTextFile),
                escapeshellarg($language)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                error_log("Error OCR en página {$pageNumber}: " . implode("\n", $output));
                $allText .= "\n[ERROR: No se pudo procesar la página {$pageNumber}]\n";
            } else {
                // Leer el texto extraído
                $pageTextPath = $pageTextFile . '.txt';
                if (file_exists($pageTextPath)) {
                    $pageText = file_get_contents($pageTextPath);
                    $allText .= "\n--- PÁGINA " . ($pageNumber + 1) . " ---\n";
                    $allText .= trim($pageText) . "\n";
                    unlink($pageTextPath); // Limpiar archivo temporal
                }
            }

            $pageNumber++;
        }

        return trim($allText);
    }

    /**
     * Cuenta las páginas procesadas
     */
    private function getPageCount($imagePrefix)
    {
        $count = 0;
        while (file_exists(sprintf('%s-%03d.png', $imagePrefix, $count))) {
            $count++;
        }
        return $count;
    }

    /**
     * Limpia archivos temporales
     */
    private function cleanupTempFiles($imagePrefix)
    {
        $pageNumber = 0;
        while (true) {
            $imagePath = sprintf('%s-%03d.png', $imagePrefix, $pageNumber);
            if (file_exists($imagePath)) {
                unlink($imagePath);
                $pageNumber++;
            } else {
                break;
            }
        }
    }

    /**
     * Guarda el texto extraído como metadata
     */
    public function saveTextMetadata($pdfPath, $extractedText, $format = 'json')
    {
        $pathInfo = pathinfo($pdfPath);
        $baseName = $pathInfo['dirname'] . '/' . $pathInfo['filename'];

        switch ($format) {
            case 'json':
                $metadataPath = $baseName . '.ocr.json';
                $metadata = [
                    'pdf_file' => basename($pdfPath),
                    'processed_date' => date('Y-m-d H:i:s'),
                    'text_content' => $extractedText,
                    'char_count' => strlen($extractedText),
                    'word_count' => str_word_count($extractedText)
                ];
                file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;

            case 'txt':
                $metadataPath = $baseName . '.ocr.txt';
                file_put_contents($metadataPath, $extractedText);
                break;

            default:
                throw new Exception("Formato no soportado: {$format}");
        }

        return $metadataPath;
    }

    /**
     * Encuentra ejecutables en Windows
     */
    private function findWindowsExecutable($executable)
    {
        // Rutas comunes en Windows
        $commonPaths = [
            "C:\\Program Files\\Tesseract-OCR\\tesseract.exe",
            "C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe",
            "C:\\Program Files\\ImageMagick\\magick.exe",
            "C:\\Program Files (x86)\\ImageMagick\\magick.exe",
        ];

        // Buscar en PATH primero
        $cmd = $executable . '.exe';
        $output = [];
        exec("where $cmd 2>nul", $output, $returnCode);

        if ($returnCode === 0 && !empty($output[0])) {
            return $output[0];
        }

        // Buscar en rutas comunes
        foreach ($commonPaths as $path) {
            if (stripos($path, $executable) !== false && file_exists($path)) {
                return $path;
            }
        }

        // Si no encuentra, intentar con el nombre simple
        return $executable;
    }

    /**
     * Lee metadata OCR existente
     */
    public function readTextMetadata($pdfPath, $format = 'json')
    {
        $pathInfo = pathinfo($pdfPath);
        $baseName = $pathInfo['dirname'] . '/' . $pathInfo['filename'];

        switch ($format) {
            case 'json':
                $metadataPath = $baseName . '.ocr.json';
                if (file_exists($metadataPath)) {
                    return json_decode(file_get_contents($metadataPath), true);
                }
                break;

            case 'txt':
                $metadataPath = $baseName . '.ocr.txt';
                if (file_exists($metadataPath)) {
                    return file_get_contents($metadataPath);
                }
                break;
        }

        return null;
    }
}