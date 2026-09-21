@echo off
   cd /d "D:\Project-Scraping-Perpustakaan\scraping-perpustakaan"
   "C:\php-8.3\php.exe" artisan schedule:run >> storage\logs\scheduler.log 2>&1