@echo off
title 1rep4out.cmd
cls
rem ==========================================================================
rem 
rem  CMD File && PHP
rem 
rem  NAME: Разбор входящих файлов из АСТРЫ
rem 
rem  AUTHOR: Evgeny Dadykov, Mizuho Bank
rem  DATE  : 10.01.2020
rem 
rem  COMMENT: запускать в 7 утра, выключится сам в 23-00
rem           выход по Ctrl && C
rem ==========================================================================



set path=C:\UTILZ\php;C:\UTILZ;%PATH%
cd C:\BAT

:LOOP
if "%TIME: =0%" geq "23:00:00,00" exit

php C:\BAT\1rep4out.php
choice.exe /n /C q /D q /T 125 /M "задержка в 125 сек (Q - не ждать, CTRL+C - выход)"

goto LOOP
