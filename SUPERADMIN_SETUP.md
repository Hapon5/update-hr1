# Super Admin Account Creation Guide

## Overview
This guide explains how to create a Super Admin account for the HR1 System.

## Method 1: Using the Super Admin Setup Page (Recommended)

### Steps:
1. Navigate to: `http://your-domain/hr1/create_superadmin.php`
2. Fill in the form:
   - **Full Name**: Your full name
   - **Email Address**: Your email (will be used for login)
   - **Password**: A strong password
3. Click "Create Super Admin Account"
4. You will see a success message
5. Go to the login page and login with your credentials

### Features:
- ✅ **No OTP verification required** - Account is created instantly
- ✅ **Automatic password hashing** - Passwords are securely stored
- ✅ **Full admin privileges** - Account_type = 0 (Super Admin)
- ✅ **Database transaction** - Ensures data integrity
- ✅ **Duplicate email check** - Prevents duplicate accounts

### Security Note:
⚠️ **IMPORTANT**: After creating your Super Admin account, you should **DELETE** or **RESTRICT ACCESS** to `create_superadmin.php` for security purposes.

---

## Method 2: Using Auto-Verification Emails

The system has built-in auto-verification for specific email addresses:

### Auto-Verified Emails:
- `admin@gmail.com`
- `ferrerandy76@gmail.com`

### How it works:
1. Register using one of the auto-verified emails
2. The system will:
   - Skip email sending
   - Use a fixed OTP: `123456`
   - Auto-verify on the OTP page
3. Login immediately without waiting for email

---

## Account Types

The system supports 4 account types:

| Type | Value | Description |
|------|-------|-------------|
| Super Admin | 0 | Full system access, all privileges |
| HR Admin | 1 | HR management access |
| Staff | 2 | Limited staff access |
| Employee | 3 | Employee portal access |

---

## Database Tables

### logintbl
Stores login credentials:
- `LoginID` (Primary Key)
- `Email` (Unique)
- `Password` (Hashed)
- `Account_type` (0-3)

### candidates
Stores user profile information:
- `id` (Primary Key)
- `full_name`
- `email` (Links to logintbl)
- `status`
- `source`

---

## Troubleshooting

### Issue: "Field 'position' doesn't have a default value"
**Solution**: This error has been fixed. The system now automatically provides default values for all required database fields (experience_years, age, contact_number, address). If you still encounter this error, ensure you're using the latest version of `create_superadmin.php`.

### Issue: "Email is already registered"
**Solution**: The email is already in use. Use a different email or delete the existing account from the database.

### Issue: "Database connection error"
**Solution**: Check your database connection settings in `Database/Connections.php`

### Issue: Can't access create_superadmin.php
**Solution**: Make sure the file exists in the root directory of your HR1 system.

---

## Security Best Practices

1. **Delete the setup page** after creating your Super Admin account
2. **Use strong passwords** (minimum 8 characters, mix of letters, numbers, symbols)
3. **Change default auto-verify emails** in production
4. **Enable HTTPS** for your production server
5. **Regularly backup** your database
6. **Monitor login attempts** for suspicious activity

---

## Quick Start Commands

### Create Super Admin via Browser:
```
http://localhost/hr1/create_superadmin.php
```

### Login Page:
```
http://localhost/hr1/login.php
```

---

## Support

For issues or questions, contact your system administrator.

**Created**: February 11, 2026  
**Version**: 1.1  
**Last Updated**: February 12, 2026

---

## Changelog

### Version 1.1 (February 12, 2026)
**Fixed**: Database error "Field 'position' doesn't have a default value"

**Changes Made:**
- Updated all INSERT statements to the `candidates` table to include all required NOT NULL fields
- Added default values for: `experience_years` (0), `age` (0), `contact_number` ('N/A'), `address` ('N/A')
- Ensured `position` field is always provided with appropriate value

**Files Updated:**
1. `create_superadmin.php` - Super admin account creation
2. `Super-admin/Modules/Job_posting.php` - Online job applications
3. `Modules/job_posting.php` - Walk-in job applications  
4. `login.php` - User registration verification
5. `Employee/Verification.php` - Employee registration

**Database Schema:**
The `candidates` table requires these NOT NULL fields:
- `full_name` - Candidate's full name
- `job_title` - Job title or role
- `position` - Position applied for
- `experience_years` - Years of experience (default: 0)
- `age` - Candidate's age (default: 0)
- `contact_number` - Phone number (default: 'N/A')
- `address` - Address (default: 'N/A')

