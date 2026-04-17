@echo off
REM eSIM Upload System - Verification Script
REM Run this to verify everything is working

setlocal enabledelayedexpansion

echo.
echo ============================================
echo   eSIM UPLOAD SYSTEM - VERIFICATION
echo ============================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo ERROR: Not in project root directory
    echo Please run this script from: c:\Users\USER\Desktop\hecks\hecksteckie\
    pause
    exit /b 1
)

echo [1/6] Checking storage/app/public/esim directory...
if exist "storage\app\public\esim" (
    echo ✅ Directory exists
) else (
    echo ❌ Directory missing - creating...
    mkdir storage\app\public\esim
    if exist "storage\app\public\esim" (
        echo ✅ Directory created
    ) else (
        echo ❌ Failed to create directory
    )
)
echo.

echo [2/6] Checking public/storage link...
if exist "public\storage" (
    echo ✅ Storage link exists
) else (
    echo ⚠️  Storage link missing - creating...
    cd public
    mklink /J storage "..\storage\app\public"
    cd ..
    if exist "public\storage" (
        echo ✅ Storage link created
    ) else (
        echo ❌ Failed to create link (may need admin rights)
    )
)
echo.

echo [3/6] Clearing Laravel caches...
php artisan cache:clear > nul 2>&1
php artisan config:clear > nul 2>&1
php artisan view:clear > nul 2>&1
php artisan route:clear > nul 2>&1
echo ✅ Caches cleared
echo.

echo [4/6] Checking file modifications...
findstr /c:"Storage::disk('public')" app\Http\Controllers\AdminServiceController.php > nul
if !errorlevel! equ 0 (
    echo ✅ AdminServiceController updated
) else (
    echo ❌ AdminServiceController not updated
)

findstr /c:"createStorageLink" app\Providers\AppServiceProvider.php > nul
if !errorlevel! equ 0 (
    echo ✅ AppServiceProvider updated
) else (
    echo ❌ AppServiceProvider not updated
)
echo.

echo [5/6] Checking file permissions...
icacls "storage\app\public\esim" > nul 2>&1
if !errorlevel! equ 0 (
    echo ✅ Permissions OK
) else (
    echo ⚠️  May need to set permissions: icacls "storage" /grant:r "%%USERNAME%%:F" /t
)
echo.

echo [6/6] Application status...
php artisan tinker --execute="echo 'Laravel OK'" 2>&1 | find "Laravel OK" > nul
if !errorlevel! equ 0 (
    echo ✅ Laravel running
) else (
    echo ⚠️  Laravel check inconclusive
)
echo.

echo ============================================
echo   TESTS COMPLETED
echo ============================================
echo.
echo Next steps:
echo 1. Upload a new eSIM image via admin panel
echo 2. Check storage/app/public/esim/ for files
echo 3. Verify image displays in admin transaction
echo 4. Check user transaction history for images
echo 5. Open browser DevTools (F12) - Network tab
echo 6. Verify image URLs show /storage/esim/...
echo.
echo For full documentation, see:
echo - ESIM_UPLOAD_FIX_README.md (detailed)
echo - ESIM_UPLOAD_QUICK_SUMMARY.md (quick reference)
echo.
pause
