@echo off
"c:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS gestao"
"c:\xampp\mysql\bin\mysql.exe" -u root gestao < "c:\xampp\htdocs\gestao\database.sql"
"c:\xampp\mysql\bin\mysql.exe" -u root gestao < add_email_config.sql
