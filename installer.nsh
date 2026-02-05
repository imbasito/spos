; ============================================================
; SPOS Professional Installer Script
; Version: 1.0.5
; Copyright © 2026 SINYX. All Rights Reserved.
; ============================================================

!include "MUI2.nsh"
!include "LogicLib.nsh"
!include "nsDialogs.nsh"

; ============================================================
; CUSTOM INSTALL - Runs after files are copied
; ============================================================
!macro customInstall
  ; -----------------------------------------------------------
  ; 1. CHECK VISUAL C++ REDISTRIBUTABLE
  ; -----------------------------------------------------------
  DetailPrint "Checking Visual C++ Redistributable..."
  
  ; Check if VC++ 2015-2022 x64 is installed (Registry check)
  ReadRegStr $0 HKLM "SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64" "Installed"
  ${If} $0 != "1"
    ; Try alternate registry location
    ReadRegStr $0 HKLM "SOFTWARE\WOW6432Node\Microsoft\VisualStudio\14.0\VC\Runtimes\x64" "Installed"
  ${EndIf}
  
  ${If} $0 != "1"
    MessageBox MB_YESNO|MB_ICONEXCLAMATION "Visual C++ Redistributable 2015-2022 is required but not installed.$\n$\nWould you like to download it now?$\n$\n(Click 'No' to continue anyway - SPOS may not work correctly)" IDYES downloadVC IDNO skipVC
    downloadVC:
      ExecShell "open" "https://aka.ms/vs/17/release/vc_redist.x64.exe"
      MessageBox MB_OK|MB_ICONINFORMATION "Please install Visual C++ Redistributable and then restart SPOS."
    skipVC:
  ${EndIf}
  
  ; -----------------------------------------------------------
  ; 2. ADD WINDOWS FIREWALL RULES
  ; -----------------------------------------------------------
  DetailPrint "Configuring Windows Firewall..."
  
  ; Remove old rules first (in case of reinstall)
  nsExec::ExecToLog 'netsh advfirewall firewall delete rule name="SPOS MySQL Server" 2>nul'
  nsExec::ExecToLog 'netsh advfirewall firewall delete rule name="SPOS PHP Server" 2>nul'
  nsExec::ExecToLog 'netsh advfirewall firewall delete rule name="SPOS Application" 2>nul'
  
  ; Add firewall rule for MySQL (Inbound)
  nsExec::ExecToLog 'netsh advfirewall firewall add rule name="SPOS MySQL Server" dir=in action=allow program="$INSTDIR\resources\mysql\bin\mysqld.exe" enable=yes profile=any'
  
  ; Add firewall rule for PHP (Inbound)
  nsExec::ExecToLog 'netsh advfirewall firewall add rule name="SPOS PHP Server" dir=in action=allow program="$INSTDIR\resources\php\php.exe" enable=yes profile=any'
  
  ; Add firewall rule for SPOS Application
  nsExec::ExecToLog 'netsh advfirewall firewall add rule name="SPOS Application" dir=in action=allow program="$INSTDIR\SPOS.exe" enable=yes profile=any'
  
  ; -----------------------------------------------------------
  ; 3. INITIALIZE MYSQL DATABASE
  ; -----------------------------------------------------------
  DetailPrint "Initializing MySQL Database..."
  
  ; Check if MySQL data folder already exists
  IfFileExists "$INSTDIR\resources\mysql\data\mysql\*.*" skipMySQLInit initMySQL
  
  initMySQL:
    ; Initialize MySQL with --initialize-insecure (no root password)
    nsExec::ExecToLog '"$INSTDIR\resources\mysql\bin\mysqld.exe" --initialize-insecure --datadir="$INSTDIR\resources\mysql\data"'
    DetailPrint "MySQL database initialized successfully."
    Goto doneMySQLInit
    
  skipMySQLInit:
    DetailPrint "MySQL database already exists. Skipping initialization."
    
  doneMySQLInit:
  
  ; -----------------------------------------------------------
  ; 4. SET FOLDER PERMISSIONS
  ; -----------------------------------------------------------
  DetailPrint "Setting folder permissions..."
  
  ; Grant full control to Users group for storage and mysql folders
  nsExec::ExecToLog 'icacls "$INSTDIR\resources\storage" /grant Users:(OI)(CI)F /T /Q'
  nsExec::ExecToLog 'icacls "$INSTDIR\resources\mysql\data" /grant Users:(OI)(CI)F /T /Q'
  nsExec::ExecToLog 'icacls "$INSTDIR\resources\bootstrap\cache" /grant Users:(OI)(CI)F /T /Q'
  
  ; -----------------------------------------------------------
  ; 5. CREATE REQUIRED DIRECTORIES
  ; -----------------------------------------------------------
  DetailPrint "Creating required directories..."
  
  CreateDirectory "$INSTDIR\resources\storage\logs"
  CreateDirectory "$INSTDIR\resources\storage\framework\cache"
  CreateDirectory "$INSTDIR\resources\storage\framework\sessions"
  CreateDirectory "$INSTDIR\resources\storage\framework\views"
  CreateDirectory "$INSTDIR\resources\storage\app\backups"
  CreateDirectory "$INSTDIR\resources\bootstrap\cache"
  
  DetailPrint "SPOS installation completed successfully!"
!macroend

; ============================================================
; CUSTOM UNINSTALL - Cleanup on removal
; ============================================================
!macro customUnInstall
  DetailPrint "Removing SPOS configuration..."
  
  ; Remove firewall rules
  nsExec::ExecToLog 'netsh advfirewall firewall delete rule name="SPOS MySQL Server"'
  nsExec::ExecToLog 'netsh advfirewall firewall delete rule name="SPOS PHP Server"'
  nsExec::ExecToLog 'netsh advfirewall firewall delete rule name="SPOS Application"'
  
  ; Ask user if they want to keep database
  MessageBox MB_YESNO|MB_ICONQUESTION "Do you want to DELETE all SPOS data (products, sales, customers)?$\n$\nClick 'No' to keep your data for future reinstallation." IDYES deleteData IDNO keepData
  
  deleteData:
    RMDir /r "$INSTDIR\resources\mysql\data"
    RMDir /r "$INSTDIR\resources\storage"
    DetailPrint "All SPOS data has been removed."
    Goto doneUninstall
    
  keepData:
    DetailPrint "SPOS data preserved for future use."
    
  doneUninstall:
!macroend
