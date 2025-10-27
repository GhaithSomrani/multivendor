
# PrestaShop Multi-Vendor Marketplace Module - Technical Documentation

## Module Overview
**Name:** multivendor  
**Version:** 3.0.0  
**Author:** Ghaith Somrani  
**PrestaShop Compatibility:** 1.7+

Transforms a standard PrestaShop store into a multi-vendor marketplace where multiple vendors can sell products and manage their operations independently.

---

## Core Architecture

### Database Schema

**Vendors**
- `mv_vendor` - Vendor profiles (linked to customers and suppliers)
- `mv_vendor_commission` - Commission rates per vendor
- `mv_vendor_commission_log` - Commission rate change history

**Orders & Commissions**
- `mv_vendor_order_detail` - Vendor-specific order line items
- `mv_order_line_status` - Status for each order line
- `mv_order_line_status_type` - Status types configuration
- `mv_order_line_status_log` - Status change history

**Payments & Transactions**
- `mv_vendor_transaction` - Individual vendor transactions
- `mv_vendor_payment` - Payment records to vendors

**Manifests**
- `mv_manifest` - Manifest documents (pickup/returns)
- `mv_manifest_type` - Manifest types
- `mv_manifest_status_type` - Manifest status types
- `mv_manifest_details` - Manifest line items

**Product Commissions**
- `mv_product_commission` - Product-specific commission overrides
- `mv_product_commission_log` - Product commission change history

---

## Key Components

### 1. Classes

**Vendor.php**
- Manages vendor accounts
- Links vendors to customers and suppliers
- Handles vendor profiles (shop name, logo, banner)

**VendorCommission.php**
- Manages commission rates
- Logs rate changes
- Calculates vendor earnings

**OrderLineStatus.php**
- Tracks status per order line per vendor
- Validates status transitions
- Triggers commission actions (add/cancel/refund)

**OrderLineStatusType.php**
- Defines available statuses
- Controls vendor/admin permissions
- Determines commission impact

**VendorTransaction.php**
- Records individual transactions
- Tracks commission/refund amounts
- Links to payments

**VendorPayment.php**
- Groups transactions into payments
- Generates payment references
- Manages payment status (pending/completed/canceled)

**Manifest.php**
- Creates pickup/return manifests
- Groups order lines for logistics
- Generates unique references

**ProductCommission.php**
- Overrides default commission rates per product
- Logs commission changes

### 2. Helper Classes

**VendorHelper.php**
- Vendor lookup utilities
- Commission calculations
- Dashboard statistics
- CSV exports

**OrderHelper.php**
- Processes orders for vendors
- Filters order data
- Handles order detail creation/updates

**TransactionHelper.php**
- Transaction management
- Payment grouping
- Transaction status updates

---

## Admin Controllers

**AdminVendors** - Vendor CRUD  
**AdminVendorCommissions** - Commission management  
**AdminVendorPayments** - Payment processing  
**AdminOrderLineStatus** - Status type configuration  
**AdminManifest** - Manifest management  
**AdminManifestType** - Manifest type configuration  
**AdminVendorOrderDetails** - View vendor order lines

---

## Front Controllers

**dashboard** - Vendor dashboard with sales analytics  
**orders** - Vendor order management  
**commissions** - Commission history  
**manageorders** - Order line status updates  
**manifestmanager** - Create/manage manifests  
**profile** - Vendor profile settings

---

## Hooks

**actionValidateOrder** - Creates vendor order records  
**actionOrderStatusUpdate** - Updates order line statuses  
**displayAdminOrder** - Shows vendor info in admin orders  
**displayCustomerAccount** - Vendor menu in customer account  
**actionObjectOrderDetailAddAfter** - Processes new order lines  
**actionObjectOrderDetailUpdateAfter** - Updates order lines  
**actionObjectOrderDetailDeleteAfter** - Removes order lines  
**displayBackOfficeHeader** - Loads admin assets  
**addWebserviceResources** - Exposes API resources

---

## Key Features

### Commission System
- Global commission rates per vendor
- Product-specific overrides
- Status-based commission triggers:
  - `add` - Adds commission
  - `cancel` - Zeros commission
  - `refund` - Negative commission

### Order Line Status Workflow
- Each order line has independent status
- Configurable status types with:
  - Vendor/admin permissions
  - Commission actions
  - Status transitions
  - Visual colors

### Manifest System
- **Pickup Manifests** - Group items for delivery
- **Return Manifests** - Handle returns
- Auto-generates transactions when validated
- Links to vendor payments

### Payment Processing
- Groups transactions into payments
- Payment statuses: pending/completed/canceled
- Reference generation: `VND-DATE-ID`
- Transaction linking

### Dashboard Analytics
- Sales trends (daily/monthly)
- Order statistics by status
- Top products
- Commission summary
- Date range filtering

---

## Configuration

Key settings stored in PrestaShop Configuration:
- `MV_ALLOW_VENDOR_REGISTRATION` - Enable vendor signup
- `MV_HIDE_FROM_VENDOR` - Status IDs hidden from vendors

---

## API / Webservices

Exposed resources:
- `order_line_status_types`
- `order_line_history`
- `order_line_statuses`
- `product_commissions`

---

## File Structure

```
multivendor/
├── multivendor.php (main module)
├── classes/ (model classes)
├── controllers/
│   ├── admin/ (admin controllers)
│   └── front/ (vendor front controllers)
├── views/
│   ├── templates/
│   │   ├── admin/ (admin views)
│   │   └── front/ (vendor views)
│   ├── css/ (stylesheets)
│   └── js/ (JavaScript)
├── sql/
│   ├── install.php
│   └── uninstall.php
└── translations/
```

---

## Installation Process

1. Creates 12+ database tables
2. Registers PrestaShop hooks
3. Installs admin menu tabs:
   - Vendors
   - Commissions
   - Payments
   - Order Line Statuses
   - Manifests
   - Manifest Types

---

## Workflow Example

1. Customer places order with products from Vendor A
2. `actionValidateOrder` hook creates records in `mv_vendor_order_detail`
3. Default status assigned (usually "Pending")
4. Vendor views order in dashboard
5. Vendor updates status to "Processing" (commission action: add)
6. System creates transaction in `mv_vendor_transaction`
7. Admin creates manifest for pickup
8. Manifest validated → transactions ready for payment
9. Admin creates payment grouping transactions
10. Payment marked completed → vendor receives funds

---

## Commission Calculation

```
vendor_amount = product_price * (1 - commission_rate)
commission_amount = product_price * commission_rate
```

Commission sources (priority):
1. Product-specific commission
2. Vendor default commission

---

## Security Features

- Customer authentication required for vendor access
- Vendor isolation (can only see their own data)
- Admin-only payment processing
- Status change logging with user tracking
- XSS protection via PrestaShop validators

---

## Extensibility

- Hook-based architecture
- WebService API for integrations
- Modular helper classes
- Template override support
- Custom status types

---

This module provides a complete multi-vendor ecosystem with granular control over order fulfillment, commissions, and payments while maintaining separation between vendors.