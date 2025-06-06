# Azentio OneERP Integration - User Guide

## Table of Contents

1. [What is the Azentio Integration?](#what-is-the-azentio-integration)
2. [Getting Started](#getting-started)
3. [How It Works](#how-it-works)
   - [Product Sync Process](#the-product-sync-process---what-happens-behind-the-scenes)
   - [Member Sync Process](#the-member-sync-process---what-happens-behind-the-scenes)
4. [Product Information](#what-information-gets-transferred)
5. [Important Rules](#important-requirements)
6. [Member Information](#what-information-gets-transferred-1)

## What is the Azentio Integration?

The Azentio integration connects our Retail ERP system with the Azentio OneERP platform. This connection allows product information (like name, color, size, upc) to be automatically copied from Azentio into our system, so you don't have to enter the same information twice.

Think of it as an automatic copying assistant that works overnight to keep product information in both systems matching and up-to-date.

## Getting Started

Before you can use this feature, you need:

1. **Set up a connection**:
   - Go to the Integration section in your super admin panel
   - Select "ONE ERP" as the connection type
   - Enter the website address (URL) provided by your Azentio account manager
   - Enter the secret key provided by Azentio
   - Click "Save" to store these connection details

2. **Set the starting date**:
   - If you're unsure about this setting, please contact your system administrator
   - This tells the system from which date it should start collecting product information

## How It Works

### The Product Sync Process - What Happens Behind the Scenes

1. **Daily Updates**:
    - Every night at midnight, the system automatically connects to Azentio
    - It looks for any new or updated product information
    - These updates appear in your product catalog the next morning

2. **How the System Decides What to Copy**:
    - The system is smart about which products to update:
        - It remembers when it last successfully copied data
        - If it's the first time running, it uses the start date you provided
        - If no date was provided, it defaults to collecting yesterday's changes

3. **Handling Large Product Catalogs**:
    - The system transfers products in small groups (batches) to avoid overwhelming the system
    - It works through the entire product list methodically
    - For stores with many products, the process continues automatically until all products are updated

4. **Secure Connection**:
    - The system uses a digital "key" to securely connect to Azentio
    - This connection stays active for about an hour at a time

## What Information Gets Transferred?

When the integration runs, it copies the following information from Azentio to your ERP system:

- **Product Details**:
    - Basic Information: Name, Upc, Color, Size

All this information is used to create or update products in your catalog, saving you from having to manually enter this data.

## Important Requirements

For the integration to work properly, make sure:

1. **Brand Setup is Complete**:
    - Your company must have at least one brand set up
    - All products imported from Azentio will be assigned to your company's first brand
    - Without a brand, product imports will fail

2. **Product Codes Match**:
    - If a product already exists in both systems, they will be matched using the product code
    - Double-check that product codes are consistent across both systems

### The Member Sync Process - What Happens Behind the Scenes

1. **Daily Updates**:
    - Every night at midnight, the system automatically connects to Azentio
    - It looks for any new or updated member information
    - These updates appear in your member catalog the next morning

2. **How the System Decides What to Copy**:
    - The system is smart about which member to update:
        - It remembers when it last successfully copied data
        - If it's the first time running, it uses the start date you provided
        - If no date was provided, it defaults to collecting yesterday's changes

3. **Handling Large Member Catalogs**:
    - The system transfers members in small groups (batches) to avoid overwhelming the system
    - It works through the entire member list methodically
    - For stores with many members, the process continues automatically until all members are updated

4. **Secure Connection**:
    - The system uses a digital "key" to securely connect to Azentio
    - This connection stays active for about an hour at a time

## What Information Gets Transferred?

When the integration runs, it copies the following information from Azentio to your ERP system:

- **Member Details**:
    - Basic Information: Name, Mobile, Email
    - Mobile is taken as uniqueness check.

All this information is used to create or update member in your catalog, saving you from having to manually enter this data.
