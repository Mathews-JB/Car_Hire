# Targeted Vouchers & Enhanced Email Notifications

## Overview
This update introduces functionality to create exclusive promo codes targeted at specific customers via email. It also revamps the booking confirmation email to include a celebratory section when a discount is applied.

## Changes Implemented

### 1. Database Schema
- **Table:** `vouchers`
- **Change:** Added `assigned_user_email` column (VARCHAR 255).
- **Purpose:** To link a voucher code to a specific user email address.

### 2. Admin Portal (`portal-admin/vouchers.php`)
- **Creation Form:** added an "assigned email" input field.
- **Display:** Updated the voucher list to indicate if a voucher is "Public" or "Assigned to [Email]".

### 3. Core Logic (`includes/functions.php`)
- **Validation:** Updated `validateVoucher()` to accept an optional `$user_email` parameter.
- **Logic:** If a voucher has an assigned email, it now verifies that the redeeming user's email matches it.

### 4. API (`api/validate-voucher.php`)
- **Update:** Now fetches the logged-in user's email from the session and passes it to the validation function.

### 5. Booking Process (`portal-customer/booking-form.php`)
- **Validation:** Passes the user's email when applying a voucher during the booking process.
- **Email Notification:**
    - Detects if a discount was applied.
    - Injects a "You Scored a Deal!" celebratory block into the confirmation email body.
    - Highlights the saved amount in a distinct visual style.

## Usage
1.  **Create targeted voucher:** In Admin > Vouchers, enter a code and specify an email address in the "Target Email" field.
2.  **Public voucher:** Leave the "Target Email" field blank.
3.  **Redemption:** Users can only redeem targeted vouchers if they are logged in with the matching email address.
