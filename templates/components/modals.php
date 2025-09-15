<!-- Copy and Rename Modal -->
<div id="copy-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-copy"></i>
                Asignar Nombre y Copiar Documento
            </h3>
        </div>

        <p class="mb-3">Se copiará el archivo: <strong id="original-filename-display"></strong></p>

        <div class="modal-grid">
            <div class="form-group">
                <label class="form-label">N° Documento *</label>
                <input type="text" id="doc_number" class="form-control" placeholder="Número de documento" required>
            </div>
            <div class="form-group">
                <label class="form-label">Placa *</label>
                <input type="text" id="license_plate" class="form-control" placeholder="Placa del vehículo" required>
            </div>
            <div class="form-group">
                <label class="form-label">Primer Nombre *</label>
                <input type="text" id="first_name" class="form-control" placeholder="Primer nombre" required>
            </div>
            <div class="form-group">
                <label class="form-label">Segundo Nombre</label>
                <input type="text" id="second_name" class="form-control" placeholder="Segundo nombre">
            </div>
            <div class="form-group">
                <label class="form-label">Primer Apellido *</label>
                <input type="text" id="first_lastname" class="form-control" placeholder="Primer apellido" required>
            </div>
            <div class="form-group">
                <label class="form-label">Segundo Apellido</label>
                <input type="text" id="second_lastname" class="form-control" placeholder="Segundo apellido">
            </div>
        </div>

        <div class="form-group">
            <label for="destination_base_select" class="form-label">Copiar a la carpeta:</label>
            <select id="destination_base_select" class="form-control">
                <?php foreach ($config['document_bases'] as $key => $details): ?>
                    <?php if ($key !== 'SCANNER' && $key !== 'docs'): ?>
                        <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($details['name']); ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="modal-buttons">
            <button class="btn btn-secondary" onclick="hideCopyModal()">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button class="btn btn-primary" onclick="submitCopyForm()">
                <i class="fas fa-copy"></i>
                Aceptar y Copiar
            </button>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form method="post" id="rename-form" style="display:none;" action="?path=<?php echo urlencode($currentPathRelative ?? ''); ?>&base=<?php echo $activeBaseKey ?? ''; ?>">
    <input type="hidden" name="old_name" id="old_name">
    <input type="hidden" name="new_name" id="new_name">
</form>

<form method="post" id="copy-rename-form" style="display:none;" action="?path=<?php echo urlencode($currentPathRelative ?? ''); ?>&base=<?php echo $activeBaseKey ?? ''; ?>">
    <input type="hidden" name="action" value="copy_and_rename">
    <input type="hidden" name="original_file_relative" id="original_file_relative">
    <input type="hidden" name="new_name_for_copy" id="new_name_for_copy">
    <input type="hidden" name="destination_base" id="destination_base">
</form>