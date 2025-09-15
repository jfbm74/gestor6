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
        $fullPath = $this->pathValidator->buildSecurePath($basePath, $relativePath);

        if (!is_readable($fullPath)) {
            throw new Exception("No se puede acceder a la carpeta");
        }

        $items = [];
        $scanResult = scandir($fullPath);

        foreach ($scanResult as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $fullPath . '/' . $item;
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