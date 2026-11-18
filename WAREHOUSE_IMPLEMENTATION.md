# Implementation Summary - Warehouse Intake & QC Workflow (DLNH ERP)

**Status:** QA/QC workflow implemented; production database verification is blocked
**Last Updated:** 2026-11-18

---

## Overview

Implemented complete warehouse intake process for herbal raw materials (dược liệu thô) and other material inputs (nguyên liệu, phụ liệu, tá dược, bao bì) in DLNH ERP system.

### Process Flow

1. **Receive Purchase Order** → Warehouse receives goods from supplier
2. **Temporary Storage** (Kho Tạm) → Goods stored temporarily with location code
3. **QA Review** → Only for raw materials: QA updates COA, batch number, MFG/EXP dates
4. **QC Inspection** → Raw materials require a test report number, date, and file plus an OK/NG decision. Other materials require only an OK/NG decision.
5. **Stock Receipt** (Nhập Kho Chính) → If QC passed, warehouse posts to main inventory
6. **Return to Supplier** (Trả NCC) → If QC failed, logistics arranges return

The QA raw-material queue and QC queues are separate menu destinations. The warehouse receipt form does not assign preliminary QA/QC reviewers.

---

## Technical Implementation

### Database Schema

#### `material_lots` table (enhanced)
- `temporary_location_code` (varchar, nullable) - Storage location in temporary zone
- `temporary_stored_at` (timestamp, nullable) - When moved to temporary storage
- `temporary_stored_by` (FK to users, nullable) - Who performed temporary storage
- `qa_reviewed_at` (timestamp, nullable) - When QA reviewed (raw materials only)
- `qa_reviewed_by` (FK to users, nullable) - QA who reviewed

#### `material_lot_inspections` table (created)
- `id` (PK)
- `material_lot_id` (FK)
- `checked_by` (FK to users)
- `result` (varchar: pending, passed, failed)
- `note` (text, nullable)
- `checked_at` (timestamp)
- **New columns:**
  - `inspection_stage` (varchar: qa_review, qc_inspection)
  - `detailed_findings` (text, nullable)
  - `corrective_action` (text, nullable)
- QC report fields: `report_number`, `report_date`, and `report_file` (used for raw-material inspections)
- Timestamps

The inspection table is created by `2026_11_18_090000`; report fields and nullable preliminary reviewer fields are handled by `2026_11_18_110000`.

### Models (Updated)

#### MaterialLot
```php
// Relationships
public function inspections()           // → MaterialLotInspection
public function qaReviewedBy()          // → User
public function temporaryStoredBy()     // → User

// Status labels mapping
public function status_label()
// pending_qa → "Chờ QA Cập Nhật COA"
// pending_qc_inspection → "Chờ QC Kiểm Tra"
// pending_reinspection → "QC Yêu Cầu Kiểm Tra Lại"
// qc_passed_pending_receipt → "QC Đạt - Chờ Kho"
// active → "Đã Vào Tồn"
// rejected → "Từ Chối"

// Helper methods
public function needsQa(): bool         // Check if raw material needs QA
```

### Controllers (Updated)

#### MaterialLotController

**New Methods:**
1. `qaIndex()` / `qcIndex()` → Separate QA raw-material queue and QC queues
2. `temporaryStorage(Request $request)` → List all lots in temporary storage
3. `moveFromTemporaryStorage(MaterialLot $lot)` → Move raw materials to QA and other materials directly to QC
4. `recordInspection()` → Save QC result and, for raw materials, the test report for that inspection
5. `inspectionReport()` → Serve inspection reports through an authorized route

**Updated Methods:**
1. `updateCoa()` → Only for raw materials; saves QA reviewer + date
1. `updateCoa()` → QA updates COA for raw materials only
2. `approve()` / `reject()` → Save QC decision; raw-material submissions require the test report
3. `requestReinspection()` → Request re-test

### Routes (web.php)

```php
// Get temporary storage list
GET /warehouse/material-lots/temporary-storage
    → route('material-lots.temporary-storage')

// Legacy material-lots index
GET /qa/material-lots?type=raw_material|ingredient|...
    → route('material-lots.index')

// Separate QA and QC queues
GET /qa/material-lots/raw-materials → route('qa.material-lots.index')
GET /qc/material-lots → route('qc.material-lots.index')

// Update COA (QA review for raw materials)
PATCH /qa/material-lots/{materialLot}
    → route('material-lots.update-coa')

// Record QC result (raw-material report is required; other materials need only result)
POST /qc/material-lots/{materialLot}/inspection
    → route('material-lots.inspect')

// Report file access
GET /qc/material-lot-inspections/{inspection}/report
    → route('material-lots.inspection-report')

// Legacy QC decision endpoints
POST /qa/material-lots/{materialLot}/approve
    → route('material-lots.approve')

// Reject lot (QC decision)
POST /qa/material-lots/{materialLot}/reject
    → route('material-lots.reject')

// Request reinspection (QC decision after rejection)
POST /qa/material-lots/{materialLot}/reinspect
    → route('material-lots.reinspect')

// Move from temporary storage
POST /warehouse/material-lots/{materialLot}/move-from-temporary
    → route('material-lots.move-from-temporary')
```

### Views (Blade Templates)

#### 1. `index.blade.php` (Enhanced)
- **Tabs by material type:** Dược liệu thô | Nguyên liệu | Phụ liệu | Tá dược | Bao bì
- **Filter bar:** Search by name/SKU/supplier + status filter
- **Status-aware actions:**
  - `pending_qa`: Show QA form (batch, MFG/EXP dates, COA file) for raw materials only
  - `pending_qc_inspection`: Show QC approve/reject forms
  - `pending_reinspection`: Show reinspection request form
  - `qc_passed_pending_receipt`: Show warehouse receipt code link
  - `rejected`: Show reinspection + return-to-supplier options
- **Status colors:** Each status has distinct badge color (slate, amber, blue, orange, lime, emerald, rose)

#### 2. `temporary-storage.blade.php`
- Lists all lots with `status = temporary_stored`
- Shows: Material type | Supplier | Quantity | Location code | Stored date | QA+QC or QC-only tag
- Action: "Chuyển sang kiểm tra" button (POST to moveFromTemporaryStorage)
- Filters: Type + search by name/SKU/supplier

#### 3. `ready-for-receipt.blade.php` (Existing)
- Used for warehouse to post stock receipt for QC-passed lots

---

## Menu Navigation (sidebar-links.blade.php)

### Kho (Warehouse) Section
```
Nhập Kho
├── Nhận Đơn Mua & Nhập Tạm
├── Danh Sách Kho Tạm
└── QC Đạt - Nhập Kho Chính

🔄 Trả Nhà Cung Cấp (QC Không Đạt)
...
```

### QC (Kiểm Chất Lượng) Section
```
Kiểm Chất Lượng Hàng Nhập
├── Cập nhật COA dược liệu thô (QA)
├── Dược liệu thô: phiếu kiểm nghiệm và kết quả (QC)
└── Vật tư: xác định OK / NG (QC)
...
```

---

## Status Transitions

```
Workflow for Raw Materials (Dược Liệu Thô):
  temporary_stored 
    → (Kho chuyển sang kiểm tra)
    → pending_qa 
      → (QA cập nhật COA + batch/dates)
      → pending_qc_inspection
        → pending_reinspection ↔ (QC yêu cầu kiểm tra lại)
        → qc_passed_pending_receipt
          → (Kho lập phiếu nhập)
          → active (Vào tồn)
        → rejected → supplier_return_ordered

Workflow for Other Materials:
  temporary_stored 
    → (Kho chuyển sang kiểm tra)
    → pending_qc_inspection
      → pending_reinspection ↔ (QC yêu cầu kiểm tra lại)
      → qc_passed_pending_receipt
        → (Kho lập phiếu nhập)
        → active (Vào tồn)
      → rejected → supplier_return_ordered
```

---

## User Permissions

- **Warehouse Department / Manager:** Receive goods, temp storage, move from temp storage, post stock receipt
- **QA Department:** Update COA and lot details for raw materials
- **QC Department:** Record inspection results; attach report for raw materials; approve/reject lots
- **IT Department:** See-all permission for testing
- **Executive Board:** View-all permission

---

## Form Validation

### QA Update COA Form (Raw Materials Only)
- Batch number (required, text)
- MFG date (required, date)
- EXP date (required, date)
- COA file (required if not already uploaded, PDF only)

### QC Inspection (Raw Materials)
- Result (required: OK or NG)
- Test report number, date, and file (required)
- QC note/findings (required when result is NG)

### QC Inspection (Other Materials)
- Result (required: OK or NG; no test report required)
- QC note/findings (required when result is NG)

### QC Reinspection Form
- Note (required, reason for reinspection)
- Corrective action (optional, textarea)

---

## Notes & Known Issues

1. The QA and QC queues are separated; the warehouse form no longer asks for preliminary QA/QC assignment.
2. Raw materials require QA COA completion before QC, and a QC report on each QC decision.
3. Other material types go directly to QC and require only an OK/NG result.
4. Do not run migrations against the current application database until its recovery plan is agreed. The actual database schema has not been verified by this code change.
5. The affected Blade templates and route registration have been checked locally; feature tests requiring database migrations are blocked by a pre-existing SQLite/MySQL migration incompatibility.

---

## Files Modified/Created

### New Files
- `app/Http/Controllers/MaterialLotInspection.php` (relationship model, exists)
- `database/migrations/2026_11_17_141725_add_warehouse_enhancements.php` (corrected)

### Modified Files
- `app/Models/MaterialLot.php` - Added relationships & helpers
- `app/Http/Controllers/MaterialLotController.php` - Added temporaryStorage() method
- `resources/views/material-lots/index.blade.php` - Complete UI redesign with tabs
- `resources/views/layouts/partials/sidebar-links.blade.php` - Menu language standardization
- `routes/web.php` - Added route for temporary-storage view
- `database/migrations/2026_11_16_170000_normalize_manual_label_codes.php` - Fixed down()

### Existing Files
- `resources/views/material-lots/temporary-storage.blade.php` - Already complete

---

## Next Steps (Future Enhancements)

1. ☐ Add QR code scanning for temporary location codes
2. ☐ Dashboard widget showing warehouse intake status
3. ☐ Automatic email notifications on lot status changes
4. ☐ Batch operations for moving multiple lots from temporary storage
5. ☐ QC report export (PDF) with detailed findings
6. ☐ Supplier performance report (pass/fail by vendor)
7. ☐ Audit trail/logs for all status transitions

---

## Testing Checklist

- [ ] Temporary storage list loads correctly
- [ ] Can move lot from temp storage to QA/QC
- [ ] QA form shows only for raw materials
- [ ] QC approve/reject forms work
- [ ] Status transitions are correct
- [ ] Material receipts link properly
- [ ] Supplier returns generate correctly on rejection
- [ ] Menu links work and highlight properly
- [ ] Pagination works
- [ ] Search/filter functions

---

**Documentation:** This file serves as reference for maintenance and future enhancements.
