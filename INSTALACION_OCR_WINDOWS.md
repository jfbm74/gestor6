# 📖 Guía de Instalación OCR en Windows

Esta guía te ayudará a instalar **Tesseract OCR**, **ImageMagick** y **Ghostscript** en Windows para habilitar la funcionalidad de extracción de texto de PDFs escaneados.

## 🎯 Opciones de Instalación

Elige uno de los dos métodos:
- **🟢 Método 1:** Chocolatey (Recomendado - Más fácil)
- **🟡 Método 2:** Instalación Manual

---

## 🟢 MÉTODO 1: Instalación con Chocolatey (Recomendado)

### Ventajas:
- ✅ Instalación automática con dependencias
- ✅ Gestión de PATH automática
- ✅ Fácil actualización
- ✅ Idiomas incluidos automáticamente

### Paso 1: Instalar Chocolatey

1. **Abrir PowerShell como Administrador:**
   - Buscar "PowerShell" en el menú inicio
   - Click derecho → "Ejecutar como administrador"

2. **Ejecutar comando de instalación:**
   ```powershell
   Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
   ```

3. **Verificar instalación:**
   ```powershell
   choco --version
   ```

### Paso 2: Instalar OCR Dependencies

```powershell
# Instalar Tesseract (incluye español automáticamente)
choco install tesseract -y

# Instalar ImageMagick
choco install imagemagick -y

# Instalar Ghostscript
choco install ghostscript -y
```

### Paso 3: Verificar Instalación

```powershell
# Verificar Tesseract
tesseract --version

# Verificar ImageMagick
magick --version

# Verificar idiomas disponibles
tesseract --list-langs
```

**✅ ¡Listo! Si todos los comandos funcionan, la instalación está completa.**

---

## 🟡 MÉTODO 2: Instalación Manual

### Ventajas:
- ✅ Control total sobre versiones
- ✅ No requiere Chocolatey
- ✅ Funciona en cualquier Windows

### Paso 1: Instalar Tesseract OCR

1. **Descargar Tesseract:**
   - Ir a: https://github.com/UB-Mannheim/tesseract/wiki
   - Descargar la última versión (ej: `tesseract-ocr-w64-setup-5.3.3.20231005.exe`)

2. **Instalar:**
   - Ejecutar el archivo descargado como Administrador
   - **IMPORTANTE:** Marcar "Additional language data (download)" durante la instalación
   - Asegurar que "Spanish" esté seleccionado
   - Instalar en la ruta por defecto: `C:\Program Files\Tesseract-OCR`

3. **Agregar al PATH:**
   - Ir a "Panel de Control" → "Sistema" → "Configuración avanzada del sistema"
   - Click en "Variables de entorno"
   - En "Variables del sistema" buscar "Path" y hacer click en "Editar"
   - Click en "Nuevo" y agregar: `C:\Program Files\Tesseract-OCR`
   - Click "Aceptar" en todas las ventanas

### Paso 2: Instalar ImageMagick

1. **Descargar ImageMagick:**
   - Ir a: https://imagemagick.org/script/download.php#windows
   - Descargar la versión para Windows (ej: `ImageMagick-7.1.1-21-Q16-HDRI-x64-dll.exe`)

2. **Instalar:**
   - Ejecutar como Administrador
   - **IMPORTANTE:** Marcar "Add application directory to your system path"
   - **IMPORTANTE:** Marcar "Install development headers and libraries for C and C++"
   - Instalar en ruta por defecto

### Paso 3: Instalar Ghostscript

1. **Descargar Ghostscript:**
   - Ir a: https://www.ghostscript.com/download/gsdnld.html
   - Descargar "GPL Ghostscript" para Windows (ej: `gs10051w64.exe`)

2. **Instalar:**
   - Ejecutar como Administrador
   - Instalar en ruta por defecto: `C:\Program Files\gs\gs10.05.1`

3. **Agregar al PATH:**
   - Seguir los mismos pasos que con Tesseract
   - Agregar: `C:\Program Files\gs\gs10.05.1\bin`

### Paso 4: Verificar Instalación Manual

1. **Abrir nueva ventana de Command Prompt o PowerShell**

2. **Verificar cada herramienta:**
   ```cmd
   tesseract --version
   magick --version
   gswin64c --version
   tesseract --list-langs
   ```

3. **Si algún comando no funciona:**
   - Reiniciar la computadora
   - Verificar que las rutas estén correctamente en PATH
   - Intentar con la ruta completa: `"C:\Program Files\Tesseract-OCR\tesseract.exe" --version`

---

## 🔧 Resolución de Problemas

### ❌ Error: "tesseract no se reconoce como un comando interno o externo"

**Solución:**
1. Verificar que Tesseract esté instalado en: `C:\Program Files\Tesseract-OCR`
2. Verificar que la ruta esté en PATH (ver instrucciones arriba)
3. Reiniciar Command Prompt/PowerShell
4. Si persiste, reiniciar la computadora

### ❌ Error: "magick no se reconoce como un comando interno o externo"

**Solución:**
1. Reinstalar ImageMagick marcando "Add to PATH"
2. O agregar manualmente: `C:\Program Files\ImageMagick-X.X.X-QXX`

### ❌ Error: "No se puede encontrar el idioma español"

**Solución:**
1. Reinstalar Tesseract marcando "Additional language data"
2. O descargar manualmente los archivos de idioma desde:
   https://github.com/tesseract-ocr/tessdata
3. Copiar `spa.traineddata` a: `C:\Program Files\Tesseract-OCR\tessdata\`

### ❌ Error: "Error al convertir PDF a imágenes"

**Solución:**
1. Verificar que Ghostscript esté instalado
2. Reinstalar ImageMagick
3. Verificar permisos de la carpeta temporal

---

## ✅ Verificación Final

Después de la instalación, ejecuta este comando para probar todo el flujo:

```cmd
echo Probando OCR... > test.txt
tesseract --list-langs
magick --version
```

Si todos los comandos funcionan correctamente, ¡la instalación OCR está lista!

---

## 📋 Requisitos del Sistema

- **Windows:** 10 o superior (recomendado)
- **RAM:** Mínimo 4GB (8GB recomendado para PDFs grandes)
- **Espacio:** ~500MB para todas las herramientas
- **PHP:** 7.4 o superior con `exec()` habilitado

---

## 🚀 Siguiente Paso

Una vez completada la instalación:

1. ✅ Subir el código PHP actualizado al servidor Windows
2. ✅ Navegar a un PDF en el explorador de archivos
3. ✅ Click en el botón "Extraer Texto"
4. ✅ ¡Disfrutar del OCR automático!

---

## 💡 Tips Adicionales

- **Rendimiento:** PDFs de mayor resolución tardan más pero dan mejor precisión
- **Idiomas:** El sistema usa español + inglés automáticamente
- **Cache:** Los resultados se guardan para evitar reprocesar
- **Formatos:** Solo funciona con archivos PDF (no imágenes sueltas)

¿Problemas? Revisa la sección de resolución de problemas o contacta al administrador del sistema.