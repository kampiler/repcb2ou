@echo off
title 1rep4out.cmd
cls
rem ==========================================================================
rem 
rem  CMD File && PHP
rem 
rem  NAME: ������ �室��� 䠩��� �� �����
rem 
rem  AUTHOR: Evgeny Dadykov, Mizuho Bank
rem  DATE  : 10.01.2020
rem 
rem  COMMENT: ����᪠�� � 7 ���, �몫����� ᠬ � 23-00
rem           ��室 �� Ctrl && C
rem ==========================================================================



set path=C:\UTILZ\php;C:\UTILZ;%PATH%
cd C:\BAT

:LOOP
if "%TIME: =0%" geq "23:00:00,00" exit

php C:\BAT\1rep4out.php
choice.exe /n /C q /D q /T 125 /M "����প� � 125 ᥪ (Q - �� �����, CTRL+C - ��室)"

goto LOOP
