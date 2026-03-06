# Implementation Plan: Car Hire Dealer Marketplace

This document outlines the strategy for expanding **Car Hire** from a direct car rental service into a multi-vendor marketplace. This allows external car dealers to list their vehicles on the platform, leveraging Car Hire's booking engine and customer base.

---

## 1. Core Concept
Dealers (external companies) can register as "Vendors" on the platform. They provide the vehicles, while Car Hire manages:
- Search and Discovery
- Booking Logic
- Payment Processing
- Customer Support
- Identity Verification

### Revenue Model
Car Hire will operate on a **Commission-Based Model**:
- **Platform Fee**: A fixed percentage (e.g., 15%) is deducted from every booking made on a dealer-owned vehicle.
- **Payouts**: Net earnings (Booking Total - Commission) are credited to the Dealer's internal wallet, available for payout.

---

## 2. Infrastructure Changes

### A. Database Schema Updates
We need to track ownership and financial splits.

#### `users` Table
- Add `'dealer'` to the `role` enum.
- Add `company_name` (VARCHAR), `is_verified` (BOOLEAN).

#### `vehicles` Table
- `owner_id` (INT, FK to `users.id`): Links vehicle to its owner.
- `inventory_type` (ENUM): `'internal'` (Car Hire fleet) or `'external'` (Dealer fleet).
- `verification_status` (ENUM): `'pending'`, `'approved'`, `'rejected'`. Listing is hidden until approved by Admin.

#### `dealer_profiles` (New)
- `user_id` (INT, FK)
- `business_license` (VARCHAR)
- `bank_details` (TEXT/JSON)
- `commission_rate` (DECIMAL): Custom rate per dealer if needed.

#### `payouts` (New)
- `dealer_id` (INT)
- `amount` (DECIMAL)
- `status` (ENUM): `'pending'`, `'paid'`, `'cancelled'`.

---

## 3. Module Development

### Phase 1: The Dealer Onboarding
- **Registration**: Update `register.php` to include a "Register as Dealer" option.
- **KYC**: Dealers must upload business documents for verification.

### Phase 2: Dealer Portal (`portal-dealer/`)
Create a dedicated space for dealers with the following pages:
1. **Dashboard**: Summary of active rentals, total earnings, and fleet status.
2. **Fleet Management**:
   - `add-vehicle.php`: specialized form for listing cars.
   - `manage-fleet.php`: Edit pricing, update availability.
3. **Reservations**: Track who has rented their cars and when they are due back.
4. **Earnings & Payouts**: View transaction history and request withdrawals.

### Phase 3: Admin Moderation (`portal-admin/`)
- **Vendor Approval**: A queue for new dealer applications.
- **Listing Moderation**: Admins must review vehicle photos and descriptions before they go live.
- **Global Financials**: Monitor the total commission earned across all vendors.

---

## 4. UI/UX Enhancements

### For Customers
- **Transparency**: Vehicles will show "Hosted by: [Dealer Name]" or a "Verified Partner" badge.
- **Filtering**: Add a filter to search for specific dealers or "Show only Car Hire Official Fleet".

### For Dealers
- **Mobile First**: Use the existing `mobile_nav.php` framework (which already has a placeholder for `vendor` role).

---

## 5. Technical Implementation Steps (Immediate Actions)

1.  **Migration Script**: Create `scripts/migrate_dealer_system.php` to update the DB.
2.  **Role Logic**: Update `login.php` to redirect users with the role `dealer` to `portal-dealer/dashboard.php`.
3.  **Owner Filtering**: Update `portal-admin/fleet.php` to distinguish between internal assets and external dealer assets.
4.  **Commission Logic**: Update the booking success logic to calculate and record the platform fee.

---

*Prepared by Antigravity AI*
