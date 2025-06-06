# Drivers Module Documentation

## Table of Contents
- [Overview](#overview)
- [Getting Started](#getting-started)
- [User Interface Guide](#user-interface-guide)
- [Managing Drivers](#managing-drivers)
- [Permissions & Access Control](#permissions--access-control)
- [Field Specifications](#field-specifications)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)
- [Technical Information](#technical-information)

## Overview

The Drivers Module is a comprehensive driver management system within the Retail ERP platform. This module allows you to manage delivery drivers, track their information, and control their status within your organization.

### Key Features
- **Driver Registration**: Add new drivers with complete profile information
- **Profile Management**: Edit and update driver details
- **Status Control**: Activate or deactivate drivers as needed
- **Search & Filter**: Quick search functionality to find specific drivers
- **Company Scoping**: Drivers are automatically scoped to your company
- **Mobile Optimization**: Responsive interface for all device types

### Module Purpose
This module serves as a central hub for managing delivery personnel, ensuring you have up-to-date contact information, identification details, and status tracking for all drivers associated with your company.

## Getting Started

### Prerequisites
To access the Drivers Module, you need:
- Valid user account with appropriate permissions
- Company-level access (drivers are company-specific)
- Web browser with JavaScript enabled

### Accessing the Module
1. Log into the ERP system
2. Navigate to the main dashboard
3. Look for "Drivers" in the navigation menu
4. Click to access the Drivers Module

## User Interface Guide

### Main Driver List Page

The main interface displays all drivers in a clean, organized table format:

#### Header Section
- **Search Bar**: Located at the top for quick driver searches
- **Add Driver Button**: Primary action button to create new driver profiles
- **Filter Options**: Status-based filtering (Active/Inactive)

#### Driver Table Columns
- **Name**: Driver's full name
- **ID Number**: Unique identification number
- **Email**: Contact email address
- **Mobile**: Phone number with country code
- **Status**: Current active/inactive status
- **Actions**: Edit and status change options

#### Pagination
- Bottom pagination controls for large driver lists
- Configurable results per page
- Quick navigation to first, last, previous, and next pages

## Managing Drivers

### Adding a New Driver

1. **Access the Form**
   - Click the "Add Driver" button on the main page
   - You'll be redirected to the driver creation form

2. **Fill Required Information**
   - **Name**: Enter the driver's full name
   - **ID Number**: Provide unique identification number
   - **Email**: Add valid email address
   - **Mobile Number**: Enter phone number
   - **Country Code**: Select appropriate country code

3. **Submit the Form**
   - Review all entered information
   - Click "Save" to create the driver profile
   - System will validate and save the new driver

4. **Confirmation**
   - Success message will appear upon successful creation
   - You'll be redirected to the drivers list
   - New driver will appear in the table

### Editing Driver Information

1. **Locate the Driver**
   - Use search or browse the driver list
   - Find the driver you want to edit

2. **Access Edit Mode**
   - Click the "Edit" button in the actions column
   - Driver form will open with pre-filled information

3. **Make Changes**
   - Modify any necessary fields
   - All fields except system-generated ones are editable

4. **Save Changes**
   - Click "Update" to save modifications
   - System will validate and update the driver profile

### Managing Driver Status

#### Activating/Deactivating Drivers

1. **Quick Status Toggle**
   - Click the status toggle in the actions column
   - Confirmation dialog will appear
   - Confirm the status change

2. **Status Options**
   - **Active**: Driver is available for assignments
   - **Inactive**: Driver is temporarily unavailable

#### Status Change Effects
- **Active Drivers**: Appear in assignment lists and searches
- **Inactive Drivers**: Hidden from active operations but retained in system

### Searching and Filtering

#### Search Functionality
- **Global Search**: Search across name, email, ID number, and mobile
- **Real-time Results**: Search updates as you type
- **Case Insensitive**: Search works regardless of letter case

#### Filter Options
- **Status Filter**: Show only active or inactive drivers
- **Company Scope**: Automatically filtered to your company

#### Search Tips
- Use partial names for broader results
- Search by ID number for exact matches
- Email searches work with partial domains

## Permissions & Access Control

### Required Permissions

The Drivers Module uses a role-based permission system:

#### Driver Read Permission
- **Access**: View driver list and details
- **Functionality**:
  - Browse all company drivers
  - View driver profiles
  - Use search and filter features

#### Driver Write Permission
- **Access**: Create new drivers
- **Functionality**:
  - Add new driver profiles
  - All read permissions included

#### Driver Modify Permission
- **Access**: Edit existing drivers
- **Functionality**:
  - Update driver information
  - Change driver status
  - All read and write permissions included

### Permission Hierarchy
```
Driver Modify > Driver Write > Driver Read
```

### Access Scenarios

| Action | Read | Write | Modify |
|--------|------|-------|--------|
| View driver list | ✓ | ✓ | ✓ |
| Search drivers | ✓ | ✓ | ✓ |
| View driver details | ✓ | ✓ | ✓ |
| Add new driver | ✗ | ✓ | ✓ |
| Edit driver info | ✗ | ✗ | ✓ |
| Change status | ✗ | ✗ | ✓ |

## Field Specifications

### Driver Information Fields

#### Name
- **Type**: Text input
- **Required**: Yes
- **Max Length**: 255 characters
- **Validation**: Must contain at least 2 characters
- **Format**: Free text, supports international characters

#### ID Number
- **Type**: Text input
- **Required**: Yes
- **Max Length**: 50 characters
- **Validation**: Must be unique within the company
- **Format**: Alphanumeric, typically official identification number

#### Email Address
- **Type**: Email input
- **Required**: Yes
- **Validation**: Valid email format required
- **Uniqueness**: Must be unique within the company
- **Format**: standard@email.format

#### Mobile Number
- **Type**: Numeric input
- **Required**: Yes
- **Validation**: Valid phone number format
- **Length**: Varies by country
- **Format**: Numbers only, no special characters

#### Country Code
- **Type**: Dropdown selection
- **Required**: Yes
- **Options**: International country codes
- **Default**: Based on system locale
- **Format**: Standard country code format (+1, +44, etc.)

### System Fields

#### Status
- **Type**: Enum (Active/Inactive)
- **Default**: Active
- **Values**:
  - `active`: Driver is available
  - `inactive`: Driver is unavailable

#### Company ID
- **Type**: System-generated
- **Source**: Current user's company
- **Purpose**: Data scoping and security

#### Created By
- **Type**: System-generated
- **Source**: Current authenticated user
- **Purpose**: Audit trail and accountability

#### Timestamps
- **Created At**: Automatic timestamp on creation
- **Updated At**: Automatic timestamp on modification

## Best Practices

### Data Entry Best Practices

1. **Consistent Naming**
   - Use full names, not nicknames
   - Maintain consistent name formatting
   - Include proper capitalization

2. **Accurate Contact Information**
   - Verify email addresses before entry
   - Double-check mobile numbers
   - Ensure country codes match phone numbers

3. **Unique Identification**
   - Use official ID numbers when possible
   - Avoid duplicate ID numbers
   - Maintain consistent ID format

### Operational Best Practices

1. **Regular Updates**
   - Review driver information quarterly
   - Update contact details promptly
   - Remove inactive drivers when appropriate

2. **Status Management**
   - Set drivers to inactive rather than deleting
   - Document reason for status changes
   - Regular review of inactive drivers

3. **Search Optimization**
   - Use specific search terms for faster results
   - Utilize filters to narrow down results
   - Bookmark frequently accessed drivers

### Security Best Practices

1. **Data Privacy**
   - Only access driver information when necessary
   - Protect sensitive driver data
   - Follow company data protection policies

2. **Permission Management**
   - Request only necessary permissions
   - Report permission issues to administrators
   - Regular review of access levels

## Troubleshooting

### Common Issues and Solutions

#### Cannot Add New Driver

**Problem**: Add Driver button is not visible or functional
**Solutions**:
- Check if you have Driver Write permissions
- Refresh the page and try again
- Contact system administrator for permission review

#### Email/ID Already Exists Error

**Problem**: System reports duplicate email or ID number
**Solutions**:
- Search for existing driver with same details
- Verify information accuracy
- Contact administrator if duplicate appears to be system error

#### Search Not Working

**Problem**: Search function returns no results
**Solutions**:
- Check search term spelling
- Try partial search terms
- Clear search and try again
- Verify driver exists in your company

#### Status Change Not Saving

**Problem**: Driver status reverts after change
**Solutions**:
- Check if you have Driver Modify permissions
- Ensure stable internet connection
- Try refreshing and attempting again

#### Form Validation Errors

**Problem**: Form won't submit due to validation errors
**Solutions**:
- Review all required fields
- Check email format validity
- Verify mobile number format
- Ensure ID number uniqueness

### Error Messages

#### Validation Errors
- **"Name is required"**: Enter driver's full name
- **"Invalid email format"**: Use valid email address format
- **"ID number already exists"**: Choose unique identification number
- **"Mobile number is required"**: Provide valid phone number

#### Permission Errors
- **"Access Denied"**: Contact administrator for proper permissions
- **"Insufficient Privileges"**: Request appropriate access level

#### System Errors
- **"Network Error"**: Check internet connection and retry
- **"Server Error"**: Contact technical support
- **"Session Expired"**: Log out and log back in

### Getting Help

#### Internal Support
1. Check this documentation first
2. Contact your system administrator
3. Submit help desk ticket
4. Reach out to IT support team

#### Technical Support
- Include error messages in support requests
- Provide step-by-step reproduction steps
- Mention your user role and permissions
- Include screenshots when helpful

## Technical Information

### Module Architecture

The Drivers Module follows Domain-Driven Design principles:

#### Key Components
- **Model**: `app/Models/Driver.php`
- **Data Object**: `app/Domains/Driver/DataObjects/DriverData.php`
- **Controller**: `app/Http/Controllers/Admin/DriverController.php`
- **Queries**: `app/Domains/Driver/DriverQueries.php`
- **Resource**: `app/Domains/Driver/Resources/DriverResource.php`

#### Frontend Components
- **List View**: `resources/js/admin/pages/drivers/Index.vue`
- **Form View**: `resources/js/admin/pages/drivers/Manage.vue`

### Database Structure

#### Driver Table Fields
- `id`: Primary key
- `company_id`: Foreign key to companies table
- `name`: Driver's full name
- `id_number`: Unique identification number
- `email`: Contact email address
- `mobile_number`: Phone number
- `country_code`: International country code
- `status`: Enum (active/inactive)
- `created_by_type`: Type of user who created record
- `created_by_id`: ID of user who created record
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

#### Relationships
- **Company**: Belongs to company (many-to-one)
- **Creator**: Polymorphic relationship to user who created driver

### API Endpoints

#### Driver Management Routes
- `GET /admin/drivers`: List all drivers
- `GET /admin/drivers/create`: Show create form
- `POST /admin/drivers`: Store new driver
- `GET /admin/drivers/{driver}/edit`: Show edit form
- `PUT /admin/drivers/{driver}`: Update driver
- `PUT /admin/drivers/{driver}/change-status`: Change driver status

#### AJAX Endpoints
- `GET /admin/drivers/fetch-drivers`: Fetch drivers with pagination and search

### Validation Rules

The system implements comprehensive validation:

#### DriverData Validation
```php
'name' => 'required|string|max:255'
'id_number' => 'required|string|max:50|unique:drivers,id_number'
'email' => 'required|email|unique:drivers,email'
'mobile_number' => 'required|string'
'country_code' => 'required|string'
'status' => 'required|in:active,inactive'
```

### Testing Coverage

The module includes comprehensive tests:

#### Test Types
- **Unit Tests**: DriverController functionality
- **Feature Tests**: DriverQueries operations
- **Validation Tests**: DriverData validation rules

#### Test Files
- `tests/Unit/admin/DriverControllerTest.php`
- `tests/Feature/Domains/Driver/DriverQueriesTest.php`
- `tests/Feature/Domains/Driver/DriverDataValidationTest.php`

---

## Changelog

### Version History
- **v1.0**: Initial driver management functionality
- **v1.1**: Added status management
- **v1.2**: Enhanced search and filtering
- **v1.3**: Improved mobile responsiveness

---

## Support

For additional support or questions about the Drivers Module:
- **Documentation**: Refer to this guide
- **Technical Issues**: Contact IT support
- **Feature Requests**: Submit through proper channels
- **Training**: Request user training sessions