<?php
/**
 * Sistema de Gestión de Documentos - Clínica Bonsana
 * Punto de entrada principal del sistema modular
 */

// Iniciar medición de tiempo
$startTime = microtime(true);

// Autoloader simple para las clases
spl_autoload_register(function ($className) {
    $paths = [
        'src/Auth/',
        'src/FileSystem/',
        'src/Security/',
        'src/Controllers/'
    ];

    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Cargar configuración
$configLoadTime = microtime(true);
$config = require 'config/config.php';
error_log("Config load time: " . number_format((microtime(true) - $configLoadTime) * 1000, 2) . "ms");

// Inicializar componentes principales
$initTime = microtime(true);
$pathValidator = new PathValidator(array_column($config['document_bases'], 'path'));
$sessionManager = new SessionManager($config);
$authManager = new AuthManager($config, $sessionManager);
error_log("Components init time: " . number_format((microtime(true) - $initTime) * 1000, 2) . "ms");

// Manejar logout
if (isset($_GET['logout'])) {
    $authManager->logout();
    header('Location: index.php');
    exit;
}

// Manejar login
$loginError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    if (!$authManager->authenticate($_POST['username'], $_POST['password'])) {
        $loginError = "Usuario o contraseña incorrectos.";
    } else {
        header('Location: index.php');
        exit;
    }
}

// Si no está autenticado, mostrar login
if (!$authManager->isAuthenticated()) {
    include 'templates/pages/login.php';
    exit;
}

// === USUARIO AUTENTICADO - LÓGICA PRINCIPAL ===

// Inicializar managers de archivos
$managersInitTime = microtime(true);
$directoryManager = new DirectoryManager($pathValidator, $config['document_bases']);
$fileManager = new FileManager($pathValidator, $config['document_bases'], $config);
$searchManager = new SearchManager($config['document_bases'], $pathValidator);
error_log("Managers init time: " . number_format((microtime(true) - $managersInitTime) * 1000, 2) . "ms");

// Determinar base activa
$activeBaseKey = array_key_first($config['document_bases']);
if (isset($_GET['base']) && isset($config['document_bases'][$_GET['base']])) {
    $activeBaseKey = $_GET['base'];
}

try {
    // === MANEJO DE DESCARGAS ===
    if (isset($_GET['download'])) {
        $authManager->requirePermission('read');

        if (isset($_GET['is_absolute']) && $_GET['is_absolute'] === '1') {
            $fileManager->downloadFile(null, null, $_GET['download']);
        } else {
            $fileManager->downloadFile($activeBaseKey, $_GET['download']);
        }
        exit;
    }

    // === MANEJO DE OPERACIONES DE ARCHIVOS ===
    $currentPathRelative = isset($_GET['path']) && !isset($_GET['search']) ? trim($_GET['path'], '/') : '';

    // Eliminación de archivos/carpetas
    if (isset($_GET['delete'])) {
        $authManager->requirePermission('admin');
        $fileManager->deleteItem($activeBaseKey, $currentPathRelative, $_GET['delete']);
        header('Location: ?path=' . urlencode($currentPathRelative) . '&base=' . $activeBaseKey);
        exit;
    }

    // Upload de archivos
    if (isset($_FILES['newfile'])) {
        $authManager->requirePermission('upload');
        try {
            $fileManager->uploadFile($activeBaseKey, $currentPathRelative, $_FILES['newfile']);
            header('Location: ?path=' . urlencode($currentPathRelative) . '&base=' . $activeBaseKey);
            exit;
        } catch (Exception $e) {
            $uploadError = $e->getMessage();
        }
    }

    // Crear nueva carpeta
    if (isset($_POST['new_folder']) && !empty($_POST['new_folder'])) {
        $authManager->requirePermission('admin');
        try {
            $directoryManager->createDirectory($activeBaseKey, $currentPathRelative, $_POST['new_folder']);
            header('Location: ?path=' . urlencode($currentPathRelative) . '&base=' . $activeBaseKey);
            exit;
        } catch (Exception $e) {
            $folderError = $e->getMessage();
        }
    }

    // Renombrar archivo/carpeta
    if (isset($_POST['old_name']) && isset($_POST['new_name']) && !empty($_POST['new_name'])) {
        $authManager->requirePermission('admin');
        try {
            $fileManager->renameItem($activeBaseKey, $currentPathRelative, $_POST['old_name'], $_POST['new_name']);
            header('Location: ?path=' . urlencode($currentPathRelative) . '&base=' . $activeBaseKey);
            exit;
        } catch (Exception $e) {
            $renameError = $e->getMessage();
        }
    }

    // Copiar y renombrar archivo
    if (isset($_POST['action']) && $_POST['action'] === 'copy_and_rename') {
        $authManager->requirePermission('upload');
        try {
            $fileManager->copyAndRename(
                $_POST['original_file_relative'],
                $_POST['new_name_for_copy'],
                $_POST['destination_base']
            );
            header('Location: ?path=' . urlencode($currentPathRelative) . '&base=' . $activeBaseKey);
            exit;
        } catch (Exception $e) {
            $copyError = $e->getMessage();
        }
    }

    // Batch upload de archivos
    if (isset($_POST['action']) && $_POST['action'] === 'batch_upload') {
        $authManager->requirePermission('upload');

        header('Content-Type: application/json');

        try {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error al recibir el archivo");
            }

            if (!isset($_POST['new_name']) || !isset($_POST['destination_base'])) {
                throw new Exception("Parámetros faltantes");
            }

            $uploadedFile = $_FILES['file'];
            $newName = $_POST['new_name'];
            $destinationBase = $_POST['destination_base'];

            // Validar que la base de destino existe
            if (!isset($config['document_bases'][$destinationBase])) {
                throw new Exception("Base de documentos no válida");
            }

            // Subir archivo directamente a la base de destino
            $destinationPath = $config['document_bases'][$destinationBase]['path'];

            $sanitizedFileName = $pathValidator->sanitizeFilename($newName);
            if (empty($sanitizedFileName)) {
                throw new Exception("Nombre de archivo no válido");
            }

            $targetPath = $destinationPath . '/' . $sanitizedFileName;

            if (file_exists($targetPath)) {
                throw new Exception("El archivo ya existe");
            }

            if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                throw new Exception("Error al subir el archivo");
            }

            echo json_encode(['success' => true, 'filename' => $sanitizedFileName]);
            exit;

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    // === PREPARAR DATOS PARA LA VISTA ===

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        // Búsqueda
        $searchResults = $searchManager->searchFiles($_GET['search']);
        $recentFiles = [];
        $directoryContents = [];
        $breadcrumbs = [];
        $parentPath = null;
    } else {
        // Navegación normal
        $navigationTime = microtime(true);
        $searchResults = [];

        $dirContentsTime = microtime(true);
        $directoryContents = $directoryManager->getDirectoryContents($activeBaseKey, $currentPathRelative);
        error_log("Directory contents time: " . number_format((microtime(true) - $dirContentsTime) * 1000, 2) . "ms");

        $breadcrumbs = $directoryManager->getBreadcrumbs($activeBaseKey, $currentPathRelative);
        $parentPath = $directoryManager->getParentPath($currentPathRelative);

        // TEMPORALMENTE DESHABILITADO: Obtener archivos recientes
        // Esta operación es muy costosa en Windows - escanea todo el directorio
        $recentFiles = [];

        /*
        if (empty($currentPathRelative)) {
            $recentFilesTime = microtime(true);
            $recentFiles = $searchManager->getRecentFiles(8, [$activeBaseKey]);
            error_log("Recent files time: " . number_format((microtime(true) - $recentFilesTime) * 1000, 2) . "ms");
        } else {
            $recentFiles = [];
        }
        */

        error_log("Total navigation time: " . number_format((microtime(true) - $navigationTime) * 1000, 2) . "ms");
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error en file manager: " . $e->getMessage());

    // Valores por defecto en caso de error
    $searchResults = [];
    $directoryContents = [];
    $breadcrumbs = [];
    $parentPath = null;
    $recentFiles = [];
}

// Mostrar la vista principal
$renderTime = microtime(true);
include 'templates/pages/file-browser.php';
error_log("Template render time: " . number_format((microtime(true) - $renderTime) * 1000, 2) . "ms");

$totalTime = microtime(true) - $startTime;
error_log("TOTAL EXECUTION TIME: " . number_format($totalTime * 1000, 2) . "ms");
?>