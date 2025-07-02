# Moodle Form Builder Plugin

## Project Overview
A comprehensive Moodle local plugin for advanced form building with drag-and-drop interface, inspired by Easy Form Builder WordPress plugin. Supports all LMS requirements with professional-grade features.

## Recent Changes
- **2025-07-02**: Complete plugin rebuild from scratch with proper Moodle architecture
- **2025-07-02**: Created comprehensive form_manager class with full CRUD operations
- **2025-07-02**: Built working demo with 12 field types and template system
- **2025-07-02**: Implemented proper database schema and capabilities system
- **2025-07-02**: Added standalone demo for immediate testing (demo_standalone.php)
- **2025-07-02**: Implemented submissions page with detailed view and export functionality
- **2025-07-02**: Fixed index page dropdown menu toggle with Bootstrap JavaScript
- **2025-07-02**: Added multi-step form support with page break fields and progress indicators

## Current Status
The plugin implements all required features:
- ✅ Code-free form creation
- ✅ Drag-and-drop interface 
- ✅ 17 field types (text, textarea, select, checkbox, radio, file, email, number, date, heading, paragraph, grid, calculation, image, video, pagebreak)
- ✅ Help text and placeholders
- ✅ Multi-page forms
- ✅ Arithmetic calculations
- ✅ Email notifications
- ✅ Mobile responsive design
- ✅ Custom redirects and messages

## Architecture
- **Backend**: PHP with Moodle API integration
- **Frontend**: JavaScript with jQuery UI for drag-and-drop
- **Database**: MySQL/PostgreSQL compatible schema
- **Templates**: Mustache templating system
- **Styling**: Bootstrap + custom CSS

## Files Structure
- `index.php` - Main form listing page
- `builder.php` - Form creation/editing interface
- `view.php` - Form display for submissions
- `submit.php` - Form submission processing
- `manage.php` - Submissions management
- `multipage.php` - Multi-page form handler
- `demo.html` - Feature demonstration page
- `classes/` - PHP classes for forms and privacy
- `templates/` - Mustache templates
- `amd/src/` - JavaScript modules
- `lang/en/` - Language strings

## User Preferences
- Focus on comprehensive feature implementation
- Prioritize working demonstrations over perfect code structure
- Maintain compatibility with Moodle standards while providing standalone demo capability