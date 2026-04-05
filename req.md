**WordPress Plugin Development Prompt - "Listing Engine Backend"**

---

## **Plugin Overview**
Create a WordPress plugin named **"Listing Engine Backend"** with the following metadata:
- **Plugin URI:** https://arttechfuzion.com
- **Author:** Art-Tech Fuzion
- **Naming Convention:** All functions, classes, hooks, and IDs must use the prefix `LEB` (e.g., `LEB_Database_Handler`, `leb_create_table`, `#leb-type-list`)

---

## **Core Architecture & Folder Structure**

```
listing-engine-backend/
├── listing-engine-backend.php          # Main plugin file
├── assets/
│   ├── css/
│   │   ├── global.css                  # Global styles with CSS variables
│   │   ├── leb-toaster.css             # Toaster notification styles
│   │   └── leb-admin.css               # Admin panel styles
│   └── js/
│       └── leb-toaster.js              # Toaster notification JS
├── includes/
│   ├── class-db-handler.php            # Database operations handler
│   ├── db-schema.php                   # Table definitions & update logic
│   └── assets-loader.php               # Centralized asset path management
├── templates/
│   ├── database-page.php               # Database management screen
│   └── type-management.php             # Type CRUD interface
└── screen-name/                        # UI reference images (provided by client)
    ├── reference-1.png
    └── reference-2.png
```

---

## **Database Management System**

### **1. No Auto-Creation on Activation**
- **DO NOT** create any database tables during plugin activation hook
- Tables should only be created manually via the admin interface

### **2. Database Sub-Menu Screen**
Create a sub-menu item labeled **"Database"** under the main LEB menu with the following features:

#### **UI Layout (Grid Block Design):**
- Display each database table as a **separate grid block/card**
- Each card must show:
  - **Table Title:** Name of the table (e.g., "Types Table")
  - **Status Indicators:**
    - ✅ **Table Created** / ❌ **Table Not Created**
    - ✅ **All Rows Present** / ⚠️ **Missing Rows** (if table exists but required default data is missing)
  - **Action Buttons:**
    - 🔄 **Refresh Button:** Re-checks current DB status (AJAX-based)
    - ➕ **Create/Repair Button:** Triggers table creation or row insertion

#### **Backend Logic Flow:**
```
User clicks "Create/Repair" 
    → AJAX request to class-db-handler.php 
    → Handler calls db-schema.php functions 
    → Executes CREATE TABLE IF NOT EXISTS 
    → Checks for missing default rows 
    → Inserts missing data 
    → Returns success/error response 
    → Shows toaster notification
```

### **3. db-schema.php Structure**
Define the following table schema:

**Table Name:** `wp_ls_types` (or `{prefix}ls_types`)

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT(20) | AUTO_INCREMENT, PRIMARY KEY | Unique identifier |
| `name` | VARCHAR(255) | NOT NULL | Type name (stored lowercase) |
| `slug` | VARCHAR(255) | NOT NULL, UNIQUE | URL-friendly slug (always lowercase) |
| `updated_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Last modification timestamp |

**Required Functions in db-schema.php:**
- `leb_get_types_schema()` – Returns CREATE TABLE SQL
- `leb_get_default_type_rows()` – Returns array of default entries (if any)
- `leb_check_table_status($table_name)` – Returns array with keys: `exists`, `rows_complete`

---

## **Admin Menu Structure**

### **1. Main Menu**
- **Menu Label:** "LEB"
- **Position:** Near "Comments" menu (use appropriate position integer)
- **Icon:** Use WordPress dashicon or custom SVG

### **2. Sub-Menus:**
1. **"Types"** (Primary management screen – see below)
2. **"Database"** (DB management screen described above)

### **3. Plugins Page Integration**
- Add a **"Settings" link** next to the **"Deactivate"** action on the installed plugins page
- Clicking this link should redirect to the LEB plugin's main settings page

---

## **Type Management System (CRUD)**

### **Screen: Types List View**

**Layout Components (Top to Bottom):**
1. **Page Heading:** "Manage Types" (or similar)
2. **Action Bar (Right-aligned):**
   - **[+ Add New Type]** Button – Opens add form (modal or new page)
3. **Search Bar:**
   - Real-time filtering functionality
   - **Trigger:** Starts filtering after **2 characters** are typed
   - Uses AJAX to fetch filtered results without page reload
4. **Data Table/List:**
   - Displays columns: ID, Name, Slug, Updated At, Actions
   - **Pagination:** Show **10 items per page**
   - Pagination controls at bottom (Previous, Page Numbers, Next)
5. **Row Actions:**
   - **Edit Button** (✏️) – Opens edit form pre-filled with current data

### **Screen: Add/Edit Type Form**

**Form Fields:**
- **Type Name** (text input, required)
- **Slug** (text input, auto-generated from name or manual entry)
- **Submit Buttons:**
  - **[Create Type]** (for new entries)
  - **[Update Type]** (for existing entries)

**Validation Rules:**
- Both fields are required
- **Slug must be converted to lowercase before saving** (use `sanitize_title()` or `strtolower()`)
- Check for duplicate slugs before insert/update
- Show validation errors via toaster notifications

**CRUD Operations:**
- **Create:** INSERT INTO `wp_ls_types`
- **Read:** SELECT with pagination (LIMIT/OFFSET) and search (LIKE query)
- **Update:** UPDATE `wp_ls_types` WHERE id = X
- **Delete:** (Optional – soft delete recommended)

---

## **Styling & Asset Management**

### **1. global.css (CSS Variables System)**
```css
:root {
    /* Font Family */
    --leb-font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    
    /* Color Palette */
    --leb-primary-color: #2271b1;      /* WordPress blue-ish */
    --leb-success-color: #00a32a;       /* Green for success states */
    --leb-warning-color: #dba617;       /* Amber for warnings */
    --leb-error-color: #d63638;         /* Red for errors */
    --leb-text-color: #1d2327;          /* Dark text */
    --leb-bg-light: #f6f7f7;            /* Light background */
    --leb-border-color: #dcdcde;        /* Border gray */
    
    /* Spacing */
    --leb-spacing-sm: 8px;
    --leb-spacing-md: 16px;
    --leb-spacing-lg: 24px;
    
    /* Border Radius */
    --leb-radius: 4px;
}
```

**Strict Rules:**
- ❌ **NO hardcoded color codes** in any PHP/HTML files
- ❌ **NO inline styles** (style attributes)
- ✅ **ONLY use CSS variables** from `global.css`
- ✅ All components must inherit from variable system

### **2. assets-loader.php (Centralized Path Manager)**
This file must:
- Define constants or functions for all asset paths:
  - `LEB_PLUGIN_URL` – Full URL to plugin directory
  - `LEB_ASSETS_CSS_URL` – Path to CSS folder
  - `LEB_ASSETS_JS_URL` – Path to JS folder
  - `LEB_TEMPLATES_PATH` – Path to templates folder
- Provide enqueue functions:
  - `leb_enqueue_global_styles()` – Loads global.css on all LEB pages
  - `leb_enqueue_toaster_assets()` – Loads toaster CSS/JS when needed
  - `leb_enqueue_admin_styles()` – Loads admin-specific CSS
- Hook into `admin_enqueue_scripts` with proper page conditionals (only load on LEB pages)

### **3. Toaster Notification System**
**Separate Files:**
- `assets/css/leb-toaster.css` – Styling for toast messages
- `assets/js/leb-toaster.js` – JavaScript logic (auto-dismiss after 3 seconds, animation)

**Features:**
- Position: Top-right corner
- Types: success, error, warning, info
- Trigger via JavaScript function: `LEB_Toaster.show(message, type)`
- Must work with WP AJAX responses

---

## **Technical Requirements**

### **WordPress Coding Standards:**
- Follow WordPress coding standards (indentation, naming, etc.)
- All database queries must use `$wpdb->prepare()` to prevent SQL injection
- Use `check_ajax_referer()` for AJAX security
- Implement `current_user_can('manage_options')` checks for admin pages
- Internationalization ready: Use `__('string', 'listing-engine-backend')` for all user-facing strings

### **Security:**
- Nonce verification on all forms
- Data sanitization: `sanitize_text_field()`, `sanitize_title()`, etc.
- Output escaping: `esc_html()`, `esc_attr()`, `wp_kses()`

### **Performance:**
- Load CSS/JS only on LEB admin pages (don't bloat other areas)
- Use WordPress transients for caching table status checks if needed
- Optimize database queries with proper indexing

---

## **Deliverables Checklist**

- [ ] Main plugin file with proper headers and activation/deactivation hooks
- [ ] Complete folder structure as specified
- [ ] `class-db-handler.php` with create/update methods
- [ ] `db-schema.php` with `wp_ls_types` table definition
- [ ] `assets-loader.php` with centralized path management
- [ ] `global.css` with full CSS variable system
- [ ] Toaster notification system (CSS + JS)
- [ ] Database management page (grid layout with status cards)
- [ ] Type list page with search, pagination, CRUD
- [ ] Add/Edit type form with validation
- [ ] Settings link on plugins page
- [ ] Proper LEB prefixing throughout
- [ ] All slugs stored in lowercase
- [ ] No inline styles or hardcoded colors
- [ ] UI matching provided reference images in `screen-name/` folder

---

**Note to Developer:** Refer to the UI mockup images located in the `screen-name/` folder for exact visual design implementation of the Type Management and Database screens. The design should follow modern WordPress admin aesthetics (Gutenberg-era clean UI).