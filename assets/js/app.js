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
 * Muestra el modal de copia y renombrado
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

    // Mostrar modal
    copyModal.style.display = 'flex';

    // Focus en el primer campo
    setTimeout(() => {
        document.getElementById('doc_number').focus();
    }, 100);
}

/**
 * Oculta el modal de copia
 */
function hideCopyModal() {
    copyModal.style.display = 'none';
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