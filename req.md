---

# **📋 COMPLETE PROJECT PROMPT: Property Management Admin Panel**

## **🎯 PROJECT OVERVIEW**
Build a fully responsive **Property Listing Management System** for WordPress admin panel with modular file architecture, real-time data handling, and intuitive UI/UX.

---

## **🏗️ ARCHITECTURE & CODE STANDARDS**

### **File Structure Requirements:**
- **Each feature/function MUST be in separate independent files**
- **ZERO dependencies between files** (fully modular)
- **Future-proof design**: New features can be added without modifying existing code
- **Comprehensive comments** in every code section explaining functionality
- **No inline CSS or hardcoded colors** anywhere in the code

### **Styling Guidelines:**
- Use **ONLY `global.css`** for all styling
- All colors must be defined as **CSS variables** in global.css
- Font-family must come from global.css only
- **Absolutely no inline styles**, no hardcoded color codes (including rgba, hex in HTML attributes, or box-shadow values directly in components)

### **UI Reference:**
- Replicate exact UI design from `/screen` folder (2 reference screens already provided)
- Must be **fully responsive** (mobile, tablet, desktop compatible)

---

## **📱 SCREEN 1: MAIN PROPERTY LISTING DASHBOARD**

### **Header Section:**
- **"Add New Property" button** → Opens Add/Edit Modal (detailed below)

### **Search & Filter System:**
1. **Search Bar**: 
   - Real-time filtering capability
   - Triggers filter **after 2 characters are typed** (not on first character)
   
2. **Status Filter Tabs** (click to filter):
   - **Pending Tab** → Shows only properties with `"pending"` status
   - **Published Tab** → Shows only properties with `"published"` status
   - **Rejected Tab** → Shows only properties with `"rejected"` status
   - **Draft Tab** → Shows only properties with `"draft"` status

### **Property Data Table:**

**Pagination Rules:**
- Show **10 properties per page**
- Pagination controls at bottom (Previous/Next buttons with page numbers)

**Table Columns & Data Sources:**

| Column # | Column Name | Data Source | Display Logic |
|-----------|-------------|-------------|---------------|
| 1 | Checkbox | - | For bulk selection |
| 2 | Title | `wp_ls_listings.title` | **Truncate with ellipsis** if long (example: *"Jacuzzi Suite by Ridhi...."*)<br><br>⚠️ **IMPORTANT**: Define a variable at top of config file like `TITLE_MAX_LENGTH` so admin can easily change character limit in future without hunting through code |
| 3 | Price | `wp_ls_listings.price` | Display as formatted currency |
| 4 | Type | `wp_ls_listings.type` (contains ID) | **Join Query Required**: Take the ID value from this field → Go to `wp_ls_types` table → Match ID → Return the `name` value from that table<br><br>Example: If type = `2`, query wp_ls_types where id=2, return "Villa" |
| 5 | User | `wp_ls_listings.user_id` (contains ID) | **Join Query Required**: Take user_id → Go to `wp_users` table → Match ID → Return format: **"username (ID)"**<br><br>Example: If user_id=1 and that user's login is "thesk", display: **"thesk (1)"** |
| 6 | Date/Time | `wp_ls_listings.updated_at` | Format as readable date/time |

### **Bulk Operations (Below Table):**
- **Bulk Delete Button**: Delete all selected (checked) properties at once
- **Bulk Status Change Dropdown**: Change status for all selected properties simultaneously (options: Draft, Pending, Published, Rejected)

---

## **➕ SCREEN 2: ADD/EDIT PROPERTY MODAL**

### **Modal Navigation:**
- **Back Arrow (←)** at top-left → Closes modal and returns to main listing screen

### **Form Fields (Top to Bottom Order):**

#### **FIELD 1: Property Title**
- Text input field
- Saves to: `wp_ls_listings.title`

---

#### **FIELD 2: Image Upload & Management Section**

**Upload Source:** WordPress Media Gallery picker

**Validation Rules:**
- ✅ **Minimum 5 images required** (cannot save with fewer)
- ✅ **Maximum 10 images allowed** (cannot select more)
- ✅ **Each image must be less than 1MB** in file size
- Show error message if validation fails

**Preview Display:**
- Show thumbnail previews of all selected images
- Each preview must have:
  - ❌ **Remove button (X icon)** → Removes that specific image from selection
  - **Order number badge** showing position (1, 2, 3...)
  - **Drag handle** (☰ grip icon) for reordering

**⭐ DRAG-AND-DROP REORDERING FEATURE:**
- Admin can **drag thumbnails** to rearrange image order
- Visual feedback during drag (slight lift/shadow effect)
- Drop target highlighting when dragging over valid position
- **Real-time order update** as images are moved
- Save the final arrangement order to database

**Database Storage (CRITICAL):**
- Table: **`wp_ls_img`**
- **One property = ONE row only** (not multiple rows)
- Column: `image` stores **JSON array** containing all images for that property
- Column: `property_id` links to the property

**JSON Structure Example:**
```json
[
  {
    "id": 4567,
    "path": "/uploads/2024/property-living-room.jpg",
    "order": 0
  },
  {
    "id": 4568,
    "path": "/uploads/2024/property-bedroom.jpg",
    "order": 1
  },
  {
    "id": 4569,
    "path": "/uploads/2024/property-pool.jpg",
    "order": 2
  }
]
```

Each object contains:
- `id`: The WordPress media attachment ID
- `path`: File path/URL to image
- `order`: Integer representing sequence position (0, 1, 2... based on drag arrangement)

---

#### **FIELD 3: Description**
- Large textarea or rich text editor
- Saves to: `wp_ls_listings.description`

---

#### **FIELD 4: Property Details Grid**
Arrange these fields in a clean grid layout (2x2 or similar):

| Label | Database Column | Input Type |
|-------|----------------|------------|
| Guests | `wp_ls_listings.guests` | Number input |
| Bedrooms | `wp_ls_listings.bedrooms` | Number input |
| Beds | `wp_ls_listings.beds` | Number input |
| Bathrooms | `wp_ls_listings.bathrooms` | Number input |
| Price | `wp_ls_listings.price` | Number/currency input |

---

#### **FIELD 5: Amenities Selection**

**Data Source:** Fetch all amenities from **`wp_ls_ameneties`** table ⚠️ (note spelling: a-m-e-n-e-t-e-y-s)

**UI Format:**
- Display as checkboxes or selectable cards/grid
- Admin can select **multiple amenities**
- Show amenity name

**Database Storage:**
- Saves to: **`wp_ls_listings.ameneties`** column
- **Format: Comma-separated IDs in exact selection order**
- Example: If admin selects Pool (ID=3), then WiFi (ID=1), then Parking (ID=4), store: **`"3,1,4"`**
- ⚠️ **Must preserve the order** in which admin clicked/selected them

---

#### **FIELD 6: Location Dropdown**

**Data Source:** Fetch options from **`wp_ls_location`** table

**Display:** Dropdown/select menu showing location names

**Database Storage:**
- Saves to: **`wp_ls_listings.location`**
- **Store ONLY the ID** (integer), not the name
- Example: If "Goa" has ID=5, store value: `5`

---

#### **FIELD 7: Property Type Dropdown**

**Data Source:** Fetch options from **`wp_ls_types`** table

**Display:** Dropdown/select menu showing type names

**Database Storage:**
- Saves to: **`wp_ls_listings.type`**
- **Store ONLY the ID** (integer), not the name
- Example: If "Villa" has ID=2, store value: `2`

---

#### **FIELD 8: Calendar / Date Blocker**

**Functionality:**
- Interactive calendar widget displayed in modal
- Admin can click on dates to select them as "blocked/unavailable"
- Selected dates should be visually highlighted

**Database Storage:**
- Table: **`wp_ls_block_date`**
- Columns:
  - `property_id`: Links to the property
  - `dates`: **JSON array** of date strings (format: YYYY-MM-DD)
    - Example: `["2024-01-15", "2024-01-16", "2024-01-20"]`
  - `created_at`: Timestamp of when this block was created (current date/time when admin saves it)

---

#### **FIELD 9: Host Information (READ-ONLY SECTION)**

**⚠️ DISPLAY CONDITION:**
- **Show this section ONLY when editing an existing property**
- **HIDE completely when creating a new property** (this section shouldn't exist for new listings)

**Purpose:** Display information about the property owner/host

**Data Fetch Logic:**
```
Step 1: Get the current property's user_id from wp_ls_listings table

Step 2: Using that user_id, query wp_users table to get:
        - Username ← from user_login column
        - Email    ← from user_email column  
        - User ID  ← (already have it, but display for reference)

Step 3: Using same user_id, query wp_usermeta table:
        - WHERE user_id = [current user_id] 
        - AND meta_key = 'mobile_number'
        - Get mobile_number value from meta_value column
```

**Display Format:**
```
┌─────────────────────────────────────┐
│  👤 HOST INFORMATION                │
│  ─────────────────────────────────  │
│  User:   thesk (ID: 1)             │
│  Email:  thesk@example.com         │
│  Mobile: +91-9876543210            │
└─────────────────────────────────────┘
```

This entire section is **read-only** (no editable fields) - purely informational.

---

#### **FIELD 10: Status Dropdown**

**Options:**
- Draft
- Pending
- Published
- Rejected

**Saves to:** `wp_ls_listings.status`

---

#### **FIELD 11: Action Buttons (Bottom of Modal)**

**Button text changes based on mode:**

| Mode | Button Text | Behavior |
|------|-------------|----------|
| Creating NEW property | **"Create Listing"** | Inserts new record into database |
| Editing EXISTING property | **"Update Listing"** | Updates existing record |

---

## **💾 SAVE / SUBMIT LOGIC & WORKFLOW**

### **When Admin Clicks Create/Update Button:**

**STEP 1: Client-Side Validation**
- Check all required fields are filled
- Verify minimum 5 images uploaded (and max 10)
- Confirm each image file is under 1MB
- Validate price is positive number
- Check location and type are selected
- ❌ **If any validation fails**: Show error message near the problematic field AND show a toaster notification with error details

**STEP 2: Loading State**
- Disable the submit button immediately (prevent double-clicks)
- Show loading spinner/indicator with text like "Saving..." or "Creating Property..."
- Keep modal open during this process

**STEP 3: API Call (AJAX/Fetch)**
- Send all form data to backend WordPress AJAX endpoint
- Include all fields: title, description, price, guests, bedrooms, beds, bathrooms, type (ID), location (ID), ameneties (CSV string like "3,1,4"), status, images (JSON array), blocked dates (JSON array)

**STEP 4A: Success Response**
- Show **success toaster notification**: ✅ "Property created successfully!" or ✅ "Property updated successfully!"
- Wait 1.5 seconds (auto-close delay)
- Close modal automatically
- Refresh/reload the main listing table to show updated data

**STEP 4B: Error Response**
- Show **error toaster notification**: ❌ "Error: [specific error message from server]"
- Re-enable the submit button
- Hide loading spinner
- Allow admin to fix issues and retry

---

## **⚡ REAL-TIME AUTO-SAVE BEHAVIOR (Special Rule)**

**For NEW Listings:**
- As admin fills out the form, data can auto-save in real-time to database
- **HOWEVER**, the status must remain as **"draft"** until explicitly published
- Even if admin selects "Published" in dropdown, initial save keeps it draft
- Final status applies only when clicking "Create Listing" button successfully

**For EDITING Existing Listings:**
- Original data is already fetched and pre-populated in form fields
- Only track what fields have changed (dirty checking)
- On "Update Listing" click, send only changed data (optimization) OR send full updated record

---



## **✅ FINAL CHECKLIST FOR DEVELOPER**

Before starting development, confirm:

- [ ] File structure is modular with zero inter-dependencies
- [ ] All styling uses CSS variables from global.css only
- [ ] Image upload validates: min 5, max 10, under 1MB each
- [ ] Images are drag-reorderable with visual feedback
- [ ] Images store as single JSON entry in `wp_ls_img` table (one row per property)
- [ ] Amenities table name is `wp_ls_ameneties` (with 'e')
- [ ] Amenities save as comma-separated IDs in selection order (e.g., "3,1,4")
- [ ] Location saves as ID only (integer)
- [ ] Type saves as ID only (integer)
- [ ] Search triggers after 2 characters
- [ ] Pagination shows 10 items per page
- [ ] Bulk delete and bulk status change work
- [ ] Title truncates based on configurable variable
- [ ] Host info shows only in edit mode (hidden for new)
- [ ] Host info fetches username, email, mobile via joins
- [ ] Calendar blocks dates and saves as JSON
- [ ] Form validates before submit with error messages
- [ ] Loading spinner shows during save process
- [ ] Toaster notifications for success/error
- [ ] New listings default to "draft" status
- [ ] Back arrow closes modal
- [ ] All code has clear comments explaining functionality
- [ ] UI matches provided reference screens
- [ ] Fully responsive design (mobile/tablet/desktop)

---

**END OF PROMPT** ✅