# Sistema de Notas de Enfermería

Este proyecto es un sistema web para la gestión de calificaciones en prácticas de enfermería. Permite a docentes calificar a estudiantes en diferentes módulos y rotaciones.

## Características

- Gestión de usuarios (administradores, docentes, estudiantes)
- Creación y asignación de listas de prácticas
- Calificación de estudiantes por docentes
- Reportes y estadísticas
- Interfaz responsive con Tailwind CSS

## Tecnologías

- PHP 7+
- MySQL
- JavaScript (jQuery, DataTables)
- Tailwind CSS
- Composer para dependencias PHP

## Instalación

1. Clona el repositorio
2. Instala las dependencias de PHP con Composer: `composer install`
3. Instala las dependencias de Node.js: `npm install`
4. Configura la base de datos en `config/config.php`
5. Ejecuta los scripts de migración en la carpeta `database/`

## Uso

- Accede a `index.php` para el login
- Los administradores pueden gestionar usuarios y listas
- Los docentes pueden calificar estudiantes asignados

## Licencia

Este proyecto es de uso educativo.