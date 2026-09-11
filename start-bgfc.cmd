@echo off
setlocal
cd /d "%~dp0"

set "BGFC_NODE=node"
where node >nul 2>nul
if errorlevel 1 set "BGFC_NODE=%TEMP%\bgfc-node22-complete\node-v22.20.0-win-x64\node.exe"

if not exist "%BGFC_NODE%" (
  echo Node.js was not found.
  echo Install Node.js 22, then run this file again.
  pause
  exit /b 1
)

start "BGFC Inventory Server" /min "%BGFC_NODE%" src\app.js
echo BGFC Inventory is starting. Open http://localhost:3000/login
timeout /t 2 /nobreak >nul
start "" http://localhost:3000/login
endlocal
