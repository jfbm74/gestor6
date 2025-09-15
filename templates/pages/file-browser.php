<?php
$pageTitle = "Explorador de Archivos";
$showNavTabs = true;

// Pre-calcular valores costosos para optimizar el template
$currentUser = htmlspecialchars($authManager->getCurrentUser());
$activeBaseKeyEscaped = htmlspecialchars($activeBaseKey);

// Optimizar datos de archivos
if (!empty($directoryContents)) {
    foreach ($directoryContents as &$item) {
        $item['name_escaped'] = htmlspecialchars($item['name']);
        $item['path_encoded'] = urlencode($item['path']);
        $item['path_escaped'] = htmlspecialchars($item['path'], ENT_QUOTES);
        $item['size_formatted'] = $item['is_directory'] ? '-' : number_format($item['size'] / 1024, 1) . ' KB';
        if (!$item['is_directory']) {
            $extension = pathinfo($item['name'], PATHINFO_EXTENSION);
            $item['file_icon'] = $searchManager->getFileIcon($extension);
        }
    }
    unset($item); // Romper la referencia
}

// Optimizar archivos recientes
if (!empty($recentFiles)) {
    foreach ($recentFiles as &$file) {
        $file['full_path_encoded'] = urlencode($file['full_path']);
        $file['name_escaped'] = htmlspecialchars($file['name']);
    }
    unset($file);
}

include 'templates/layout/header.php';
?>

<!-- Search Section -->
<div class="search-container">
    <form method="get" class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text"
               name="search"
               class="search-input"
               placeholder="🔎 Buscar en todas las carpetas..."
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <input type="hidden" name="base" value="<?php echo $activeBaseKeyEscaped; ?>">
    </form>
</div>

<?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
    <!-- Search Results -->
    <div class="content-section">
        <div class="section-header" style="padding: 1.5rem;">
            <h2 class="section-title">
                <i class="fas fa-search"></i>
                Resultados para "<?php echo htmlspecialchars($_GET['search']); ?>"
            </h2>
            <a href="?base=<?php echo $activeBaseKeyEscaped; ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-arrow-left"></i>
                Volver a explorar
            </a>
        </div>

        <?php if (empty($searchResults)): ?>
        <div style="padding: 2rem; text-align: center; color: var(--gray-500);">
            <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p>No se encontraron resultados para tu búsqueda.</p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="file-table">
                <thead>
                    <tr>
                        <th>NOMBRE</th>
                        <th>UBICACIÓN</th>
                        <th>TAMAÑO</th>
                        <th>MODIFICADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($searchResults as $result): ?>
                    <tr class="file-row">
                        <td>
                            <div class="file-info">
                                <i class="fas <?php echo $result['is_directory'] ? 'fa-folder folder' : $searchManager->getFileIcon($result['extension']); ?> file-info-icon"></i>
                                <div class="file-info-details">
                                    <div class="file-info-name">
                                        <?php if ($result['is_directory']): ?>
                                            📁 <?php echo htmlspecialchars($result['name']); ?>
                                        <?php else: ?>
                                            <a href="?download=<?php echo urlencode($result['full_path']); ?>&is_absolute=1" target="_blank">
                                                📄 <?php echo htmlspecialchars($result['name']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted">
                                <?php echo htmlspecialchars($result['directory_path']); ?>
                                <strong>(<?php echo $result['base_name']; ?>)</strong>
                            </span>
                        </td>
                        <td><?php echo $result['size_formatted'] ?? '-'; ?></td>
                        <td><?php echo $result['modified_formatted']; ?></td>
                        <td>
                            <?php if (!$result['is_directory']): ?>
                            <a href="?download=<?php echo urlencode($result['full_path']); ?>&is_absolute=1"
                               target="_blank"
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Recent Files Section -->
    <?php if (!empty($recentFiles)): ?>
    <section class="recent-files">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-clock"></i>
                Archivos Recientes
            </h2>
        </div>
        <div class="recent-files-grid">
            <?php foreach (array_slice($recentFiles, 0, 6) as $file): ?>
            <div class="file-card" onclick="window.open('?download=<?php echo $file['full_path_encoded']; ?>&is_absolute=1', '_blank')">
                <div class="file-icon <?php echo $file['extension']; ?>">
                    <i class="fas <?php echo $searchManager->getFileIcon($file['extension']); ?>"></i>
                </div>
                <div class="file-name"><?php echo $file['name_escaped']; ?></div>
                <div class="file-size"><?php echo $file['size_formatted']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php if ($index > 0): ?>
                <span class="breadcrumb-separator">/</span>
            <?php endif; ?>
            <a href="<?php echo $crumb['url']; ?>" class="breadcrumb-item">
                <?php echo htmlspecialchars($crumb['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($activeBaseKey === 'SCANNER' && $authManager->hasPermission('upload')): ?>
    <!-- Modern Batch Upload Section -->
    <div class="modern-upload-section">
        <!-- Drop Zone -->
        <div class="modern-drop-zone" id="batch-drop-zone">
            <div class="drop-zone-inner">
                <div class="drop-zone-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 10l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 15V5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="drop-zone-text">
                    <h3>Arrastra tus archivos aquí</h3>
                    <p>o <span class="drop-link">haz clic para seleccionar</span></p>
                    <span class="drop-formats">Soporta PDF, JPG, PNG, DOC • Máx. 50MB</span>
                </div>
            </div>
            <input type="file"
                   id="batch-file-input"
                   multiple
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                   style="display: none;">
        </div>

        <!-- File Preview Cards (Initially Hidden) -->
        <div class="files-preview-section" id="batch-files-container" style="display: none;">
            <div class="preview-header">
                <div class="preview-title">
                    <h4>Archivos seleccionados</h4>
                    <span class="file-counter" id="file-count">0</span>
                </div>
                <div class="preview-actions">
                    <button type="button" class="btn-ghost" onclick="clearBatchFiles()">
                        <i class="fas fa-trash-alt"></i>
                        Limpiar todo
                    </button>
                    <button type="button" class="btn-primary" onclick="showBatchFormModal()" disabled id="process-batch-btn">
                        <i class="fas fa-arrow-right"></i>
                        Continuar
                    </button>
                </div>
            </div>
            <div class="files-grid" id="batch-files-grid">
                <!-- File cards will be dynamically added here -->
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- File List -->
    <div class="content-section">
        <div class="table-container">
            <table class="file-table">
                <thead>
                    <tr>
                        <th>NOMBRE</th>
                        <th>TAMAÑO</th>
                        <th>PROPIETARIO</th>
                        <th>ÚLTIMA ACTUALIZACIÓN</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Parent Directory Link -->
                    <?php if ($parentPath !== null): ?>
                    <tr class="file-row">
                        <td>
                            <div class="file-info">
                                <i class="fas fa-level-up-alt file-info-icon" style="color: var(--gray-500);"></i>
                                <div class="file-info-details">
                                    <div class="file-info-name">
                                        <a href="?base=<?php echo $activeBaseKeyEscaped; ?>&path=<?php echo urlencode($parentPath); ?>">
                                            ⬆️ Subir un nivel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                    <?php endif; ?>

                    <!-- Directory Contents -->
                    <?php foreach ($directoryContents as $item): ?>
                    <tr class="file-row">
                        <td>
                            <div class="file-info">
                                <i class="fas <?php echo $item['is_directory'] ? 'fa-folder folder' : $item['file_icon']; ?> file-info-icon"></i>
                                <div class="file-info-details">
                                    <div class="file-info-name">
                                        <?php if ($item['is_directory']): ?>
                                            <a href="?base=<?php echo $activeBaseKeyEscaped; ?>&path=<?php echo $item['path_encoded']; ?>">
                                                📁 <?php echo $item['name_escaped']; ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="?download=<?php echo $item['path_encoded']; ?>&base=<?php echo $activeBaseKeyEscaped; ?>" target="_blank">
                                                📄 <?php echo $item['name_escaped']; ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $item['size_formatted']; ?></td>
                        <td><?php echo $currentUser; ?></td>
                        <td><?php echo $item['modified_formatted']; ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($activeBaseKey === 'SCANNER' && !$item['is_directory'] && $authManager->hasPermission('upload')): ?>
                                    <button class="btn btn-outline-success btn-sm"
                                            onclick="showCopyModal('<?php echo $item['path_escaped']; ?>', '<?php echo $item['name_escaped']; ?>')">
                                        <i class="fas fa-copy"></i>
                                        Copiar
                                    </button>
                                <?php endif; ?>

                                <?php if (!$item['is_directory'] && strtolower(pathinfo($item['name'], PATHINFO_EXTENSION)) === 'pdf' && $authManager->hasPermission('upload')): ?>
                                    <button class="btn btn-outline-info btn-sm"
                                            onclick="extractTextOCR('<?php echo $item['path_escaped']; ?>', '<?php echo $item['name_escaped']; ?>')">
                                        <i class="fas fa-text-width"></i>
                                        Extraer Texto
                                    </button>
                                <?php endif; ?>

                                <?php if ($authManager->hasPermission('admin')): ?>
                                    <button class="btn btn-outline-primary btn-sm"
                                            onclick="renameItem('<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-edit"></i>
                                        Renombrar
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('¿Estás seguro de eliminar permanentemente este elemento?') && (window.location.href='?base=<?php echo $activeBaseKey; ?>&path=<?php echo urlencode($currentPathRelative); ?>&delete=<?php echo urlencode($item['name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                        Eliminar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Admin Forms -->
    <?php if (($authManager->hasPermission('upload') || $authManager->hasPermission('admin')) && $activeBaseKey !== 'SCANNER'): ?>
    <div class="admin-section">
        <h3><i class="fas fa-tools"></i> Herramientas de Administración</h3>
        <div class="admin-forms">
            <?php if ($authManager->hasPermission('upload')): ?>
            <div class="admin-form">
                <h4><i class="fas fa-upload"></i> Subir Archivo</h4>
                <form method="post" enctype="multipart/form-data" action="?path=<?php echo urlencode($currentPathRelative); ?>&base=<?php echo $activeBaseKey; ?>">
                    <div class="form-group">
                        <input type="file" name="newfile" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i>
                        Subir Archivo
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($authManager->hasPermission('admin')): ?>
            <div class="admin-form">
                <h4><i class="fas fa-folder-plus"></i> Crear Carpeta</h4>
                <form method="post" action="?path=<?php echo urlencode($currentPathRelative); ?>&base=<?php echo $activeBaseKey; ?>">
                    <div class="form-group">
                        <input type="text" name="new_folder" class="form-control" placeholder="Nombre de la carpeta" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-folder-plus"></i>
                        Crear Carpeta
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php include 'templates/components/modals.php'; ?>
<?php include 'templates/layout/footer.php'; ?>