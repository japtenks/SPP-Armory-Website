@echo off
setlocal EnableExtensions EnableDelayedExpansion

REM --- Config ---
set "site=https://wow.zamimg.com"
set "base=%site%/images/wow/icons/large/"

REM --- Inputs check ---
if not exist "iconname.txt" (
  echo iconname.txt not found in the current folder.
  exit /b 1
)

REM --- Main loop ---
for /f "usebackq delims=" %%F in ("iconname.txt") do (
  set "name=%%F"
  REM Trim leading spaces
  for /f "tokens=* delims= " %%A in ("!name!") do set "name=%%A"

  if not defined name (
    echo [SKIP] blank line
  ) else (
    REM Add .jpg if there is no dot in the name
    if "!name!"=="!name:.=!" set "name=!name!.jpg"

    if exist "!name!" (
      echo [SKIP] !name! already exists
    ) else (
      echo [GET ] %base%!name!
      curl -fSL "%base%!name!" -o "!name!"
      if errorlevel 1 (
        echo [FAIL] !name!
      ) else (
        echo [ OK ] !name!
      )
    )

    REM Random wait: 4–8 seconds
    set /a wait=4 + (!random! %% 5)
    echo [WAIT] Sleeping !wait! seconds...
    timeout /t !wait! /nobreak >nul
  )
)

endlocal
