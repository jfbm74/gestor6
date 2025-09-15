# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP-based document management system for **Clínica Bonsana** that provides a modern web interface for browsing, uploading, searching, and managing files across multiple directory bases. The application is designed for organizing scanned medical documents with role-based access control and features a modern, responsive design inspired by contemporary file managers.

## Architecture

The application follows a **modular MVC architecture** with clear separation of concerns:

### Core Components

1. **Configuration** (`config/config.php`):
   - Centralized configuration for users, document bases, and application settings
   - Security settings and file validation rules
   - Clínica Bonsana branding configuration

2. **Authentication System** (`src/Auth/`):
   - `AuthManager.php`: Handles user authentication and authorization
   - `SessionManager.php`: Manages user sessions and permissions
   - Three user roles: `lector` (read), `cargas` (upload), `superadmin` (full)

3. **File System Management** (`src/FileSystem/`):
   - `DirectoryManager.php`: Directory navigation and listing
   - `FileManager.php`: File operations (upload, delete, rename, copy)
   - `SearchManager.php`: Global search across all document bases

4. **Security** (`src/Security/`):
   - `PathValidator.php`: Path traversal protection and file validation

5. **Frontend** (`templates/`, `assets/`):
   - Modern responsive design with Bonsana branding
   - Separated CSS (`assets/css/styles.css`) and JavaScript (`assets/js/app.js`)
   - Template-based views with header/footer layout

### Document Bases

The system is configured to work with three document repositories:
- **DOCUMENTOSSCANEADOSANTIGUOS**: Legacy documents archive
- **DOCUMENTOSSCANEADOS**: Current scanned documents
- **SCANNER**: Inbox for new scans with copy/rename workflow

## Key Features

1. **Modern UI/UX**:
   - Clean, modern design similar to contemporary file managers
   - Responsive layout that works on desktop and mobile
   - Bonsana clinic branding with logo and color scheme
   - Recent files view with file type icons
   - Table view with sortable columns

2. **Advanced Search**:
   - Global search across all configured directories
   - Real-time search results with file previews
   - File type filtering and sorting

3. **File Operations**:
   - Drag-and-drop file uploads (role-dependent)
   - Bulk file operations
   - Copy and rename workflow with automatic naming conventions
   - Secure file downloads with MIME type detection

4. **Security Features**:
   - Robust path traversal protection
   - Role-based access control with granular permissions
   - File type validation and sanitization
   - Session timeout and validation

## Development Commands

Since this is a PHP application with no build process:

- **Run locally**: Use PHP built-in server: `php -S localhost:8000`
- **Deploy**: Copy all files to web server document root
- **Configuration**: Edit `config/config.php` for environment-specific settings

## File Structure

```
gestor/
├── index.php                 # Main application entry point
├── config/
│   └── config.php           # Centralized configuration
├── src/
│   ├── Auth/
│   │   ├── AuthManager.php  # Authentication logic
│   │   └── SessionManager.php # Session management
│   ├── FileSystem/
│   │   ├── DirectoryManager.php # Directory operations
│   │   ├── FileManager.php  # File operations
│   │   └── SearchManager.php # Search functionality
│   └── Security/
│       └── PathValidator.php # Security validation
├── templates/
│   ├── layout/
│   │   ├── header.php       # Common header
│   │   └── footer.php       # Common footer
│   ├── pages/
│   │   ├── login.php        # Login page
│   │   └── file-browser.php # Main file browser
│   └── components/
│       └── modals.php       # Modal dialogs
├── assets/
│   ├── css/
│   │   └── styles.css       # Main stylesheet
│   └── js/
│       └── app.js           # JavaScript functionality
└── source/
    ├── logo.png             # Bonsana clinic logo
    └── *.png                # Legacy assets
```

## Development Guidelines

1. **Adding New Features**: Follow the modular architecture by adding new classes in appropriate `src/` subdirectories
2. **UI Changes**: Modify templates in `templates/` and styles in `assets/css/styles.css`
3. **Configuration**: All settings should be added to `config/config.php`
4. **Security**: Always use `PathValidator` for any file system operations
5. **Error Handling**: Use try-catch blocks and log errors appropriately

## Environment Requirements

- PHP 7.4+ with standard extensions
- Web server (Apache/Nginx) with PHP support
- File system access to configured document directories
- No database required (file-based system)

The application maintains backward compatibility with the original monolithic design while providing a much more maintainable and scalable architecture.