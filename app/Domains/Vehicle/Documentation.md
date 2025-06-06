# Vehicles Module Documentation

## Table of Contents
- [Overview](#overview)
- [Getting Started](#getting-started)
- [User Interface Guide](#user-interface-guide)
- [Managing Vehicles](#managing-vehicles)
- [Permissions & Access Control](#permissions--access-control)
- [Field Specifications](#field-specifications)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)
- [Technical Information](#technical-information)

## Overview

The Vehicles Module is a comprehensive vehicle management system within the Retail ERP platform. This module allows you to manage delivery vehicles, track their information, and control their status within your organization.

### Key Features
- **Vehicle Registration**: Add new vehicles with complete profile information
- **Fleet Management**: Edit and update vehicle details
- **Status Control**: Activate or deactivate vehicles as needed
- **Search & Filter**: Quick search functionality to find specific vehicles
- **Company Scoping**: Vehicles are automatically scoped to your company
- **Mobile Optimization**: Responsive interface for all device types

### Module Purpose
This module serves as a central hub for managing the delivery fleet, ensuring you have up-to-date vehicle information, plate number tracking, and status management for all vehicles associated with your company.

## Getting Started

### Prerequisites
To access the Vehicles Module, you need:
- Valid user account with appropriate permissions
- Company-level access (vehicles are company-specific)
- Web browser with JavaScript enabled

### Accessing the Module
1. Log into the ERP system
2. Navigate to the main dashboard
3. Look for "Vehicles" in the navigation menu under Account Setup -> Company Structure
4. Click to access the Vehicles Module

## User Interface Guide

### Main Vehicle List Page

The main interface displays all vehicles in a clean, organized table format:

#### Header Section
- **Search Bar**: Located at the top for quick vehicle searches
- **Add Vehicle Button**: Primary action button to create new vehicle profiles
- **Filter Options**: Status-based filtering (Active/Inactive)

#### Vehicle Table Columns
- **Name**: Vehicle's name or identifier
- **Plate No**: Vehicle license plate number
- **Type of Vehicle**: Vehicle category or description
- **Status**: Current active/inactive status
- **Actions**: Edit and status change options

#### Pagination
- Bottom pagination controls for large vehicle lists
- Configurable results per page
- Quick navigation to first, last, previous, and next pages

## Managing Vehicles

### Adding a New Vehicle

1. **Access the Form**
   - Click the "Add Vehicle" button on the main page
   - You'll be redirected to the vehicle creation form

2. **Fill Required Information**
   - **Name**: Enter the vehicle's name or identifier
   - **Plate No**: Provide unique license plate number
   - **Type of Vehicle**: Add vehicle category or description (optional)
   - **Status**: Set initial status (Active by default)

3. **Submit the Form**
   - Review all entered information
   - Click "Save" to create the vehicle profile
   - System will validate and save the new vehicle

4. **Confirmation**
   - Success message will appear upon successful creation
   - You'll be redirected to the vehicles list
   - New vehicle will appear in the table

### Editing Vehicle Information

1. **Locate the Vehicle**
   - Use search or browse the vehicle list
   - Find the vehicle you want to edit

2. **Access Edit Mode**
   - Click the "Edit" button in the actions column
   - Vehicle form will open with pre-filled information

3. **Make Changes**
   - Modify any necessary fields
   - All fields except system-generated ones are editable

4. **Save Changes**
   - Click "Update" to save modifications
   - System will validate and update the vehicle profile

### Managing Vehicle Status

#### Activating/Deactivating Vehicles

1. **Quick Status Toggle**
   - Click the status toggle in the actions column
   - Confirmation dialog will appear
   - Confirm the status change

2. **Status Options**
   - **Active**: Vehicle is available for assignments
   - **Inactive**: Vehicle is temporarily unavailable

#### Status Change Effects
- **Active Vehicles**: Appear in assignment lists and searches
- **Inactive Vehicles**: Hidden from active operations but retained in system

### Searching and Filtering

#### Search Functionality
- **Global Search**: Search across name, plate number, and vehicle type
- **Real-time Results**: Search updates as you type
- **Case Insensitive**: Search works regardless of letter case

#### Filter Options
- **Status Filter**: Show only active or inactive vehicles
- **Company Scope**: Automatically filtered to your company

#### Search Tips
- Use partial names for broader results
- Search by plate number for exact matches
- Vehicle type searches work with partial descriptions

## Permissions & Access Control

### Required Permissions

The Vehicles Module uses a role-based permission system:

#### Vehicle Read Permission
- **Access**: View vehicle list and details
- **Functionality**:
  - Browse all company vehicles
  - View vehicle profiles
  - Use search and filter features

#### Vehicle Write Permission
- **Access**: Create new vehicles
- **Functionality**:
  - Add new vehicle profiles
  - All read permissions included

#### Vehicle Modify Permission
- **Access**: Edit existing vehicles
- **Functionality**:
  - Update vehicle information
  - Change vehicle status
  - All read and write permissions included

### Permission Hierarchy
```
Vehicle Modify > Vehicle Write > Vehicle Read
```

### Access Scenarios

| Action | Read | Write | Modify |
|--------|------|-------|--------|
| View vehicle list | ✓ | ✓ | ✓ |
| Search vehicles | ✓ | ✓ | ✓ |
| View vehicle details | ✓ | ✓ | ✓ |
| Add new vehicle | ✗ | ✓ | ✓ |
| Edit vehicle info | ✗ | ✗ | ✓ |
| Change status | ✗ | ✗ | ✓ |

## Field Specifications

### Vehicle Information Fields

#### Name
- **Type**: Text input
- **Required**: Yes
- **Max Length**: 255 characters
- **Validation**: Must contain at least 1 character
- **Format**: Free text, supports international characters

#### Plate No
- **Type**: Text input
- **Required**: Yes
- **Max Length**: 20 characters
- **Validation**: Must be unique within the company
- **Format**: Alphanumeric, license plate format

#### Type of Vehicle
- **Type**: Text input
- **Required**: No
- **Max Length**: 255 characters
- **Validation**: Optional field
- **Format**: Free text description (e.g., "Delivery Van", "Truck", "Motorcycle")

### System Fields

#### Status
- **Type**: Boolean (Active/Inactive)
- **Default**: Active (true)
- **Values**:
  - `true`: Vehicle is available
  - `false`: Vehicle is unavailable

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
   - Use clear, descriptive vehicle names
   - Maintain consistent name formatting
   - Include proper capitalization

2. **Accurate Plate Information**
   - Verify plate numbers before entry
   - Use standard plate number format
   - Ensure plate numbers are current and valid

3. **Unique Identification**
   - Use official plate numbers
   - Avoid duplicate plate numbers within company
   - Maintain consistent plate format

### Operational Best Practices

1. **Regular Updates**
   - Review vehicle information quarterly
   - Update vehicle details promptly
   - Remove inactive vehicles when appropriate

2. **Status Management**
   - Set vehicles to inactive rather than deleting
   - Document reason for status changes
   - Regular review of inactive vehicles

3. **Search Optimization**
   - Use specific search terms for faster results
   - Utilize filters to narrow down results
   - Bookmark frequently accessed vehicles

### Security Best Practices

1. **Data Privacy**
   - Only access vehicle information when necessary
   - Protect sensitive vehicle data
   - Follow company data protection policies

2. **Permission Management**
   - Request only necessary permissions
   - Report permission issues to administrators
   - Regular review of access levels

## Troubleshooting

### Common Issues and Solutions

#### Cannot Add New Vehicle

**Problem**: Add Vehicle button is not visible or functional
**Solutions**:
- Check if you have Vehicle Write permissions
- Refresh the page and try again
- Contact system administrator for permission review

#### Plate Number Already Exists Error

**Problem**: System reports duplicate plate number
**Solutions**:
- Search for existing vehicle with same plate number
- Verify plate number accuracy
- Contact administrator if duplicate appears to be system error

#### Search Not Working

**Problem**: Search function returns no results
**Solutions**:
- Check search term spelling
- Try partial search terms
- Clear search and try again
- Verify vehicle exists in your company

#### Status Change Not Saving

**Problem**: Vehicle status reverts after change
**Solutions**:
- Check if you have Vehicle Modify permissions
- Ensure stable internet connection
- Try refreshing and attempting again

#### Form Validation Errors

**Problem**: Form won't submit due to validation errors
**Solutions**:
- Review all required fields
- Check plate number format
- Ensure plate number uniqueness
- Verify name field is not empty

### Error Messages

#### Validation Errors
- **"Name is required"**: Enter vehicle's name or identifier
- **"Plate No is required"**: Provide valid license plate number
- **"Plate No already exists"**: Choose unique plate number
- **"Type of Vehicle too long"**: Reduce description length

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

The Vehicles Module follows Domain-Driven Design principles:

#### Key Components
- **Model**: `app/Models/Vehicle.php`
- **Data Object**: `app/Domains/Vehicle/DataObjects/VehicleData.php`
- **Controller**: `app/Http/Controllers/Admin/VehicleController.php`
- **Queries**: `app/Domains/Vehicle/VehicleQueries.php`
- **Resource**: `app/Domains/Vehicle/Resources/VehicleResource.php`

#### Frontend Components
- **List View**: `resources/js/admin/pages/vehicles/Index.vue`
- **Form View**: `resources/js/admin/pages/vehicles/Manage.vue`

### Database Structure

#### Vehicle Table Fields
- `id`: Primary key
- `company_id`: Foreign key to companies table
- `name`: Vehicle's name or identifier
- `plate_no`: License plate number
- `type_of_vehicle`: Vehicle category or description (nullable)
- `status`: Boolean (active/inactive)
- `created_by_type`: Type of user who created record
- `created_by_id`: ID of user who created record
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

#### Indexes
- Primary key on `id`
- Index on `company_id`
- Unique index on `company_id, plate_no` (unique plate per company)

#### Relationships
- **Company**: Belongs to company (many-to-one)
- **Creator**: Polymorphic relationship to user who created vehicle

### API Endpoints

#### Vehicle Management Routes
- `GET /admin/vehicles`: List all vehicles
- `GET /admin/vehicles/create`: Show create form
- `POST /admin/vehicles`: Store new vehicle
- `GET /admin/vehicles/{vehicle}/edit`: Show edit form
- `PUT /admin/vehicles/{vehicle}`: Update vehicle
- `PUT /admin/vehicles/{vehicle}/change-status`: Change vehicle status

#### AJAX Endpoints
- `GET /admin/vehicles/fetch-vehicles`: Fetch vehicles with pagination and search

### Validation Rules

The system implements comprehensive validation:

#### VehicleData Validation
```php
'name' => 'required|string|max:255'
'plate_no' => 'required|string|max:20|unique:vehicles,plate_no'
'type_of_vehicle' => 'nullable|string|max:255'
'status' => 'required|boolean'
```

### Testing Coverage

The module includes comprehensive tests:

#### Test Types
- **Feature Tests**: VehicleQueries operations
- **Validation Tests**: VehicleData validation rules

#### Test Files
- `tests/Feature/Domains/Vehicle/VehicleQueriesTest.php`
- `tests/Feature/Domains/Vehicle/VehicleDataValidationTest.php`

#### Test Coverage Statistics
- **VehicleQueriesTest**: 15 tests covering CRUD operations
- **VehicleDataValidationTest**: 10 tests covering validation scenarios
- **Total Assertions**: 47 assertions across all tests

---

## Changelog

### Version History
- **v1.0**: Initial vehicle management functionality with name, plate number, type, and status fields

---

## Support

For additional support or questions about the Vehicles Module:
- **Documentation**: Refer to this guide
- **Technical Issues**: Contact IT support
- **Feature Requests**: Submit through proper channels
- **Training**: Request user training sessions
