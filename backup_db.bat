@echo off

for /F "tokens=1,2,3 delims=_" %%i in ('PowerShell -Command "& {Get-Date -format "MM_dd_yyyy"}"') do (
    set MONTH=%%i
    set DAY=%%j
    set YEAR=%%k
)

set name=%YEAR%%MONTH%%DAY%
cd\
c:
c:\xampp\mysql\bin\mysqldump --user=root --result-file="D:\backup_legal_office\%name%_legal_office.sql" legal_office
cd\
d:

exit