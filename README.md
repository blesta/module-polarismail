# PolarisMail Blesta Module

A fully-featured email hosting module for Blesta, providing integration with the PolarisMail email platform.

## Overview

The PolarisMail Blesta module enables hosting providers to offer professional email services through their Blesta installation. It provides comprehensive email account management, including multiple account types, aliases, forwarding, distribution lists, DKIM configuration, and custom branding.

## Features

### Core Functionality
- ✅ **Automated Provisioning**: Automatic domain setup, suspension, and termination
- ✅ **Multiple Account Types**: Support for Basic and Enhanced mailboxes
- ✅ **Quota Management**: Flexible quota allocation and tracking
- ✅ **Account Management**: Full CRUD operations for email accounts
- ✅ **Bulk Operations**: CSV import for batch account creation

### Email Features
- ✅ **Email Aliases**: Create unlimited aliases (based on package limits)
- ✅ **Email Forwarding**: Internal and external forwarding support
- ✅ **Distribution Lists**: Manage email distribution lists and members
- ✅ **DKIM Support**: Enable and configure DKIM signing
- ✅ **Custom Branding**: White-label email interface with logo and colors


## Quick Start

### Installation

1. Upload the `blesta` directory to your Blesta installation:
   ```bash
   # Upload the entire blesta/ directory to:
   /path/to/blesta/components/modules/
   # This will create: /path/to/blesta/components/modules/polarismail/
   ```

2. Install the module:
   - Navigate to **Settings → Company → Modules → Available**
   - Find **PolarisMail** and click **Install**

3. Configure a server:
   - Go to **Settings → Company → Modules → Installed**
   - Click **Manage** next to PolarisMail
   - Click **Add Server** and enter your API credentials

4. Create a package:
   - Navigate to **Packages → Browse Packages**
   - Click **Create Package**
   - Select **PolarisMail** as the module
   - Configure package options


## Module Structure

```
blesta/                          # Upload this directory to /components/modules/
└── polarismail/                 # Module directory (auto-created when uploading)
    ├── polarismail.php          # Main module class
    ├── config.json              # Module metadata
    ├── apis/
    │   └── polarismail_api.php # API client library
    ├── language/
    │   └── en_us/
    │       └── polarismail.php  # English translations
    ├── views/
    │   └── default/
    │       ├── manage.pdt       # Module management view
    │       ├── add_row.pdt      # Add server view
    │       ├── edit_row.pdt     # Edit server view
    │       └── tab_client_*.pdt # Client service tabs
    └── assets/
        ├── css/
        │   └── polarismail.css  # Module styles
        └── js/
            └── polarismail.js   # Module JavaScript
```

Use the following **Blesta Configuration Options** names so PolarismMail can detect limits correctly.

> In Blesta, create these under **Packages → Configuration Options**, then assign them to your PolarismMail package.

#### Mailboxes

| Blesta Option Label | Blesta Option Name (recommended) | Type | Typical Min/Max |
|---|---|---|---|
| Basic Mailboxes | `basic_mailboxes` | Quantity | Min `1`, Max `100` |
| Enhanced Mailboxes | `enhanced_mailboxes` | Quantity | Min `0`, Max `100` |

#### Aliases and Lists

| Blesta Option Label | Blesta Option Name (recommended) | Type | Typical Min/Max |
|---|---|---|---|
| Total Aliases | `total_aliases` | Quantity | Min `0`, Max `500` |
| Total Distribution Lists | `total_distribution_lists` | Quantity | Min `0`, Max `50` |

#### Quota

| Blesta Option Label | Blesta Option Name (recommended) | Type | Notes |
|---|---|---|---|
| Mailbox Quota (GB) | `mailbox_quota_gb` | Quantity or Select | Per-mailbox quota |
| Total Mailbox Quota (GB) | `total_mailbox_quota_gb` | Quantity | Total quota pool across accounts |

#### Optional Account Type Override

| Blesta Option Label | Blesta Option Name (recommended) | Type | Allowed Values |
|---|---|---|---|
| Default Account Type | `default_account_type` | Select / Quantity | `1` = Basic, `2` = Enhanced |

## Requirements

- **Blesta**: 5.0 or higher
- **PHP**: 7.4 or higher
- **cURL**: PHP cURL extension enabled
- **PolarisMail Account**: Active API credentials

