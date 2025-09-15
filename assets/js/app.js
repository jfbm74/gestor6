/**
 * Sistema de Gestión de Documentos - Clínica Bonsana
 * JavaScript para interacciones del usuario
 */

// Variables globales
let originalFileNameForExtension = '';
const copyModal = document.getElementById('copy-modal');

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeFileManager();
});

/**
 * Inicializa el file manager
 */
function initializeFileManager() {
    // Mejorar la experiencia de búsqueda
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    }

    // Añadir efectos hover a las tarjetas de archivos
    const fileCards = document.querySelectorAll('.file-card');
    fileCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Inicializar tooltips para botones de acción
    initializeTooltips();

    // Inicializar controles de PDF
    initializePdfControls();

    // Inicializar batch upload drag & drop
    initializeBatchUpload();

    // Cerrar modal al hacer click fuera
    if (copyModal) {
        copyModal.addEventListener('click', function(e) {
            if (e.target === this) {
                hideCopyModal();
            }
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && copyModal.style.display === 'flex') {
                hideCopyModal();
            }
        });
    }
}

/**
 * Inicializa tooltips para botones
 */
function initializeTooltips() {
    const buttons = document.querySelectorAll('.btn[title]');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            showTooltip(this, this.getAttribute('title'));
        });

        button.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

/**
 * Función para renombrar elementos
 */
function renameItem(oldName) {
    const newName = prompt("Introduce el nuevo nombre para '" + oldName + "':", oldName);

    if (newName && newName !== oldName && newName.trim() !== '') {
        // Validar el nuevo nombre
        if (!isValidFileName(newName)) {
            showNotification('El nombre contiene caracteres no permitidos', 'error');
            return;
        }

        document.getElementById('old_name').value = oldName;
        document.getElementById('new_name').value = newName.trim();
        document.getElementById('rename-form').submit();
    }
}

/**
 * Muestra el modal de copia y renombrado con preview del PDF
 */
function showCopyModal(relativePath, originalName) {
    // Limpiar campos
    document.getElementById('doc_number').value = '';
    document.getElementById('license_plate').value = '';
    document.getElementById('first_name').value = '';
    document.getElementById('second_name').value = '';
    document.getElementById('first_lastname').value = '';
    document.getElementById('second_lastname').value = '';

    // Configurar datos del archivo original
    originalFileNameForExtension = originalName;
    document.getElementById('original-filename-display').textContent = originalName;
    document.getElementById('original_file_relative').value = relativePath;

    // Cargar PDF preview
    loadPdfPreview(relativePath, originalName);

    // Mostrar modal
    copyModal.style.display = 'flex';

    // Focus en el primer campo después de un pequeño delay
    setTimeout(() => {
        document.getElementById('doc_number').focus();
    }, 100);
}

/**
 * Carga la vista previa del PDF
 */
function loadPdfPreview(relativePath, fileName) {
    const pdfViewer = document.getElementById('pdf-viewer');
    const pdfLoading = document.getElementById('pdf-loading');
    const pdfError = document.getElementById('pdf-error');

    // Mostrar indicador de carga
    pdfLoading.style.display = 'flex';
    pdfError.style.display = 'none';
    pdfViewer.style.display = 'none';

    // Verificar si es un PDF
    const extension = fileName.toLowerCase().split('.').pop();
    if (extension !== 'pdf') {
        showPdfError('Este archivo no es un PDF');
        return;
    }

    // Construir URL del PDF
    const urlParams = new URLSearchParams(window.location.search);
    const currentBase = urlParams.get('base') || 'SCANNER';
    const pdfUrl = `?download=${encodeURIComponent(relativePath)}&base=${currentBase}`;

    // Configurar el iframe
    pdfViewer.onload = function() {
        pdfLoading.style.display = 'none';
        pdfViewer.style.display = 'block';
    };

    pdfViewer.onerror = function() {
        showPdfError('Error al cargar el documento PDF');
    };

    // Cargar el PDF
    pdfViewer.src = pdfUrl;

    // Timeout para detectar errores de carga
    setTimeout(() => {
        if (pdfLoading.style.display === 'flex') {
            showPdfError('Tiempo de carga agotado');
        }
    }, 10000); // 10 segundos timeout
}

/**
 * Muestra error en el visor de PDF
 */
function showPdfError(message) {
    const pdfViewer = document.getElementById('pdf-viewer');
    const pdfLoading = document.getElementById('pdf-loading');
    const pdfError = document.getElementById('pdf-error');

    pdfViewer.style.display = 'none';
    pdfLoading.style.display = 'none';
    pdfError.style.display = 'flex';
    pdfError.querySelector('p').textContent = message;
}

/**
 * Controles de zoom para el PDF
 */
let currentZoom = 100;

function initializePdfControls() {
    const zoomInBtn = document.getElementById('zoom-in');
    const zoomOutBtn = document.getElementById('zoom-out');
    const zoomLevel = document.getElementById('zoom-level');

    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function() {
            if (currentZoom < 200) {
                currentZoom += 25;
                updatePdfZoom();
            }
        });
    }

    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function() {
            if (currentZoom > 50) {
                currentZoom -= 25;
                updatePdfZoom();
            }
        });
    }
}

function updatePdfZoom() {
    const pdfViewer = document.getElementById('pdf-viewer');
    const zoomLevel = document.getElementById('zoom-level');

    if (pdfViewer && zoomLevel) {
        pdfViewer.style.transform = `scale(${currentZoom / 100})`;
        pdfViewer.style.transformOrigin = 'top left';
        zoomLevel.textContent = currentZoom + '%';
    }
}

/**
 * Oculta el modal de copia
 */
function hideCopyModal() {
    copyModal.style.display = 'none';

    // Reset PDF viewer
    const pdfViewer = document.getElementById('pdf-viewer');
    if (pdfViewer) {
        pdfViewer.src = '';
        pdfViewer.style.transform = 'scale(1)';
    }

    // Reset zoom
    currentZoom = 100;
    const zoomLevel = document.getElementById('zoom-level');
    if (zoomLevel) {
        zoomLevel.textContent = '100%';
    }
}

/**
 * Envía el formulario de copia y renombrado
 */
function submitCopyForm() {
    // Obtener valores de los campos
    const docNumber = document.getElementById('doc_number').value.trim();
    const firstName = document.getElementById('first_name').value.trim();
    const secondName = document.getElementById('second_name').value.trim();
    const firstLastname = document.getElementById('first_lastname').value.trim();
    const secondLastname = document.getElementById('second_lastname').value.trim();
    const licensePlate = document.getElementById('license_plate').value.trim();

    // Validar campos requeridos
    if (!docNumber || !firstName || !firstLastname || !licensePlate) {
        showNotification('Por favor, completa los campos requeridos (*).', 'error');
        return;
    }

    // Validar formato de placa (opcional - puedes personalizar según necesidades)
    if (!isValidLicensePlate(licensePlate)) {
        showNotification('Formato de placa no válido', 'error');
        return;
    }

    // Crear el nombre del archivo
    const filenameParts = [
        docNumber,
        firstName,
        secondName,
        firstLastname,
        secondLastname,
        licensePlate
    ];

    const newNameBase = filenameParts
        .filter(Boolean) // Eliminar campos vacíos
        .join('_')
        .toUpperCase()
        .replace(/\s+/g, '_'); // Reemplazar espacios con guiones bajos

    // Obtener extensión del archivo original
    const extension = originalFileNameForExtension.includes('.')
        ? originalFileNameForExtension.substring(originalFileNameForExtension.lastIndexOf('.'))
        : '';

    const finalNewName = newNameBase + extension;

    // Validar longitud del nombre
    if (finalNewName.length > 255) {
        showNotification('El nombre del archivo es demasiado largo', 'error');
        return;
    }

    // Configurar y enviar formulario
    document.getElementById('new_name_for_copy').value = finalNewName;
    document.getElementById('destination_base').value = document.getElementById('destination_base_select').value;

    // Mostrar indicador de carga
    showLoadingIndicator();

    document.getElementById('copy-rename-form').submit();
}

/**
 * Valida si un nombre de archivo es válido
 */
function isValidFileName(fileName) {
    // Caracteres no permitidos en nombres de archivo
    const invalidChars = /[<>:"/\\|?*\x00-\x1f]/;
    return !invalidChars.test(fileName) && fileName.length > 0 && fileName.length <= 255;
}

/**
 * Valida formato de placa (personalizable según región)
 */
function isValidLicensePlate(plate) {
    // Ejemplo básico - puedes personalizar según las reglas de tu región
    const platePattern = /^[A-Z0-9-]{3,10}$/i;
    return platePattern.test(plate);
}

/**
 * Muestra notificaciones al usuario
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    // Añadir estilos si no existen
    if (!document.getElementById('notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 9999;
                max-width: 400px;
                animation: slideInRight 0.3s ease-out;
            }
            .notification-info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
            .notification-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
            .notification-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
            .notification-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
            .notification-content { display: flex; align-items: center; gap: 0.5rem; }
            .notification-close { background: none; border: none; cursor: pointer; margin-left: auto; }
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(styles);
    }

    // Añadir al DOM
    document.body.appendChild(notification);

    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Obtiene el icono apropiado para el tipo de notificación
 */
function getNotificationIcon(type) {
    const icons = {
        info: 'fa-info-circle',
        success: 'fa-check-circle',
        warning: 'fa-exclamation-triangle',
        error: 'fa-times-circle'
    };
    return icons[type] || icons.info;
}

/**
 * Muestra indicador de carga
 */
function showLoadingIndicator() {
    const loader = document.createElement('div');
    loader.id = 'loading-indicator';
    loader.innerHTML = `
        <div class="loading-overlay">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Procesando...</p>
            </div>
        </div>
    `;

    // Añadir estilos si no existen
    if (!document.getElementById('loading-styles')) {
        const styles = document.createElement('style');
        styles.id = 'loading-styles';
        styles.textContent = `
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            }
            .loading-spinner {
                background: white;
                padding: 2rem;
                border-radius: 8px;
                text-align: center;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .loading-spinner i {
                font-size: 2rem;
                color: var(--primary-color);
                margin-bottom: 1rem;
            }
            .loading-spinner p {
                margin: 0;
                color: var(--gray-600);
            }
        `;
        document.head.appendChild(styles);
    }

    document.body.appendChild(loader);
}

/**
 * Oculta el indicador de carga
 */
function hideLoadingIndicator() {
    const loader = document.getElementById('loading-indicator');
    if (loader) {
        loader.remove();
    }
}

/**
 * Confirma eliminación de archivos
 */
function confirmDelete(itemName, deleteUrl) {
    if (confirm(`¿Estás seguro de eliminar permanentemente "${itemName}"?`)) {
        showLoadingIndicator();
        window.location.href = deleteUrl;
    }
}

/**
 * Previsualización de archivos (opcional)
 */
function previewFile(filePath, fileName) {
    const extension = fileName.split('.').pop().toLowerCase();

    if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
        showImagePreview(filePath, fileName);
    } else if (extension === 'pdf') {
        window.open(filePath, '_blank');
    } else {
        window.open(filePath, '_blank');
    }
}

/**
 * Muestra vista previa de imágenes
 */
function showImagePreview(imagePath, imageName) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 90%; max-height: 90%;">
            <div class="modal-header">
                <h3>${imageName}</h3>
                <button class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="text-align: center;">
                <img src="${imagePath}" alt="${imageName}" style="max-width: 100%; max-height: 70vh; object-fit: contain;">
            </div>
        </div>
    `;

    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.remove();
        }
    });

    document.body.appendChild(modal);
    modal.style.display = 'flex';
}

// Funciones de utilidad para mejorar la experiencia del usuario

/**
 * Formatea el tamaño de archivo de manera legible
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';

    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

/**
 * Debounce para búsquedas
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// === BATCH UPLOAD FUNCTIONALITY ===

let batchFiles = [];
let fileCounter = 0;

/**
 * Inicializa la funcionalidad de batch upload
 */
function initializeBatchUpload() {
    const dropZone = document.getElementById('batch-drop-zone');
    const fileInput = document.getElementById('batch-file-input');

    if (!dropZone || !fileInput) return;

    // Click en la zona de drop abre el selector de archivos
    dropZone.addEventListener('click', function(e) {
        fileInput.click();
    });

    // Drag and drop events
    dropZone.addEventListener('dragover', handleDragOver);
    dropZone.addEventListener('dragenter', handleDragEnter);
    dropZone.addEventListener('dragleave', handleDragLeave);
    dropZone.addEventListener('drop', handleDrop);

    // File input change
    fileInput.addEventListener('change', handleFileSelect);

    // Prevent default drag behaviors on document
    document.addEventListener('dragover', preventDefault);
    document.addEventListener('drop', preventDefault);
}

/**
 * Previene comportamientos por defecto del drag
 */
function preventDefault(e) {
    e.preventDefault();
    e.stopPropagation();
}

/**
 * Maneja el evento dragover
 */
function handleDragOver(e) {
    preventDefault(e);
    this.classList.add('drag-over');
}

/**
 * Maneja el evento dragenter
 */
function handleDragEnter(e) {
    preventDefault(e);
    this.classList.add('drag-over');
}

/**
 * Maneja el evento dragleave
 */
function handleDragLeave(e) {
    preventDefault(e);
    // Solo remover la clase si realmente salimos del elemento
    if (!this.contains(e.relatedTarget)) {
        this.classList.remove('drag-over');
    }
}

/**
 * Maneja el evento drop
 */
function handleDrop(e) {
    preventDefault(e);
    this.classList.remove('drag-over');

    const files = e.dataTransfer.files;
    processBatchFileList(files);
}

/**
 * Maneja la selección de archivos desde el input
 */
function handleFileSelect(e) {
    const files = e.target.files;
    processBatchFileList(files);
    // Reset input para permitir seleccionar los mismos archivos
    e.target.value = '';
}

/**
 * Procesa la lista de archivos seleccionados
 */
function processBatchFileList(files) {
    if (!files || files.length === 0) return;

    const validFiles = Array.from(files).filter(file => {
        // Validar tipo de archivo
        const validTypes = ['.pdf', '.jpg', '.jpeg', '.png', '.doc', '.docx'];
        const extension = '.' + file.name.split('.').pop().toLowerCase();

        if (!validTypes.includes(extension)) {
            showNotification(`Archivo "${file.name}" no es válido. Solo se permiten: PDF, JPG, PNG, DOC, DOCX`, 'warning');
            return false;
        }

        // Validar tamaño (máximo 50MB)
        const maxSize = 50 * 1024 * 1024; // 50MB
        if (file.size > maxSize) {
            showNotification(`Archivo "${file.name}" es demasiado grande. Máximo 50MB`, 'warning');
            return false;
        }

        return true;
    });

    if (validFiles.length === 0) return;

    // Agregar archivos al lote
    validFiles.forEach(file => {
        fileCounter++;
        const fileObj = {
            id: fileCounter,
            file: file,
            name: file.name,
            size: file.size,
            type: detectDocumentType(file.name),
            status: 'pending'
        };
        batchFiles.push(fileObj);
    });

    updateBatchDisplay();
    showNotification(`${validFiles.length} archivo(s) agregado(s) al lote`, 'success');
}

/**
 * Detecta automáticamente el tipo de documento por el nombre del archivo
 */
function detectDocumentType(filename) {
    const name = filename.toLowerCase();

    if (name.includes('autorizac') || name.includes('autoriza')) return 'autorizacion';
    if (name.includes('historia') || name.includes('hc')) return 'historia';
    if (name.includes('factura') || name.includes('fact')) return 'factura';
    if (name.includes('consentimiento') || name.includes('consent')) return 'consentimiento';
    if (name.includes('orden') || name.includes('om')) return 'orden';
    if (name.includes('soat')) return 'soat';

    return 'otro'; // Por defecto
}

/**
 * Actualiza la visualización del lote de archivos
 */
function updateBatchDisplay() {
    const container = document.getElementById('batch-files-container');
    const grid = document.getElementById('batch-files-grid');
    const fileCount = document.getElementById('file-count');
    const processBtn = document.getElementById('process-batch-btn');

    if (batchFiles.length === 0) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'block';
    fileCount.textContent = batchFiles.length;

    // Generar cards de archivos
    grid.innerHTML = batchFiles.map(fileObj => createFileCard(fileObj)).join('');

    // Generar previews para PDFs después de renderizar
    setTimeout(() => {
        batchFiles.forEach(fileObj => {
            if (fileObj.name.toLowerCase().endsWith('.pdf')) {
                generatePDFPreview(fileObj);
            }
        });
    }, 100);

    // Habilitar/deshabilitar botón de procesar
    const allReady = batchFiles.every(f => f.status === 'ready');
    processBtn.disabled = !allReady || batchFiles.length === 0;
}

/**
 * Crea una card de archivo moderna para el lote
 */
function createFileCard(fileObj) {
    const icon = getFileIcon(fileObj.name);
    const sizeFormatted = formatFileSize(fileObj.size);
    const isPDF = fileObj.name.toLowerCase().endsWith('.pdf');

    return `
        <div class="modern-file-card" data-file-id="${fileObj.id}">
            ${isPDF ? `<div class="file-preview" id="preview-${fileObj.id}">
                <div class="preview-placeholder">
                    <i class="fas fa-file-pdf"></i>
                    <span>Vista previa</span>
                </div>
            </div>` : ''}

            <div class="file-card-header">
                <div class="file-card-info">
                    <div class="file-card-name">
                        <i class="fas ${icon}"></i>
                        ${fileObj.name}
                    </div>
                    <div class="file-card-size">${sizeFormatted}</div>
                </div>
                <button class="file-remove-btn" onclick="removeBatchFile(${fileObj.id})" title="Eliminar archivo">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="file-type-selector">
                <label>Tipo de documento</label>
                <select onchange="updateFileType(${fileObj.id}, this.value)">
                    <option value="autorizacion" ${fileObj.type === 'autorizacion' ? 'selected' : ''}>📋 Autorización</option>
                    <option value="historia" ${fileObj.type === 'historia' ? 'selected' : ''}>🩺 Historia Clínica</option>
                    <option value="factura" ${fileObj.type === 'factura' ? 'selected' : ''}>🧾 Factura</option>
                    <option value="consentimiento" ${fileObj.type === 'consentimiento' ? 'selected' : ''}>📝 Consentimiento</option>
                    <option value="orden" ${fileObj.type === 'orden' ? 'selected' : ''}>🏥 Orden Médica</option>
                    <option value="soat" ${fileObj.type === 'soat' ? 'selected' : ''}>🚗 SOAT</option>
                    <option value="otro" ${fileObj.type === 'otro' ? 'selected' : ''}>📄 Otro</option>
                </select>
            </div>
        </div>
    `;
}


/**
 * Actualiza el tipo de un archivo en el lote
 */
function updateFileType(fileId, newType) {
    const fileObj = batchFiles.find(f => f.id === fileId);
    if (fileObj) {
        fileObj.type = newType;
        fileObj.status = 'ready';
        updateBatchDisplay();
    }
}

/**
 * Remueve un archivo del lote
 */
function removeBatchFile(fileId) {
    batchFiles = batchFiles.filter(f => f.id !== fileId);
    updateBatchDisplay();

    if (batchFiles.length === 0) {
        showNotification('Lote de archivos vaciado', 'info');
    }
}

/**
 * Limpia todos los archivos del lote
 */
function clearBatchFiles() {
    if (batchFiles.length === 0) return;

    if (confirm(`¿Estás seguro de limpiar los ${batchFiles.length} archivos del lote?`)) {
        batchFiles = [];
        fileCounter = 0;
        updateBatchDisplay();
        showNotification('Lote de archivos limpiado', 'info');
    }
}

/**
 * Muestra el modal del formulario del paciente
 */
function showBatchFormModal() {
    if (batchFiles.length === 0) {
        showNotification('No hay archivos para procesar', 'warning');
        return;
    }

    const allReady = batchFiles.every(f => f.status === 'ready');
    if (!allReady) {
        showNotification('Algunos archivos no están listos. Revisa los tipos de documento.', 'warning');
        return;
    }

    // Limpiar campos del formulario
    document.getElementById('batch_doc_number').value = '';
    document.getElementById('batch_license_plate').value = '';
    document.getElementById('batch_first_name').value = '';
    document.getElementById('batch_second_name').value = '';
    document.getElementById('batch_first_lastname').value = '';
    document.getElementById('batch_second_lastname').value = '';

    // Actualizar contador de archivos
    document.getElementById('batch-file-count-display').textContent = batchFiles.length;

    // Mostrar modal
    const modal = document.getElementById('batch-form-modal');
    modal.style.display = 'flex';

    // Focus en el primer campo
    setTimeout(() => {
        document.getElementById('batch_doc_number').focus();
    }, 100);
}

/**
 * Oculta el modal del formulario del paciente
 */
function hideBatchFormModal() {
    const modal = document.getElementById('batch-form-modal');
    modal.style.display = 'none';
}

/**
 * Procesa el formulario del paciente y sube los archivos
 */
function submitBatchForm() {
    // Obtener valores de los campos
    const docNumber = document.getElementById('batch_doc_number').value.trim();
    const firstName = document.getElementById('batch_first_name').value.trim();
    const firstLastname = document.getElementById('batch_first_lastname').value.trim();
    const licensePlate = document.getElementById('batch_license_plate').value.trim();

    // Validar campos requeridos
    if (!docNumber || !firstName || !firstLastname || !licensePlate) {
        showNotification('Por favor, completa los campos requeridos (*).', 'error');
        return;
    }

    // Validar formato de placa
    if (!isValidLicensePlate(licensePlate)) {
        showNotification('Formato de placa no válido', 'error');
        return;
    }

    showNotification('Función de procesamiento en desarrollo...', 'info');
    hideBatchFormModal();
    // TODO: Implementar la subida real de archivos
}

/**
 * Genera vista previa para archivos PDF
 */
function generatePDFPreview(fileObj) {
    const previewContainer = document.getElementById(`preview-${fileObj.id}`);
    if (!previewContainer) return;

    // Crear un canvas pequeño para la preview
    const canvas = document.createElement('canvas');
    canvas.width = 200;
    canvas.height = 140;
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.objectFit = 'cover';
    canvas.style.borderRadius = '6px';

    const ctx = canvas.getContext('2d');

    // Crear URL temporal para el archivo
    const fileUrl = URL.createObjectURL(fileObj.file);

    // Usar PDF.js si está disponible, sino mostrar placeholder mejorado
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.getDocument(fileUrl).promise.then(pdf => {
            return pdf.getPage(1);
        }).then(page => {
            const viewport = page.getViewport({ scale: 0.5 });
            canvas.width = viewport.width;
            canvas.height = viewport.height;

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            return page.render(renderContext).promise;
        }).then(() => {
            previewContainer.innerHTML = '';
            previewContainer.appendChild(canvas);
        }).catch(error => {
            console.log('PDF.js no disponible, usando placeholder');
            showPreviewPlaceholder(previewContainer, fileObj);
        });
    } else {
        // Placeholder mejorado sin PDF.js
        showPreviewPlaceholder(previewContainer, fileObj);
    }

    // Limpiar URL temporal
    setTimeout(() => URL.revokeObjectURL(fileUrl), 1000);
}

/**
 * Muestra placeholder mejorado para vista previa
 */
function showPreviewPlaceholder(container, fileObj) {
    const placeholder = document.createElement('div');
    placeholder.className = 'preview-placeholder-enhanced';
    placeholder.innerHTML = `
        <div class="placeholder-content">
            <i class="fas fa-file-pdf"></i>
            <span class="placeholder-text">PDF</span>
            <div class="placeholder-size">${formatFileSize(fileObj.file.size)}</div>
        </div>
    `;

    container.innerHTML = '';
    container.appendChild(placeholder);
}

/**
 * Obtiene el icono FontAwesome para un archivo
 */
function getFileIcon(filename) {
    const extension = filename.split('.').pop().toLowerCase();
    const iconMap = {
        'pdf': 'fa-file-pdf',
        'doc': 'fa-file-word',
        'docx': 'fa-file-word',
        'jpg': 'fa-file-image',
        'jpeg': 'fa-file-image',
        'png': 'fa-file-image',
        'gif': 'fa-file-image'
    };
    return iconMap[extension] || 'fa-file';
}