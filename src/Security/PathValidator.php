<?php

class PathValidator
{
    private $allowedBasePaths;

    public function __construct($allowedBasePaths)
    {
        $this->allowedBasePaths = array_map('realpath', $allowedBasePaths);
    }

    public function validatePath($path, $basePath = null)
    {
        $realPath = realpath($path);

        if ($realPath === false) {
            return false;
        }

        if ($basePath) {
            $realBasePath = realpath($basePath);
            return $realBasePath && strpos($realPath, $realBasePath) === 0;
        }

        // Verificar contra todas las rutas base permitidas
        foreach ($this->allowedBasePaths as $allowedPath) {
            if ($allowedPath && strpos($realPath, $allowedPath) === 0) {
                return true;
            }
        }

        return false;
    }

    public function sanitizeFilename($filename, $allowedCharsPattern = null)
    {
        if (!$allowedCharsPattern) {
            $allowedCharsPattern = '/[^A-Za-z0-9\-_\. ()]/';
        }

        return preg_replace($allowedCharsPattern, '', $filename);
    }

    public function buildSecurePath($basePath, $relativePath)
    {
        $fullPath = realpath($basePath . '/' . $relativePath);

        if (!$this->validatePath($fullPath, $basePath)) {
            throw new Exception("Ruta no válida o acceso denegado");
        }

        return $fullPath;
    }

    public function isPathTraversalAttempt($path)
    {
        return strpos($path, '..') !== false || strpos($path, './') !== false;
    }
}