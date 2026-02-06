; SPOS Custom Installer Script
; Professional Update System with Data Preservation
; Preserves user data, license, and system state during updates

; Variables for backup paths
Var IsUpdate
Var StateFileBackup
Var ConfigBackup
Var DatabaseBackup
Var StorageBackup

!macro customInit
  ; Initialize variables
  StrCpy $IsUpdate "0"
  StrCpy $R0 "$TEMP\SPOS_Backup_${__TIMESTAMP__}"
  
  ; Check if installation already exists
  ${If} ${FileExists} "$INSTDIR\SPOS.exe"
    StrCpy $IsUpdate "1"
    DetailPrint "Existing installation detected - Update mode"
    
    ; Create backup directory
    CreateDirectory "$R0"
    DetailPrint "Creating backup at: $R0"
    
    ; Backup system state file (CRITICAL - preserves license and version info)
    ${If} ${FileExists} "$INSTDIR\resources\storage\app\system_state.json"
      CopyFiles /SILENT "$INSTDIR\resources\storage\app\system_state.json" "$R0\system_state.json"
      StrCpy $StateFileBackup "1"
      DetailPrint "✓ System state backed up"
    ${EndIf}
    
    ; Backup activation marker
    ${If} ${FileExists} "$INSTDIR\resources\storage\app\activated_at"
      CopyFiles /SILENT "$INSTDIR\resources\storage\app\activated_at" "$R0\activated_at"
      DetailPrint "✓ Activation marker backed up"
    ${EndIf}
    
    ; Backup config/system.php (contains license info)
    ${If} ${FileExists} "$INSTDIR\resources\config\system.php"
      CopyFiles /SILENT "$INSTDIR\resources\config\system.php" "$R0\system.php"
      StrCpy $ConfigBackup "1"
      DetailPrint "✓ License configuration backed up"
    ${EndIf}
    
    ; Backup database
    ${If} ${FileExists} "$INSTDIR\resources\database\database.sqlite"
      CopyFiles /SILENT "$INSTDIR\resources\database\database.sqlite" "$R0\database.sqlite"
      StrCpy $DatabaseBackup "1"
      DetailPrint "✓ Database backed up"
    ${EndIf}
    
    ; Backup entire storage directory (user uploads, logs, backups)
    ${If} ${FileExists} "$INSTDIR\resources\storage\app\*.*"
      CreateDirectory "$R0\storage_app"
      CopyFiles /SILENT "$INSTDIR\resources\storage\app\*.*" "$R0\storage_app\"
      StrCpy $StorageBackup "1"
      DetailPrint "✓ User data backed up"
    ${EndIf}
    
    ; Create installation metadata for version tracking
    FileOpen $0 "$R0\install_metadata.json" w
    FileWrite $0 '{"install_type":"update","timestamp":"${__TIMESTAMP__}","from_version":"previous","to_version":"1.1.0"}'
    FileClose $0
    
    DetailPrint "Backup completed successfully"
  ${Else}
    DetailPrint "Fresh installation detected - Clean install mode"
    
    ; Create installation metadata for clean install
    CreateDirectory "$INSTDIR\resources\storage\app"
    FileOpen $0 "$INSTDIR\resources\storage\app\install_metadata.json" w
    FileWrite $0 '{"install_type":"clean_install","timestamp":"${__TIMESTAMP__}","version":"1.1.0"}'
    FileClose $0
  ${EndIf}
!macroend

!macro customInstall
  ; Add firewall rules
  DetailPrint "Configuring Windows Firewall..."
  nsExec::ExecToLog 'netsh advfirewall firewall add rule name="SPOS MySQL Server" dir=in action=allow program="$INSTDIR\resources\mysql\bin\mysqld.exe" enable=yes profile=any'
  nsExec::ExecToLog 'netsh advfirewall firewall add rule name="SPOS PHP Server" dir=in action=allow program="$INSTDIR\resources\php\php.exe" enable=yes profile=any'
  nsExec::ExecToLog 'netsh advfirewall firewall add rule name="SPOS Application" dir=in action=allow program="$INSTDIR\SPOS.exe" enable=yes profile=any'
  DetailPrint "✓ Firewall rules configured"
  
  ; Restore backed up data (if this was an update)
  ${If} $IsUpdate == "1"
    DetailPrint "Restoring user data from backup..."
    
    ; Restore system state (CRITICAL - prevents re-activation)
    ${If} $StateFileBackup == "1"
      CreateDirectory "$INSTDIR\resources\storage\app"
      CopyFiles /SILENT "$R0\system_state.json" "$INSTDIR\resources\storage\app\system_state.json"
      DetailPrint "✓ System state restored"
    ${EndIf}
    
    ; Restore activation marker
    ${If} ${FileExists} "$R0\activated_at"
      CopyFiles /SILENT "$R0\activated_at" "$INSTDIR\resources\storage\app\activated_at"
      DetailPrint "✓ Activation marker restored"
    ${EndIf}
    
    ; Restore config (license info)
    ${If} $ConfigBackup == "1"
      CreateDirectory "$INSTDIR\resources\config"
      CopyFiles /SILENT "$R0\system.php" "$INSTDIR\resources\config\system.php"
      DetailPrint "✓ License configuration restored"
    ${EndIf}
    
    ; Restore database (IMPORTANT - user's data)
    ${If} $DatabaseBackup == "1"
      CreateDirectory "$INSTDIR\resources\database"
      CopyFiles /SILENT "$R0\database.sqlite" "$INSTDIR\resources\database\database.sqlite"
      DetailPrint "✓ Database restored"
    ${EndIf}
    
    ; Restore storage directory (user uploads, backups)
    ${If} $StorageBackup == "1"
      CreateDirectory "$INSTDIR\resources\storage\app"
      CopyFiles /SILENT "$R0\storage_app\*.*" "$INSTDIR\resources\storage\app\"
      DetailPrint "✓ User data restored"
    ${EndIf}
    
    ; Copy installation metadata to track update
    ${If} ${FileExists} "$R0\install_metadata.json"
      CopyFiles /SILENT "$R0\install_metadata.json" "$INSTDIR\resources\storage\app\install_metadata.json"
      DetailPrint "✓ Installation metadata saved"
    ${EndIf}
    
    DetailPrint "Data restoration completed successfully"
    
    ; Clean up backup directory after successful restore
    RMDir /r "$R0"
    DetailPrint "Temporary backup cleaned up"
  ${Else}
    ; Fresh install - create initial system state
    CreateDirectory "$INSTDIR\resources\storage\app"
    FileOpen $0 "$INSTDIR\resources\storage\app\system_state.json" w
    FileWrite $0 '{"installed_version":"1.1.0","installation_type":"clean_install","initialized_at":"${__TIMESTAMP__}","activated":false,"last_migration_success":false,"update_in_progress":false,"migration_failures":0}'
    FileClose $0
    DetailPrint "✓ Initial system state created"
    
    ; Create installation metadata
    FileOpen $0 "$INSTDIR\resources\storage\app\install_metadata.json" w
    FileWrite $0 '{"install_type":"clean_install","timestamp":"${__TIMESTAMP__}","version":"1.1.0"}'
    FileClose $0
    DetailPrint "✓ Installation metadata created"
  ${EndIf}
  
  ; Set proper permissions for storage directory
  AccessControl::GrantOnFile "$INSTDIR\resources\storage" "(S-1-5-32-545)" "FullAccess"
  DetailPrint "✓ Storage permissions configured"
  
  DetailPrint "Installation completed successfully!"
!macroend

!macro customUnInstall
  ; Offer to backup user data before uninstall
  MessageBox MB_YESNO "Would you like to backup your data before uninstalling?$\n$\nThis will save your database, license, and settings to:$\n$DOCUMENTS\SPOS_Backup" IDYES backup IDNO skip_backup
  
  backup:
    CreateDirectory "$DOCUMENTS\SPOS_Backup"
    
    ; Backup database
    ${If} ${FileExists} "$INSTDIR\resources\database\database.sqlite"
      CopyFiles "$INSTDIR\resources\database\database.sqlite" "$DOCUMENTS\SPOS_Backup\database.sqlite"
    ${EndIf}
    
    ; Backup system state
    ${If} ${FileExists} "$INSTDIR\resources\storage\app\system_state.json"
      CopyFiles "$INSTDIR\resources\storage\app\system_state.json" "$DOCUMENTS\SPOS_Backup\system_state.json"
    ${EndIf}
    
    ; Backup config
    ${If} ${FileExists} "$INSTDIR\resources\config\system.php"
      CopyFiles "$INSTDIR\resources\config\system.php" "$DOCUMENTS\SPOS_Backup\system.php"
    ${EndIf}
    
    ; Backup storage
    CreateDirectory "$DOCUMENTS\SPOS_Backup\storage"
    CopyFiles "$INSTDIR\resources\storage\app\*.*" "$DOCUMENTS\SPOS_Backup\storage\"
    
    MessageBox MB_OK "Backup saved to: $DOCUMENTS\SPOS_Backup"
  
  skip_backup:
    ; Remove firewall rules
    DetailPrint "Removing firewall rules..."
    nsExec::ExecToLog 'netsh advfirewall firewall delete rule name="SPOS MySQL Server"'
    nsExec::ExecToLog 'netsh advfirewall firewall delete rule name="SPOS PHP Server"'
    nsExec::ExecToLog 'netsh advfirewall firewall delete rule name="SPOS Application"'
    DetailPrint "✓ Firewall rules removed"
!macroend
