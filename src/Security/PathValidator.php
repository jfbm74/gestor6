<?php

class PathValidator
{
    private $allowedBasePaths;

    public function __construct($allowedBasePaths)
    {
        $this->allowedBasePaths = [];
        foreach ($allowedBasePaths as $path) {
            $realPath = realpath($path);
            // Si realpath falla, usar la ruta original (para compatibilidad con Windows)
            $this->allowedBasePaths[] = $realPath !== false ? $realPath : $path;
        }
    }

    public function validatePath($path, $basePath = null)
    {
        $realPath = realpath($path);

        // Si realpath falla, usar la ruta original normalizada
        if ($realPath === false) {
            $realPath = $this->normalizePath($path);
        }

        if ($basePath) {
            $realBasePath = realpath($basePath);
            if ($realBasePath === false) {
                $realBasePath = $this->normalizePath($basePath);
            }
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
        $separator = DIRECTORY_SEPARATOR;
        $fullPath = $basePath . $separator . $relativePath;

        // Normalizar la ruta
        $fullPath = $this->normalizePath($fullPath);

        // Intentar obtener realpath, si falla usar la ruta normalizada
        $realFullPath = realpath($fullPath);
        if ($realFullPath !== false) {
            $fullPath = $realFullPath;
        }

        if (!$this->validatePath($fullPath, $basePath)) {
            throw new Exception("Ruta no válida o acceso denegado");
        }

        return $fullPath;
    }

    public function isPathTraversalAttempt($path)
    {
        return strpos($path, '..') !== false || strpos($path, './') !== false;
    }

    private function normalizePath($path)
    {
        // Normalizar separadores de directorio
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);

        // Remover separadores duplicados
        $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR) . '+#', DIRECTORY_SEPARATOR, $path);

        return $path;
    }
}