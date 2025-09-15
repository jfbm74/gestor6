<?php

class SearchManager
{
    private $documentBases;
    private $pathValidator;

    public function __construct($documentBases, PathValidator $pathValidator)
    {
        $this->documentBases = $documentBases;
        $this->pathValidator = $pathValidator;
    }

    public function searchFiles($query, $baseKeys = null)
    {
        if (empty($query)) {
            return [];
        }

        $results = [];
        $searchBases = $baseKeys ?: array_keys($this->documentBases);

        foreach ($searchBases as $baseKey) {
            if (!isset($this->documentBases[$baseKey])) {
                continue;
            }

            $basePath = $this->documentBases[$baseKey]['path'];

            if (!is_dir($basePath)) {
                continue;
            }

            $baseResults = $this->searchInDirectory($basePath, $query, $baseKey);
            $results = array_merge($results, $baseResults);
        }

        // Ordenar resultados por relevancia (nombre exacto primero, luego por fecha)
        usort($results, function($a, $b) {
            // Primero por relevancia (exactitud del match)
            $aExact = strcasecmp($a['name'], $a['query']) === 0 ? 1 : 0;
            $bExact = strcasecmp($b['name'], $b['query']) === 0 ? 1 : 0;

            if ($aExact !== $bExact) {
                return $bExact - $aExact;
            }

            // Luego por fecha de modificación (más reciente primero)
            return $b['modified'] - $a['modified'];
        });

        return $results;
    }

    private function searchInDirectory($directoryPath, $query, $baseKey)
    {
        $results = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if (stripos($file->getFilename(), $query) !== false) {
                    $relativePath = ltrim(str_replace(realpath($directoryPath), '', $file->getRealPath()), '\\/');

                    $results[] = [
                        'name' => $file->getFilename(),
                        'path' => $relativePath,
                        'full_path' => $file->getRealPath(),
                        'base_key' => $baseKey,
                        'base_name' => $this->documentBases[$baseKey]['name'],
                        'is_directory' => $file->isDir(),
                        'size' => $file->isFile() ? $file->getSize() : null,
                        'size_formatted' => $file->isFile() ? $this->formatFileSize($file->getSize()) : null,
                        'modified' => $file->getMTime(),
                        'modified_formatted' => date("d/m/Y H:i", $file->getMTime()),
                        'directory_path' => dirname($relativePath),
                        'query' => $query,
                        'extension' => $file->isFile() ? strtolower($file->getExtension()) : null
                    ];
                }
            }
        } catch (Exception $e) {
            // Log error pero continuar con otros directorios
            error_log("Error searching in directory {$directoryPath}: " . $e->getMessage());
        }

        return $results;
    }

    public function getRecentFiles($limit = 10, $baseKeys = null)
    {
        $allFiles = [];
        $searchBases = $baseKeys ?: array_keys($this->documentBases);

        foreach ($searchBases as $baseKey) {
            if (!isset($this->documentBases[$baseKey])) {
                continue;
            }

            $basePath = $this->documentBases[$baseKey]['path'];

            if (!is_dir($basePath)) {
                continue;
            }

            $baseFiles = $this->getFilesFromDirectory($basePath, $baseKey);
            $allFiles = array_merge($allFiles, $baseFiles);
        }

        // Ordenar por fecha de modificación (más reciente primero)
        usort($allFiles, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return array_slice($allFiles, 0, $limit);
    }

    private function getFilesFromDirectory($directoryPath, $baseKey, $maxDepth = 3)
    {
        $files = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            $iterator->setMaxDepth($maxDepth);

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = ltrim(str_replace(realpath($directoryPath), '', $file->getRealPath()), '\\/');

                    $files[] = [
                        'name' => $file->getFilename(),
                        'path' => $relativePath,
                        'full_path' => $file->getRealPath(),
                        'base_key' => $baseKey,
                        'base_name' => $this->documentBases[$baseKey]['name'],
                        'size' => $file->getSize(),
                        'size_formatted' => $this->formatFileSize($file->getSize()),
                        'modified' => $file->getMTime(),
                        'modified_formatted' => date("d/m/Y H:i", $file->getMTime()),
                        'directory_path' => dirname($relativePath),
                        'extension' => strtolower($file->getExtension())
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error getting files from directory {$directoryPath}: " . $e->getMessage());
        }

        return $files;
    }

    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    public function getFileIcon($extension)
    {
        $iconMap = [
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'ppt' => 'fa-file-powerpoint',
            'pptx' => 'fa-file-powerpoint',
            'jpg' => 'fa-file-image',
            'jpeg' => 'fa-file-image',
            'png' => 'fa-file-image',
            'gif' => 'fa-file-image',
            'zip' => 'fa-file-archive',
            'rar' => 'fa-file-archive',
            'txt' => 'fa-file-alt',
        ];

        return $iconMap[$extension] ?? 'fa-file';
    }
}