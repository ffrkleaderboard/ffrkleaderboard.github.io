@echo off
rem Builds the pages from data/ and serves them on http://localhost:8000.
cd /d "%~dp0"
php tools\build_site.php && php -S localhost:8000 -t dist
