@echo off
setlocal
set "REPO=%~dp0.."
bash "%REPO%\bin\create-db"
