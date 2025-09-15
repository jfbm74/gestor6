<?php
/**
 * Sistema de Gestión de Documentos - Clínica Bonsana
 * Punto de entrada principal del sistema modular
 */

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
$config = require 'config/config.php';

// Inicializar componentes principales
$pathValidator = new PathValidator(array_column($config['document_bases'], 'path'));
$sessionManager = new SessionManager($config);
$authManager = new AuthManager($config, $sessionManager);

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
$directoryManager = new DirectoryManager($pathValidator, $config['document_bases']);
$fileManager = new FileManager($pathValidator, $config['document_bases'], $config);
$searchManager = new SearchManager($config['document_bases'], $pathValidator);

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
        $searchResults = [];
        $directoryContents = $directoryManager->getDirectoryContents($activeBaseKey, $currentPathRelative);
        $breadcrumbs = $directoryManager->getBreadcrumbs($activeBaseKey, $currentPathRelative);
        $parentPath = $directoryManager->getParentPath($currentPathRelative);

        // Obtener archivos recientes solo en la página principal
        if (empty($currentPathRelative)) {
            $recentFiles = $searchManager->getRecentFiles(8, [$activeBaseKey]);
        } else {
            $recentFiles = [];
        }
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
include 'templates/pages/file-browser.php';
?>