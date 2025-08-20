# USS Serenity Training Management System

## Overview
The training system has been completely redesigned from a simple text document display to a comprehensive file management platform with enterprise-level features.

## Features

### 🔐 Departmental Access Control
- **MED/SCI**: Medical and Science departments can manage medical training files
- **ENG/OPS**: Engineering and Operations departments can manage technical training files  
- **SEC/TAC**: Security and Tactical departments can manage security training files
- **Command**: Command staff can manage all departments and command-specific training

### 📁 File Management
- **Upload**: Secure file upload with validation (PDF, DOC, DOCX, PPT, PPTX, TXT, MP4, AVI, MOV)
- **Download**: Tracked downloads with audit logging
- **Delete**: Soft delete system - files moved to deleted folder for 90 days before permanent removal
- **File Size**: 50MB maximum file size limit
- **Security**: MIME type validation and secure file storage outside web root

### 📊 Statistics Dashboard
- Real-time file counts by department
- Visual statistics with LCARS styling
- Department-specific color coding

### 🔍 Audit Trail System
- Complete logging of all file operations (upload, download, delete)
- User tracking with character names, ranks, and departments
- IP address and user agent logging for security
- Timestamp tracking for compliance
- Searchable audit interface

### 🎛️ User Interface
- **Tabbed Interface**: Separate tabs for Files and Audit Log
- **LCARS Styling**: Authentic Star Trek computer interface design
- **Responsive Design**: Mobile-friendly layout
- **Interactive Elements**: Hover effects, animations, and visual feedback

## Database Schema

### training_files
- `id`: Primary key
- `title`: File display name
- `original_filename`: Original uploaded filename
- `stored_filename`: Secure storage filename
- `file_path`: Full file path
- `file_size`: File size in bytes
- `mime_type`: File MIME type
- `department`: Department (MED/SCI, ENG/OPS, SEC/TAC, Command)
- `uploaded_by`: User ID who uploaded
- `uploaded_at`: Upload timestamp
- `download_count`: Number of downloads
- `is_deleted`: Soft delete flag
- `deleted_at`: Deletion timestamp
- `deleted_by`: User who deleted the file

### training_audit
- `id`: Primary key
- `file_id`: Foreign key to training_files
- `action`: Action performed (upload, download, delete)
- `performed_by`: User ID who performed action
- `character_name`: Full character name
- `user_rank`: User's rank
- `user_department`: User's department
- `ip_address`: IP address of user
- `user_agent`: Browser/device information
- `timestamp`: When action occurred
- `additional_notes`: Optional notes

### training_access_log
- `id`: Primary key
- `user_id`: User who accessed training system
- `access_time`: When they accessed it
- `ip_address`: IP address
- `user_agent`: Browser/device information

## File Storage Structure
```
training_files/
├── MED_SCI/           # Medical/Science files
├── ENG_OPS/           # Engineering/Operations files  
├── SEC_TAC/           # Security/Tactical files
├── Command/           # Command files
└── deleted/           # Soft-deleted files (90-day retention)
    ├── MED_SCI/
    ├── ENG_OPS/
    ├── SEC_TAC/
    └── Command/
```

## Security Features
- **.htaccess Protection**: Files cannot be directly accessed via web browser
- **MIME Type Validation**: Only allowed file types can be uploaded
- **File Size Limits**: 50MB maximum upload size
- **Departmental Access**: Users can only manage files for their department (except Command)
- **Audit Logging**: All actions are logged for security compliance
- **Secure Storage**: Files stored outside web root with randomized names

## Installation
1. Run `setup_training_system.php` to create database tables and directories
2. Ensure proper file permissions on the `training_files` directory
3. Configure web server to prevent direct access to training files

## Usage

### For Regular Users
1. Navigate to Training section
2. View files available to your department
3. Download files as needed (downloads are logged)

### For Department Managers
1. Access upload interface (only available if you have permissions)
2. Select file and enter title/description
3. Choose appropriate department
4. Upload file (action is logged)

### For Command Staff
1. Full access to all department files
2. Can upload files to any department
3. Can delete files from any department
4. Access to complete audit trail

## Automated Maintenance
- Files in the deleted folder are automatically purged after 90 days
- Audit logs are retained permanently for compliance
- Download statistics are tracked for usage analysis

## Future Enhancements
- Email notifications for new uploads
- Version control for training documents
- Approval workflow for sensitive materials
- Integration with crew schedules for mandatory training tracking
