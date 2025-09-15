<?php
session_start();

// ===================================================================
// --- CONFIGURACIÓN ---
// ===================================================================
$users = [
    'lector'     => ['password' => '1234', 'role' => 'lectura'],
    'cargas'     => ['password' => 'subir', 'role' => 'subir'],
    'superadmin' => ['password' => 'admin', 'role' => 'super']
];

// *** RUTAS ACTUALIZADAS ***
$available_bases = [
    'docs' => [
        'name' => 'Documentos Antiguos',
        'path' => 'F:\DOCUMENTOSSCANEADOSANTIGUOS'
    ],
    'DOCUMENTOSSCANEADOS' => [
        'name' => 'DOCUMENTOSSCANEADOS',
        'path' => 'F:\DOCUMENTOSSCANEADOS\documentos escaneados'
    ],
    'SCANNER' => [
        'name' => 'SCANNER',
        'path' => 'F:\SCANNER'
    ],
];

$search_directories = array_column($available_bases, 'path');

// ===================================================================
// --- LÓGICA PRINCIPAL ---
// ===================================================================
$active_base_key = key($available_bases);
if (isset($_GET['base']) && isset($available_bases[$_GET['base']])) {
    $active_base_key = $_GET['base'];
}
$base_directory = $available_bases[$active_base_key]['path'];

if (isset($_GET['download']) && isset($_SESSION['user'])) {
    $file_path = trim($_GET['download']);
    if (isset($_GET['is_absolute']) && $_GET['is_absolute'] == '1') {
        $file_full = realpath($file_path);
        $is_allowed = false;
        foreach ($search_directories as $allowed_dir) {
            if ($file_full && strpos($file_full, realpath($allowed_dir)) === 0) {
                $is_allowed = true;
                break;
            }
        }
        if ($is_allowed && is_file($file_full)) {
            header('Content-Type: ' . mime_content_type($file_full));
            header('Content-Disposition: inline; filename="' . basename($file_full) . '"');
            readfile($file_full);
            exit;
        } else {
            die("Acceso denegado o archivo no encontrado.");
        }
    } else {
        $file_full = realpath($base_directory . '/' . $file_path);
        if ($file_full && strpos($file_full, realpath($base_directory)) === 0 && is_file($file_full)) {
            header('Content-Type: ' . mime_content_type($file_full));
            header('Content-Disposition: inline; filename="' . basename($file_full) . '"');
            readfile($file_full);
            exit;
        } else {
            die("Acceso denegado o archivo no encontrado.");
        }
    }
}

$current_path_relative = '';
if (isset($_GET['path']) && !isset($_GET['search'])) { $current_path_relative = trim($_GET['path'], '/'); }
$current_path_full = realpath($base_directory . '/' . $current_path_relative);
if ($current_path_full === false || strpos($current_path_full, realpath($base_directory)) !== 0) {
    $current_path_relative = '';
    $current_path_full = realpath($base_directory);
}

if (isset($_POST['username']) && isset($_POST['password'])) { $username = $_POST['username']; if (isset($users[$username]) && $users[$username]['password'] === $_POST['password']) { $_SESSION['user'] = $username; $_SESSION['role'] = $users[$username]['role']; } else { $login_error = "Usuario o contraseña incorrectos."; } }
if (isset($_GET['logout'])) { session_destroy(); header('Location: index.php'); exit; }

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$redirect_url = '?path=' . urlencode($current_path_relative) . '&base=' . $active_base_key;

if (isset($_GET['delete']) && $role === 'super') {
    $item_to_delete = realpath($current_path_full . '/' . basename($_GET['delete']));
    if ($item_to_delete && strpos($item_to_delete, $current_path_full) === 0) {
        function delete_recursive($path) { if (is_file($path)) return unlink($path); if (is_dir($path)) { $files = array_diff(scandir($path), ['.', '..']); foreach ($files as $file) { delete_recursive("$path/$file"); } return rmdir($path); } }
        delete_recursive($item_to_delete);
    }
    header('Location: ' . $redirect_url); exit;
}

if (isset($_FILES['newfile']) && ($role === 'super' || $role === 'subir')) {
    $target_path = $current_path_full . '/' . basename($_FILES['newfile']['name']);
    move_uploaded_file($_FILES['newfile']['tmp_name'], $target_path);
    header('Location: ' . $redirect_url); exit;
}

if (isset($_POST['new_folder']) && !empty($_POST['new_folder']) && $role === 'super') {
    $new_folder_name = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $_POST['new_folder']);
    if (!empty($new_folder_name)) { mkdir($current_path_full . '/' . $new_folder_name); }
    header('Location: ' . $redirect_url); exit;
}

if (isset($_POST['old_name']) && isset($_POST['new_name']) && !empty($_POST['new_name']) && $role === 'super') {
    $old_path = realpath($current_path_full . '/' . $_POST['old_name']);
    $new_name_clean = preg_replace('/[^A-Za-z0-9\-_\. ]/', '', $_POST['new_name']);
    if ($old_path && strpos($old_path, $current_path_full) === 0 && !empty($new_name_clean)) {
        $new_path = $current_path_full . '/' . $new_name_clean;
        rename($old_path, $new_path);
    }
    header('Location: ' . $redirect_url); exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'copy_and_rename' && ($role === 'super' || $role === 'subir')) {
    $original_file_relative = $_POST['original_file_relative'];
    $new_name_for_copy = $_POST['new_name_for_copy'];
    $destination_base_key = $_POST['destination_base'];

    if (isset($available_bases[$destination_base_key])) {
        $new_name_clean = preg_replace('/[^A-Za-z0-9\-_ \.()]/', '', $new_name_for_copy);
        if (!empty($new_name_clean)) {
            $source_base_path = $available_bases['SCANNER']['path'];
            $source_full_path = realpath($source_base_path . '/' . $original_file_relative);
            if ($source_full_path && strpos($source_full_path, realpath($source_base_path)) === 0 && is_file($source_full_path)) {
                $destination_path = $available_bases[$destination_base_key]['path'];
                $destination_full_path = $destination_path . '/' . $new_name_clean;
                copy($source_full_path, $destination_full_path);
            }
        }
    }
    header('Location: ' . $redirect_url);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor de Documentos</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; background-color: #f4f4f4; color: #333; }
        .container { max-width: 900px; margin: 40px auto; padding: 20px; background-color: #fff; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .login-form { text-align: center; }
        .base-nav { margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .base-nav a { text-decoration: none; color: #555; padding: 10px 15px; font-size: 16px; margin-right: 5px; border-radius: 5px 5px 0 0; }
        .base-nav a.active { font-weight: bold; color: #333; border-bottom: 3px solid #3498db; }
        .base-nav a:hover { background-color: #f0f0f0; }
        .file-list { list-style: none; padding: 0; }
        .file-list li { padding: 12px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; transition: background-color 0.2s; }
        .file-list li:last-child { border-bottom: none; }
        .file-list li:hover { background-color: #f9f9f9; }
        .file-list a { text-decoration: none; color: #333; }
        .file-details { flex-grow: 1; display: flex; align-items: center; }
        .mod-date { font-size: 0.85em; color: #777; margin-left: 20px; white-space: nowrap; }
        .file-list .path { font-size: 0.8em; color: #888; margin-left: 10px; white-space: nowrap; }
        .actions a { text-decoration: none; font-weight: bold; padding: 5px 10px; border-radius: 5px; border: 1px solid; margin-left: 10px; transition: background-color 0.2s, color 0.2s; white-space: nowrap; }
        .rename-btn { color: #3498db; border-color: #3498db; }
        .rename-btn:hover { background-color: #3498db; color: #fff; }
        .delete-btn { color: #e74c3c; border-color: #e74c3c; }
        .delete-btn:hover { background-color: #e74c3c; color: #fff; }
        .copy-btn { color: #27ae60; border-color: #27ae60; }
        .copy-btn:hover { background-color: #27ae60; color: #fff; }
        .search-form input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; margin-bottom: 20px; box-sizing: border-box; }
        .breadcrumbs { margin-bottom: 20px; font-size: 14px; color: #555; }
        .breadcrumbs a { color: #3498db; text-decoration: none; }
        .admin-forms { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); width: 90%; max-width: 600px; }
        .modal-content h3 { margin-top: 0; }
        .modal-content input, .modal-content select, .modal-content button { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .modal-buttons { text-align: right; margin-top: 20px; }
        .modal-buttons button { width: auto; margin-left: 10px; cursor: pointer; }
        .modal-buttons .btn-primary { background-color: #3498db; color: white; border-color: #3498db; }
        .modal-buttons .btn-secondary { background-color: #bdc3c7; color: white; border-color: #bdc3c7; }
    </style>
</head>
<body>

<div class="container">
    <?php if (isset($_SESSION['user'])): ?>
        <div class="header">
            <h2>Gestor de Documentos</h2>
            <span class="user-info">Hola, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong> | <a href="?logout=true">Cerrar Sesión</a></span>
        </div>
        <div class="base-nav">
            <?php foreach ($available_bases as $key => $details): ?>
                <a href="?base=<?php echo $key; ?>" class="<?php echo ($key === $active_base_key) ? 'active' : ''; ?>"><?php echo htmlspecialchars($details['name']); ?></a>
            <?php endforeach; ?>
        </div>
        <form class="search-form" method="get">
            <input type="text" name="search" placeholder="🔎 Buscar en TODAS las carpetas..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </form>
        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
            <h3>Resultados para "<?php echo htmlspecialchars($_GET['search']); ?>"</h3>
            <ul class="file-list">
            <?php
                // (Código de búsqueda sin cambios)
                $query = $_GET['search'];
                $results = [];
                foreach ($search_directories as $directory_to_search) {
                    if (!is_dir($directory_to_search)) continue;
                    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory_to_search, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($iterator as $file) {
                        if (stripos($file->getFilename(), $query) !== false) {
                            $result_item = new stdClass(); $result_item->fileInfo = $file; $result_item->baseDir = $directory_to_search; $results[] = $result_item;
                        }
                    }
                }
                if (empty($results)) { echo "<li><span>No se encontraron resultados.</span></li>"; } else {
                    foreach ($results as $result) {
                        $file_info = $result->fileInfo; $base_dir_of_file = $result->baseDir;
                        $relative_path_for_display = ltrim(str_replace(realpath($base_dir_of_file), '', $file_info->getRealPath()), '\\/');
                        echo '<li>';
                        if ($file_info->isDir()) {
                            echo '<span>📁 ' . htmlspecialchars($file_info->getFilename()) . '</span>';
                        } else {
                            echo '<a href="?download=' . urlencode($file_info->getRealPath()) . '&is_absolute=1" target="_blank">📄 ' . htmlspecialchars($file_info->getFilename()) . '</a>';
                        }
                        echo '<span class="path">' . htmlspecialchars(dirname($relative_path_for_display)) . ' <strong>(en ' . basename($base_dir_of_file) . ')</strong></span>';
                        echo '</li>';
                    }
                }
            ?>
            </ul>
            <a href="?base=<?php echo $active_base_key; ?>" style="display:inline-block; margin-top: 20px;">← Volver a la vista de carpetas</a>
        <?php else: ?>
            <div class="breadcrumbs">
                <a href="?path=&base=<?php echo $active_base_key; ?>"><?php echo htmlspecialchars($available_bases[$active_base_key]['name']); ?></a> /
                <?php
                $path_parts = explode('/', $current_path_relative);
                $path_so_far = '';
                foreach ($path_parts as $part) { if (empty($part)) continue; $path_so_far .= $part . '/'; echo '<a href="?path=' . urlencode($path_so_far) . '&base=' . $active_base_key . '">' . htmlspecialchars($part) . '</a> / '; }
                ?>
            </div>
            <ul class="file-list">
                <?php
                if (!empty($current_path_relative)) { $parent_path = dirname($current_path_relative); if ($parent_path === '.') $parent_path = ''; echo '<li><a href="?path=' . urlencode($parent_path) . '&base=' . $active_base_key . '">⬆️ ... Subir un nivel</a></li>'; }
                if (is_readable($current_path_full)) {
                    $items = scandir($current_path_full);
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;
                        $item_path_full = $current_path_full . '/' . $item;
                        $item_path_relative = $current_path_relative ? $current_path_relative . '/' . $item : $item;
                        $mod_date = date("d/m/Y H:i", filemtime($item_path_full));
                        echo '<li>';
                        echo '<div class="file-details">';
                        if (is_dir($item_path_full)) {
                            echo '<a href="?path=' . urlencode($item_path_relative) . '&base=' . $active_base_key . '">📁 ' . htmlspecialchars($item) . '</a>';
                        } else {
                            echo '<a href="?download=' . urlencode($item_path_relative) . '&base=' . $active_base_key . '" target="_blank">📄 ' . htmlspecialchars($item) . '</a>';
                        }
                        echo '<span class="mod-date">' . $mod_date . '</span>';
                        echo '</div>';
                        if ($role === 'super' || $role === 'subir') {
                            echo '<div class="actions">';
                            if ($active_base_key === 'SCANNER' && !is_dir($item_path_full)) {
                                echo '<a href="#" class="copy-btn" onclick="showCopyModal(\'' . htmlspecialchars($item_path_relative, ENT_QUOTES) . '\', \'' . htmlspecialchars($item, ENT_QUOTES) . '\'); return false;">Copiar a...</a>';
                            }
                            if ($role === 'super') {
                                echo '<a href="#" class="rename-btn" onclick="renameItem(\'' . htmlspecialchars($item, ENT_QUOTES) . '\'); return false;">Renombrar</a>';
                                echo '<a href="?path=' . urlencode($current_path_relative) . '&delete=' . urlencode($item) . '&base=' . $active_base_key . '" class="delete-btn" onclick="return confirm(\'¿Estás seguro de ELIMINAR permanentemente este elemento?\');">Eliminar</a>';
                            }
                            echo '</div>';
                        }
                        echo '<li>';
                    }
                } else {
                    echo "<li>Error: No se puede acceder a la carpeta. Verifique que la ruta y los permisos son correctos.</li>";
                }
                ?>
            </ul>
            <div class="admin-forms">
                <?php if ($role === 'subir' || $role === 'super'): ?>
                <div class="upload-form">
                    <h3>Subir archivo a esta carpeta</h3>
                    <form method="post" enctype="multipart/form-data" action="?path=<?php echo urlencode($current_path_relative); ?>&base=<?php echo $active_base_key; ?>">
                        <input type="file" name="newfile" required> <button type="submit">Subir</button>
                    </form>
                </div>
                <?php endif; ?>
                <?php if ($role === 'super'): ?>
                <div class="new-folder-form">
                    <h3>Crear nueva carpeta aquí</h3>
                    <form method="post" action="?path=<?php echo urlencode($current_path_relative); ?>&base=<?php echo $active_base_key; ?>">
                        <input type="text" name="new_folder" placeholder="Nombre de la carpeta" required> <button type="submit">Crear</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="login-form">
            <h2>Iniciar Sesión</h2>
            <form method="post">
                <p><input type="text" name="username" placeholder="Usuario" required></p>
                <p><input type="password" name="password" placeholder="Contraseña" required></p>
                <p><button type="submit">Entrar</button></p>
            </form>
             <?php if(isset($login_error)) { echo '<p style="color:red;">' . $login_error . '</p>'; } ?>
        </div>
    <?php endif; ?>
</div>

<form method="post" id="rename-form" style="display:none;" action="?path=<?php echo urlencode($current_path_relative); ?>&base=<?php echo $active_base_key; ?>">
    <input type="hidden" name="old_name" id="old_name">
    <input type="hidden" name="new_name" id="new_name">
</form>

<div id="copy-modal" class="modal-overlay">
    <div class="modal-content">
        <h3>Asignar Nombre y Copiar Documento</h3>
        <p>Se copiará el archivo: <strong id="original-filename-display"></strong></p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <input type="text" id="doc_number" placeholder="N° Documento*" required>
            <input type="text" id="license_plate" placeholder="Placa*" required>
            <input type="text" id="first_name" placeholder="Primer Nombre*" required>
            <input type="text" id="second_name" placeholder="Segundo Nombre">
            <input type="text" id="first_lastname" placeholder="Primer Apellido*" required>
            <input type="text" id="second_lastname" placeholder="Segundo Apellido">
        </div>
        <label for="destination_base_select" style="margin-top: 15px; display: block;">Copiar a la carpeta:</label>
        <select id="destination_base_select">
            <?php
            foreach ($available_bases as $key => $details) {
                // *** LÍNEA MODIFICADA PARA QUITAR 'Documentos Antiguos' ***
                if ($key !== 'SCANNER' && $key !== 'docs') {
                    echo '<option value="' . $key . '">' . htmlspecialchars($details['name']) . '</option>';
                }
            }
            ?>
        </select>
        <div class="modal-buttons">
            <button class="btn-secondary" onclick="hideCopyModal(); return false;">Cancelar</button>
            <button class="btn-primary" onclick="submitCopyForm();">Aceptar y Copiar</button>
        </div>
    </div>
</div>

<form method="post" id="copy-rename-form" style="display:none;" action="?path=<?php echo urlencode($current_path_relative); ?>&base=<?php echo $active_base_key; ?>">
    <input type="hidden" name="action" value="copy_and_rename">
    <input type="hidden" name="original_file_relative" id="original_file_relative">
    <input type="hidden" name="new_name_for_copy" id="new_name_for_copy">
    <input type="hidden" name="destination_base" id="destination_base">
</form>

<script>
function renameItem(oldName) {
    const newName = prompt("Introduce el nuevo nombre para '" + oldName + "':", oldName);
    if (newName && newName !== oldName) {
        document.getElementById('old_name').value = oldName;
        document.getElementById('new_name').value = newName;
        document.getElementById('rename-form').submit();
    }
}

const copyModal = document.getElementById('copy-modal');
let originalFileNameForExtension = '';

function showCopyModal(relativePath, originalName) {
    document.getElementById('doc_number').value = '';
    document.getElementById('license_plate').value = '';
    document.getElementById('first_name').value = '';
    document.getElementById('second_name').value = '';
    document.getElementById('first_lastname').value = '';
    document.getElementById('second_lastname').value = '';
    
    originalFileNameForExtension = originalName;
    document.getElementById('original-filename-display').textContent = originalName;
    document.getElementById('original_file_relative').value = relativePath;
    
    copyModal.style.display = 'flex';
}

function hideCopyModal() {
    copyModal.style.display = 'none';
}

function submitCopyForm() {
    const docNumber = document.getElementById('doc_number').value.trim();
    const firstName = document.getElementById('first_name').value.trim();
    const secondName = document.getElementById('second_name').value.trim();
    const firstLastname = document.getElementById('first_lastname').value.trim();
    const secondLastname = document.getElementById('second_lastname').value.trim();
    const licensePlate = document.getElementById('license_plate').value.trim();
    if (!docNumber || !firstName || !firstLastname || !licensePlate) { // Placa ahora es requerida
        alert('Por favor, completa los campos requeridos (*).');
        return;
    }
    const filename_parts = [
        docNumber,
        firstName,
        secondName,
        firstLastname,
        secondLastname,
        licensePlate
    ];
    const newNameBase = filename_parts
        .filter(Boolean)
        .join('_')
        .toUpperCase()
        .replace(/\s+/g, '_');
    const extension = originalFileNameForExtension.includes('.')
        ? originalFileNameForExtension.substring(originalFileNameForExtension.lastIndexOf('.'))
        : '';
    const finalNewName = newNameBase + extension;
    document.getElementById('new_name_for_copy').value = finalNewName;
    document.getElementById('destination_base').value = document.getElementById('destination_base_select').value;
    document.getElementById('copy-rename-form').submit();
}
</script>

</body>
</html>