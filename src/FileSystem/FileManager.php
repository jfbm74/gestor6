<?php

class FileManager
{
    private $pathValidator;
    private $documentBases;
    private $config;

    public function __construct(PathValidator $pathValidator, $documentBases, $config)
    {
        $this->pathValidator = $pathValidator;
        $this->documentBases = $documentBases;
        $this->config = $config;
    }

    public function downloadFile($baseKey, $relativePath = null, $absolutePath = null)
    {
        if ($absolutePath) {
            $filePath = realpath($absolutePath);
            if (!$this->pathValidator->validatePath($filePath)) {
                throw new Exception("Acceso denegado al archivo");
            }
        } else {
            $basePath = $this->documentBases[$baseKey]['path'];
            $filePath = $this->pathValidator->buildSecurePath($basePath, $relativePath);
        }

        if (!is_file($filePath)) {
            throw new Exception("Archivo no encontrado");
        }

        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    }

    public function uploadFile($baseKey, $relativePath, $uploadedFile)
    {
        $basePath = $this->documentBases[$baseKey]['path'];
        $targetDir = $this->pathValidator->buildSecurePath($basePath, $relativePath);

        if (!is_dir($targetDir)) {
            throw new Exception("Directorio de destino no válido");
        }

        $fileName = basename($uploadedFile['name']);
        $sanitizedFileName = $this->pathValidator->sanitizeFilename($fileName);

        if (empty($sanitizedFileName)) {
            throw new Exception("Nombre de archivo no válido");
        }

        // Verificar extensión si está configurado
        if (!empty($this->config['app']['allowed_extensions'])) {
            $extension = strtolower(pathinfo($sanitizedFileName, PATHINFO_EXTENSION));
            if (!in_array($extension, $this->config['app']['allowed_extensions'])) {
                throw new Exception("Tipo de archivo no permitido");
            }
        }

        $targetPath = $targetDir . '/' . $sanitizedFileName;

        if (file_exists($targetPath)) {
            throw new Exception("El archivo ya existe");
        }

        if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
            throw new Exception("Error al subir el archivo");
        }

        return $sanitizedFileName;
    }

    public function deleteItem($baseKey, $relativePath, $itemName)
    {
        $basePath = $this->documentBases[$baseKey]['path'];
        $parentPath = $this->pathValidator->buildSecurePath($basePath, $relativePath);
        $itemPath = realpath($parentPath . '/' . basename($itemName));

        if (!$itemPath || strpos($itemPath, $parentPath) !== 0) {
            throw new Exception("Elemento no válido");
        }

        if (is_file($itemPath)) {
            if (!unlink($itemPath)) {
                throw new Exception("Error al eliminar el archivo");
            }
        } elseif (is_dir($itemPath)) {
            $this->deleteDirectoryRecursive($itemPath);
        } else {
            throw new Exception("Elemento no encontrado");
        }

        return true;
    }

    private function deleteDirectoryRecursive($path)
    {
        if (!is_dir($path)) {
            return unlink($path);
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $this->deleteDirectoryRecursive("$path/$file");
        }

        return rmdir($path);
    }

    public function renameItem($baseKey, $relativePath, $oldName, $newName)
    {
        $basePath = $this->documentBases[$baseKey]['path'];
        $parentPath = $this->pathValidator->buildSecurePath($basePath, $relativePath);

        $oldPath = realpath($parentPath . '/' . $oldName);
        if (!$oldPath || strpos($oldPath, $parentPath) !== 0) {
            throw new Exception("Elemento original no válido");
        }

        $sanitizedNewName = $this->pathValidator->sanitizeFilename($newName);
        if (empty($sanitizedNewName)) {
            throw new Exception("Nuevo nombre no válido");
        }

        $newPath = $parentPath . '/' . $sanitizedNewName;

        if (file_exists($newPath)) {
            throw new Exception("Ya existe un elemento con ese nombre");
        }

        if (!rename($oldPath, $newPath)) {
            throw new Exception("Error al renombrar el elemento");
        }

        return $sanitizedNewName;
    }

    public function copyAndRename($sourceRelativePath, $newName, $destinationBaseKey)
    {
        $sourcePath = realpath($this->documentBases['SCANNER']['path'] . '/' . $sourceRelativePath);

        if (!$sourcePath || !is_file($sourcePath)) {
            throw new Exception("Archivo fuente no válido");
        }

        $sanitizedNewName = $this->pathValidator->sanitizeFilename($newName);
        if (empty($sanitizedNewName)) {
            throw new Exception("Nuevo nombre no válido");
        }

        $destinationPath = $this->documentBases[$destinationBaseKey]['path'];
        $destinationFilePath = $destinationPath . '/' . $sanitizedNewName;

        if (file_exists($destinationFilePath)) {
            throw new Exception("Ya existe un archivo con ese nombre en el destino");
        }

        if (!copy($sourcePath, $destinationFilePath)) {
            throw new Exception("Error al copiar el archivo");
        }

        return $sanitizedNewName;
    }
}