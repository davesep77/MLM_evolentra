@echo off
REM ============================================
REM Database Schema Export Script (Windows)
REM ============================================
REM This script exports your local database schema
REM for deployment to DigitalOcean
REM
REM Usage: export_database.bat
REM ============================================

echo.
echo ============================================
echo   Evolentra Database Export Tool
echo ============================================
echo.

REM Configuration
set DB_NAME=evolentra
set OUTPUT_FILE=database\schema.sql
set BACKUP_FILE=database\backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%.sql

REM Create database directory if it doesn't exist
if not exist "database" mkdir database

echo Exporting database schema...
echo.

REM Check if mysqldump is available (from XAMPP)
set MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe

if not exist "%MYSQLDUMP_PATH%" (
    echo Error: mysqldump not found at %MYSQLDUMP_PATH%
    echo Please verify your XAMPP installation
    pause
    exit /b 1
)

REM Prompt for MySQL credentials
set /p DB_HOST="Host [localhost]: "
if "%DB_HOST%"=="" set DB_HOST=localhost

set /p DB_USER="Username [root]: "
if "%DB_USER%"=="" set DB_USER=root

set /p DB_PASS="Password: "

echo.
echo Exporting to: %OUTPUT_FILE%
echo.

REM Export schema and data
"%MYSQLDUMP_PATH%" -h %DB_HOST% -u %DB_USER% -p%DB_PASS% ^
  --databases %DB_NAME% ^
  --add-drop-database ^
  --add-drop-table ^
  --routines ^
  --triggers ^
  --events ^
  > "%OUTPUT_FILE%" 2>&1

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ============================================
    echo   Export Successful!
    echo ============================================
    echo.
    echo Schema exported to: %OUTPUT_FILE%
    
    REM Create backup
    copy "%OUTPUT_FILE%" "%BACKUP_FILE%" >nul
    echo Backup created: %BACKUP_FILE%
    echo.
    
    REM Show file info
    for %%A in ("%OUTPUT_FILE%") do echo File size: %%~zA bytes
    echo.
    
    echo Next steps:
    echo 1. Review the exported schema: %OUTPUT_FILE%
    echo 2. Import to DigitalOcean database (see DEPLOYMENT_GUIDE.md^)
    echo 3. Verify all tables are present
    echo.
) else (
    echo.
    echo ============================================
    echo   Export Failed!
    echo ============================================
    echo.
    echo Please check your credentials and try again
    echo.
)

pause
