<?php

class DirectoryManager
{
    private $pathValidator;
    private $documentBases;

    public function __construct(PathValidator $pathValidator, $documentBases)
    {
        $this->pathValidator = $pathValidator;
        $this->documentBases = $documentBases;
    }

    public function getDirectoryContents($baseKey, $relativePath = '')
    {
        if (!isset($this->documentBases[$baseKey])) {
            throw new Exception("Base de documentos no válida");
        }

        $basePath = $this->documentBases[$baseKey]['path'];

        try {
            $fullPath = $this->pathValidator->buildSecurePath($basePath, $relativePath);
        } catch (Exception $e) {
            // Debug: Log del error de path validation
            error_log("PathValidator error: " . $e->getMessage() . " | BasePath: {$basePath} | RelativePath: {$relativePath}");
            throw new Exception("Error de validación de ruta: " . $e->getMessage());
        }

        if (!is_dir($fullPath) || !is_readable($fullPath)) {
            // Debug: Log información detallada
            error_log("Directory access error - FullPath: {$fullPath} | is_dir: " . (is_dir($fullPath) ? 'true' : 'false') . " | is_readable: " . (is_readable($fullPath) ? 'true' : 'false'));
            throw new Exception("No se puede acceder a la carpeta: {$fullPath}");
        }

        $items = [];
        $scanResult = scandir($fullPath);

        if ($scanResult === false) {
            error_log("scandir failed for path: {$fullPath}");
            throw new Exception("Error al escanear el directorio");
        }

        foreach ($scanResult as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
            $itemRelativePath = $relativePath ? $relativePath . '/' . $item : $item;

            $items[] = [
                'name' => $item,
                'path' => $itemRelativePath,
                'full_path' => $itemPath,
                'is_directory' => is_dir($itemPath),
                'size' => is_file($itemPath) ? filesize($itemPath) : null,
                'modified' => filemtime($itemPath),
                'modified_formatted' => date("d/m/Y H:i", filemtime($itemPath))
            ];
        }

        // Ordenar: directorios primero, luego archivos, alfabéticamente
        usort($items, function($a, $b) {
            if ($a['is_directory'] != $b['is_directory']) {
                return $b['is_directory'] - $a['is_directory'];
            }
            return strcasecmp($a['name'], $b['name']);
        });

        return $items;
    }

    public function createDirectory($baseKey, $relativePath, $directoryName)
    {
        $basePath = $this->documentBases[$baseKey]['path'];
        $parentPath = $this->pathValidator->buildSecurePath($basePath, $relativePath);

        $sanitizedName = $this->pathValidator->sanitizeFilename($directoryName);
        if (empty($sanitizedName)) {
            throw new Exception("Nombre de carpeta no válido");
        }

        $newDirPath = $parentPath . '/' . $sanitizedName;

        if (file_exists($newDirPath)) {
            throw new Exception("La carpeta ya existe");
        }

        if (!mkdir($newDirPath)) {
            throw new Exception("Error al crear la carpeta");
        }

        return true;
    }

    public function getBreadcrumbs($baseKey, $relativePath)
    {
        $breadcrumbs = [];

        // Agregar la base
        $breadcrumbs[] = [
            'name' => $this->documentBases[$baseKey]['name'],
            'path' => '',
            'url' => "?base={$baseKey}"
        ];

        if (!empty($relativePath)) {
            $parts = explode('/', $relativePath);
            $pathSoFar = '';

            foreach ($parts as $part) {
                if (empty($part)) continue;

                $pathSoFar .= $part . '/';
                $breadcrumbs[] = [
                    'name' => $part,
                    'path' => rtrim($pathSoFar, '/'),
                    'url' => "?base={$baseKey}&path=" . urlencode(rtrim($pathSoFar, '/'))
                ];
            }
        }

        return $breadcrumbs;
    }

    public function getParentPath($relativePath)
    {
        if (empty($relativePath)) {
            return null;
        }

        $parentPath = dirname($relativePath);
        return $parentPath === '.' ? '' : $parentPath;
    }
}