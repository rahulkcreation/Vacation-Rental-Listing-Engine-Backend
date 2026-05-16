# Listing Engine Backend

`Listing Engine Backend` is a WordPress admin plugin for managing vacation-rental listing data through dedicated database tables and custom wp-admin interfaces. Instead of using custom post types, it maintains its own schema for listing types, amenities, locations, properties, property images, and blocked dates.

Current plugin version: `2.5.8`

## Contents

- [Project Scope](#project-scope)
- [What This Plugin Does](#what-this-plugin-does)
- [What This Plugin Does Not Do](#what-this-plugin-does-not-do)
- [Technology Profile](#technology-profile)
- [Architecture Overview](#architecture-overview)
- [Bootstrap and Load Flow](#bootstrap-and-load-flow)
- [Admin Modules](#admin-modules)
- [Database Lifecycle](#database-lifecycle)
- [Database Schema](#database-schema)
- [Data Storage Conventions](#data-storage-conventions)
- [Property Workflow](#property-workflow)
- [AJAX Action Reference](#ajax-action-reference)
- [Validation and Business Rules](#validation-and-business-rules)
- [Security Model](#security-model)
- [SVG and Media Handling](#svg-and-media-handling)
- [Asset Loading Strategy](#asset-loading-strategy)
- [Project Structure](#project-structure)
- [Installation and First-Time Setup](#installation-and-first-time-setup)
- [Day-to-Day Admin Usage](#day-to-day-admin-usage)
- [Extension Points](#extension-points)
- [Known Limitations and Implementation Notes](#known-limitations-and-implementation-notes)
- [Troubleshooting](#troubleshooting)

## Project Scope

This plugin is the backend management layer for a vacation-rental system. Its responsibility is to let administrators:

- create and manage lookup data such as property types, amenities, and locations
- create and manage actual property listings
- attach property galleries and blocked dates to listings
- create or repair the required custom database tables from the WordPress admin

It is built as an admin-focused data-management system, not as a full end-user booking platform.

## What This Plugin Does

- Registers a top-level `LEB` admin menu with dedicated sub-pages
- Creates custom admin dashboards for:
  - Types
  - Database
  - Amenities
  - Locations
  - Properties
- Provides AJAX-powered CRUD operations for all supported modules
- Uses dedicated MySQL tables instead of WordPress posts
- Supports icon selection for amenities and locations via the WordPress Media Library
- Supports property gallery selection, drag-reorder, and blocked-date management
- Supports listing duplication, bulk deletion, and bulk status changes
- Includes a shared toaster notification system and confirmation modal component

## What This Plugin Does Not Do

- It does not render a public frontend booking interface
- It does not expose REST API routes in the current codebase
- It does not auto-create tables on plugin activation
- It does not include a payment, reservation, or checkout system
- It does not include an automated test suite
- It does not include a Composer, npm, or build-tool pipeline

## Technology Profile

- Platform: WordPress plugin
- Language: PHP and vanilla JavaScript
- Styling: plain CSS
- Data access: direct `$wpdb` queries and `dbDelta()`
- UI model: custom wp-admin templates plus AJAX
- Media integration: WordPress Media Library
- Minimum practical PHP version: `7.4+`
  - the codebase uses typed properties such as `private string $types_table`

## Architecture Overview

The plugin is organized around a simple layered structure:

1. `listing-engine-backend.php`
   - plugin bootstrap
   - constants
   - dependency loading
   - activation and deactivation hooks
2. `includes/db-schema.php`
   - table definitions
   - default type seed filter
   - generic table-status checker
3. `includes/class-db-handler.php`
   - central CRUD and table-maintenance logic
4. `includes/admin-hooks.php`
   - admin menus
   - page render callbacks
   - AJAX handlers
   - SVG-related compatibility hooks
5. `includes/assets-loader.php`
   - selective CSS and JS loading for plugin admin pages
6. `templates/`
   - PHP markup for each admin screen
7. `assets/js/`
   - page-specific behavior and AJAX calls
8. `assets/css/`
   - page-specific styling
9. `components/`
   - shared UI systems such as toaster and confirmation modal

## Bootstrap and Load Flow

When WordPress loads the plugin:

1. `listing-engine-backend.php` defines plugin constants and enables `ALLOW_UNFILTERED_UPLOADS` if not already defined.
2. It loads:
   - `includes/db-schema.php`
   - `includes/class-db-handler.php`
   - `includes/assets-loader.php`
   - `includes/admin-hooks.php`
   - `includes/svg-support.php`
   - `includes/template-helpers.php`
3. Activation and deactivation only call `flush_rewrite_rules()`.
4. No table is created on activation.
5. Actual table creation happens later through the `Database` admin screen or by calling the handler methods directly.

## Admin Modules

### 1. Types

Purpose:

- maintain property types such as Apartment, Villa, Cabin, etc.

Features:

- paginated AJAX list
- search by name or slug
- add and edit form
- slug generation on the client side
- duplicate slug prevention on the server side
- bulk delete

Primary files:

- `templates/type-model/type-management.php`
- `templates/type-model/add-edit-type.php`
- `assets/js/type-model/type-management.js`
- `assets/js/type-model/add-edit-type.js`

### 2. Database

Purpose:

- let admins inspect whether the required tables exist
- create or repair tables individually

Tracked tables:

- `{prefix}ls_types`
- `{prefix}ls_amenities`
- `{prefix}ls_location`
- `{prefix}ls_property`
- `{prefix}ls_img`
- `{prefix}ls_block_date`

Primary files:

- `templates/database-page.php`
- `assets/js/database-page.js`

### 3. Amenities

Purpose:

- maintain reusable amenity definitions such as WiFi, Pool, Parking, etc.

Features:

- paginated AJAX list
- search by name
- add and edit form
- icon selection via Media Library
- icon validation
- bulk delete

Primary files:

- `templates/amenity-model/amenity-management.php`
- `templates/amenity-model/add-edit-amenity.php`
- `assets/js/amenity-model/amenity-management.js`
- `assets/js/amenity-model/add-edit-amenity.js`

### 4. Locations

Purpose:

- maintain reusable locations or destination entries such as Dubai, Goa, New York, etc.

Features:

- paginated AJAX list
- search by name or slug
- add and edit form
- client-side slug generation
- icon selection via Media Library
- duplicate slug prevention
- bulk delete

Primary files:

- `templates/location-model/location-management.php`
- `templates/location-model/add-edit-location.php`
- `assets/js/location-model/location-management.js`
- `assets/js/location-model/add-edit-location.js`

### 5. Properties

Purpose:

- manage actual vacation-rental listings

Features:

- paginated dashboard
- live search
- status tabs
- bulk publish, draft, and delete
- listing duplication
- add and edit property form
- Media Library-based gallery selection
- drag-and-drop image ordering
- dual-month or mobile-month blocked-date calendar
- draft autosave for new listings

Primary files:

- `templates/property-model/property-management.php`
- `templates/property-model/add-edit-property.php`
- `assets/js/property-model/property-management.js`
- `assets/js/property-model/add-edit-property.js`

## Database Lifecycle

This is one of the most important behaviors in the codebase:

- plugin activation does not create tables
- the schema is intentionally provisioned manually from the `LEB > Database` screen
- each table has a `Create / Repair` action
- status checks are done through `leb_check_table_status()`
- types can optionally be seeded through the `leb_default_type_rows` filter

That means a fresh install is not fully ready immediately after activation. An administrator must open the database screen and create or repair the required tables first.

## Database Schema

All table names below use `{prefix}` to represent the active WordPress table prefix.

### `{prefix}ls_types`

Purpose:

- stores property types

Columns:

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `bigint unsigned` | primary key |
| `name` | `varchar(255)` | type display name |
| `slug` | `varchar(255)` | unique slug |
| `updated_at` | `datetime` | auto-updated timestamp |

Important notes:

- `slug` is unique
- default rows are optional and controlled by `leb_default_type_rows`

### `{prefix}ls_amenities`

Purpose:

- stores reusable amenity records

Columns:

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `bigint unsigned` | primary key |
| `name` | `varchar(255)` | amenity name |
| `svg_path` | `varchar(2048)` | icon payload, usually JSON-encoded path plus attachment ID |
| `updated_at` | `datetime` | auto-updated timestamp |

Important notes:

- the column name is `svg_path`, but the stored value can be JSON such as `{"path":"...","attachment_id":123}`

### `{prefix}ls_location`

Purpose:

- stores reusable location definitions

Columns:

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `bigint unsigned` | primary key |
| `name` | `varchar(255)` | location display name |
| `slug` | `varchar(255)` | unique slug |
| `svg_path` | `varchar(2048)` | icon payload, usually JSON-encoded path plus attachment ID |
| `updated_at` | `datetime` | auto-updated timestamp |

Important notes:

- `slug` is unique
- like amenities, `svg_path` usually stores a JSON payload rather than only a plain URL

### `{prefix}ls_property`

Purpose:

- stores the main property listing record

Columns:

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `bigint unsigned` | primary key |
| `host_id` | `bigint unsigned` | WordPress user ID of the creator |
| `title` | `varchar(255)` | listing title |
| `location` | `longtext` | selected location value, currently used as lookup ID string |
| `address` | `longtext` | full property address |
| `amenities` | `longtext` | serialized or JSON-like amenity ID list |
| `type` | `varchar(255)` | selected type value, currently used as lookup ID string |
| `guests` | `int` | guest capacity |
| `bedroom` | `int` | bedroom count |
| `bed` | `int` | bed count |
| `bathroom` | `int` | bathroom count |
| `description` | `longtext` | listing description |
| `price` | `bigint` | price per night |
| `map` | `longtext` | present in schema but not used by the current admin UI |
| `status` | `varchar(50)` | `draft`, `pending`, `published`, or `rejected` |
| `updated_at` | `datetime` | last update timestamp |

Important notes:

- `host_id` is automatically set to `get_current_user_id()` during creation
- the current admin UI does not expose a host selector
- the current admin UI does not expose a map field

### `{prefix}ls_img`

Purpose:

- stores gallery data for properties

Columns:

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `bigint unsigned` | primary key |
| `property_id` | `bigint unsigned` | related property ID |
| `image` | `text` | full gallery JSON for the property |

Important notes:

- this table stores one JSON gallery blob per property, not one row per image
- the gallery JSON typically includes attachment IDs, URLs, and sort order

### `{prefix}ls_block_date`

Purpose:

- stores blocked or unavailable dates for a property

Columns:

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `bigint unsigned` | primary key |
| `property_id` | `bigint unsigned` | related property ID |
| `dates` | `longtext` | blocked-date JSON |
| `created_at` | `datetime` | creation timestamp |

Important notes:

- this table stores one JSON date blob per property rather than one row per blocked day

## Data Storage Conventions

The codebase uses a few important storage conventions that are helpful to know before extending it:

- `amenities.svg_path` and `location.svg_path`
  - usually stored as JSON with `path` and `attachment_id`
  - some list endpoints normalize this back to a raw path for the admin UI
- `property.amenities`
  - stored as a string representation of the selected amenity IDs
  - in practice, the JavaScript submits a JSON string array
- `property.type` and `property.location`
  - stored as string values that represent lookup IDs
- `ls_img.image`
  - stores the full gallery JSON in a single row
- `ls_block_date.dates`
  - stores the full blocked-date array in a single row

## Property Workflow

When an administrator creates or edits a property, the data flow is:

1. The add/edit form loads all lookup data through AJAX:
   - all types
   - all locations
   - all amenities
2. The user fills in property details and selects media from the WordPress Media Library.
3. For new properties, autosave can create an initial draft after the title and other fields begin changing.
4. On submit, the frontend validates required fields.
5. The request is sent to:
   - `leb_listing_create_listing`, or
   - `leb_listing_update_listing`
6. `LEB_Database_Handler` writes:
   - the main listing row
   - the gallery JSON row
   - the blocked-date JSON row
7. The list dashboard later resolves:
   - type name through a join to `{prefix}ls_types`
   - username through a join to `wp_users`
   - thumbnail from the first image in the gallery JSON

Duplication flow:

- duplicating a property clones:
  - the main property row
  - the gallery row
  - the blocked-date row
- the duplicate title gets ` - Copy`
- the duplicate status is always reset to `draft`

Deletion flow:

- deleting a property also deletes:
  - gallery rows
  - blocked-date rows
  - associated Media Library attachments referenced in the gallery JSON

## AJAX Action Reference

All actions below are handled through `admin-ajax.php`.

Common requirements:

- capability: `manage_options`
- nonce: `leb_nonce`

### Types

| Action | Purpose |
| --- | --- |
| `leb_get_types` | list types with search and pagination |
| `leb_create_type` | create a type |
| `leb_update_type` | update a type |
| `leb_get_type` | fetch one type for edit prefill |
| `leb_delete_type` | delete one type |
| `leb_bulk_delete_types` | bulk delete types |

### Database

| Action | Purpose |
| --- | --- |
| `leb_db_status` | return status for all tracked plugin tables |
| `leb_db_create_repair` | create or repair a selected table |

### Amenities

| Action | Purpose |
| --- | --- |
| `leb_amen_get_amenities` | list amenities with search and pagination |
| `leb_amen_create_amenity` | create an amenity |
| `leb_amen_update_amenity` | update an amenity |
| `leb_amen_get_amenity` | fetch one amenity for edit prefill |
| `leb_amen_delete_amenity` | delete one amenity |
| `leb_amen_bulk_delete_amenities` | bulk delete amenities |

### Locations

| Action | Purpose |
| --- | --- |
| `leb_loc_get_locations` | list locations with search and pagination |
| `leb_loc_create_location` | create a location |
| `leb_loc_update_location` | update a location |
| `leb_loc_get_location` | fetch one location for edit prefill |
| `leb_loc_delete_location` | delete one location |
| `leb_loc_bulk_delete_locations` | bulk delete locations |

### Properties

| Action | Purpose |
| --- | --- |
| `leb_listing_get_listings` | list properties with search, status filter, and pagination |
| `leb_listing_get_listing` | fetch one listing and related data for edit |
| `leb_listing_create_listing` | create a property |
| `leb_listing_update_listing` | update a property |
| `leb_listing_delete_listing` | delete one property |
| `leb_listing_bulk_delete` | bulk delete properties |
| `leb_listing_bulk_status` | bulk set property status |
| `leb_listing_get_amenities_all` | fetch all amenities for the property form |
| `leb_listing_get_locations_all` | fetch all locations for the property form |
| `leb_listing_get_types_all` | fetch all types for the property form |
| `leb_listing_duplicate` | duplicate a property listing |

## Validation and Business Rules

### Type rules

- name is required
- slug is required
- slug is normalized with `sanitize_title()`
- duplicate slugs are rejected

### Amenity rules

- name is required
- icon is required
- attachment validation enforces:
  - SVG or WEBP MIME type
  - max file size `1 MB`
  - intended icon dimension `24x24`

### Location rules

- name is required
- slug is required
- icon is required
- slug is normalized with `sanitize_title()`
- duplicate slugs are rejected
- icon validation reuses the same validation logic as amenities

### Property rules

Frontend validation requires:

- title
- description
- property type
- location
- at least one amenity
- address
- at least 5 images
- maximum 10 images
- guests must be at least 1
- bedrooms, beds, and bathrooms must be 0 or more
- price must be greater than 0

Server-side image validation requires:

- image count between `5` and `10`
- MIME type must be one of:
  - `image/jpeg`
  - `image/webp`
  - `image/avif`
- max file size `1 MB` per image

Status values currently supported:

- `draft`
- `pending`
- `published`
- `rejected`

## Security Model

The plugin is not a public endpoint system; it is built for administrative use only.

Security measures present in the codebase:

- every admin page checks `current_user_can( 'manage_options' )`
- every AJAX handler verifies the `leb_nonce` nonce
- most inputs are sanitized with:
  - `sanitize_text_field()`
  - `sanitize_title()`
  - `esc_url_raw()`
  - `absint()`
  - `wp_kses_post()`
- database queries are routed through `$wpdb->prepare()` or safe `$wpdb` helpers
- listing media deletion uses WordPress native attachment deletion

## SVG and Media Handling

The plugin includes aggressive SVG compatibility logic so icons can be selected and displayed reliably in wp-admin.

Files involved:

- `includes/svg-support.php`
- SVG-related hooks inside `includes/admin-hooks.php`

Implemented behavior:

- registers SVG and SVGZ MIME support
- masks SVG uploads as `application/octet-stream` during early checks
- restores `image/svg+xml` after upload
- grants `unfiltered_upload` capability to administrators for SVG uploads
- skips SVG metadata generation to avoid image-processing failures
- fixes SVG preview data for the Media Library JavaScript response
- adds admin CSS so SVG previews display properly in the Media Library

Important note:

- the plugin explicitly defines `ALLOW_UNFILTERED_UPLOADS` if it is not already defined

## Asset Loading Strategy

Asset loading is intentionally scoped so plugin CSS and JS are not loaded on unrelated admin pages.

Key behaviors in `includes/assets-loader.php`:

- assets load only when `leb_is_leb_page()` matches the current admin hook suffix
- global design tokens are loaded through `components/global/global.css`
- shared components are always loaded on plugin pages:
  - toaster
  - confirmation modal
- page-specific assets are loaded conditionally per module
- cache busting uses `filemtime()` where available
- AJAX configuration is localized through the `LEB_Ajax` object

Localized values include:

- `ajax_url`
- `nonce`
- `manage_url`
- `assets_url`

## Project Structure

```text
Vacation-Rental-Listing-Engine-Backend/
├── listing-engine-backend.php
├── README.md
├── includes/
│   ├── admin-hooks.php
│   ├── assets-loader.php
│   ├── class-db-handler.php
│   ├── db-schema.php
│   ├── svg-support.php
│   └── template-helpers.php
├── templates/
│   ├── database-page.php
│   ├── type-model/
│   ├── amenity-model/
│   ├── location-model/
│   └── property-model/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── components/
    ├── confirmation/
    ├── global/
    └── toaster/
```

Directory responsibilities:

- `includes/`
  - backend logic and bootstrap helpers
- `templates/`
  - admin page markup
- `assets/js/`
  - page behavior, AJAX requests, client-side validation
- `assets/css/`
  - page styling
- `components/`
  - reusable UI systems

## Installation and First-Time Setup

1. Copy the plugin into your WordPress plugins directory.
2. Activate the plugin in WordPress.
3. Sign in with an administrator account.
4. Open `LEB > Database`.
5. Create or repair all required tables:
   - Types
   - Amenities
   - Locations
   - Property
   - Images
   - Block Dates
6. Add the lookup data first:
   - Types
   - Amenities
   - Locations
7. Start creating properties from `LEB > Properties`.

Recommended first-run order:

1. Database
2. Types
3. Amenities
4. Locations
5. Properties

## Day-to-Day Admin Usage

### Managing types

- open `LEB > Types`
- search existing records
- add a new type or edit an existing one
- use bulk delete when cleaning up obsolete types

### Managing amenities and locations

- upload or choose icons from the Media Library
- keep icon sizes consistent at `24x24`
- use names that match how the frontend will label data

### Managing properties

- create lookup data before creating a property
- upload between 5 and 10 listing images
- set the correct status for workflow visibility
- use duplication for quick cloning of similar properties
- use blocked dates to mark unavailable days

## Extension Points

Current extension points and safe customization areas:

- `leb_default_type_rows`
  - filter for seeding default type entries after table creation
- `LEB_Database_Handler`
  - reusable backend class for CRUD and schema operations
- `templates/`
  - best place to expand admin UI markup
- `assets/js/`
  - best place to expand client-side interactions
- `assets-loader.php`
  - central place to register any new module assets
- `admin-hooks.php`
  - central place to register new admin pages or AJAX handlers

If you add a new data module, the current codebase pattern is:

1. add schema function to `db-schema.php`
2. add handler methods to `class-db-handler.php`
3. add menu and AJAX hooks to `admin-hooks.php`
4. add page template
5. add JS and CSS
6. register conditional asset loading in `assets-loader.php`

## Known Limitations and Implementation Notes

These are important codebase realities to be aware of before extending the plugin:

- table creation is manual
  - activation does not provision schema
- the plugin is admin-only
  - there is no frontend rendering layer in this repository
- property search is currently title-based at the SQL layer
  - the dashboard placeholder mentions host search, but the query only filters by title
- `ls_property.map` exists in the schema but is not used by the current forms or AJAX payloads
- gallery data is stored as a JSON blob inside a single `ls_img` row per property
  - this is convenient for the current UI but not normalized
- blocked dates are stored as a JSON blob inside a single `ls_block_date` row per property
- deleting a property permanently deletes its referenced Media Library attachments
- autosave only creates the initial draft for a new listing
  - once a listing already has an ID, the autosave routine stops creating follow-up autosaves
- the image-validation helper comment references dimension checks, but the current server-side implementation only enforces count, MIME type, and file size
- amenity and location icon validation accepts SVG and WEBP attachments in backend validation, even though the UI text mainly describes SVG usage

## Troubleshooting

### Tables are missing after activation

Cause:

- this is expected behavior

Fix:

- open `LEB > Database`
- click `Create / Repair` for each required table

### SVG uploads fail in wp-admin

Check:

- you are logged in as an administrator
- the file is actually SVG or SVGZ
- the icon is within the expected size limits

### Amenity or location icon is not showing

Check:

- the stored `svg_path` payload contains a valid `path`
- the attachment still exists in the Media Library
- the uploaded icon is `24x24`

### Property creation fails

Check:

- at least 5 images are selected
- each image is JPEG, WEBP, or AVIF
- each image is not larger than `1 MB`
- type, location, address, price, and title are all present
- the custom tables already exist

### Bulk actions do not work

Check:

- you are logged in as an admin user
- the page loaded the localized nonce correctly
- browser console and network requests to `admin-ajax.php` show no nonce or permission errors

## Summary

This repository is a custom WordPress backend administration engine for vacation-rental inventory. Its strongest characteristics are:

- dedicated custom tables instead of posts
- custom admin UX for lookup data and properties
- schema create/repair tools in wp-admin
- strong media-driven workflows for icons and galleries
- straightforward extension path for additional modules

If you are onboarding a new developer, the most important files to read first are:

1. `listing-engine-backend.php`
2. `includes/admin-hooks.php`
3. `includes/class-db-handler.php`
4. `includes/db-schema.php`
5. `includes/assets-loader.php`

