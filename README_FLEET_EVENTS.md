# 🚗 Multi-Car Fleet & Event Booking Guide (Admin Edition)

This guide outlines the professional workflow for handling high-value event bookings where customers request more than one vehicle.

---

## 📋 1. Request Intake

When a customer needs a fleet for a wedding, corporate event, or group travel, they use the **"Request Fleet Booking"** feature.

- **Location**: In the Customer Portal under "Browse Fleet" banner.
- **Support Ticket**: This generates a specialized support inquiry in the Admin Panel.

---

## 📬 2. Support Inbox Identification

Admin staff can easily spot these high-priority requests in the **Support Inbox**.

- **Visual Cue**: Look for the 🚙 icon and a **"Multi-Car/Event"** subject line.
- **Priority**: These are typically higher revenue bookings that require manual pricing.

---

## 💰 3. The Quoting Workflow

Because fleet bookings vary in complexity, the system uses a **Custom Quoting Engine** instead of standard daily rates.

1.  **Open the Message**: Read the customer's specific fleet requirements.
2.  **Calculate Cost**: Determine the total price (including any multi-car discounts or event surcharges).
3.  **Send Quote**:
    - Scroll to the **"Fleet Quote Calculator"** at the bottom of the message view.
    - Enter the total amount in **ZMW**.
    - Click **"Send Official Quote"**.

---

## ⚡ 4. Automated Background Actions

Once the "Send Official Quote" button is clicked, the system handles the heavy lifting:

- **Virtual Booking**: Creates a 'pending' entry in the `bookings` table linked to a "Multi-Car Fleet" placeholder.
- **Email Notification**: Sends a branded HTML email to the customer with the quote details.
- **Platform Alert**: Adds a notification to the customer's dashboard.
- **Linking**: Connects the support ticket directly to the new booking for easy management.

---

## ✅ 5. Finalizing the Booking (Customer Side)

The customer must accept the quote to proceed:

1.  **View Request**: The customer goes to **Support > My Requests & Quotes**.
2.  **Payment**: They will see a **"PAY NOW"** button next to their quote.
3.  **Verification**: After paying via the Lenco gateway (MTN/Airtel/Card), the booking status automatically flips to **"Confirmed"**.

---

## 🛠️ 6. Admin Management

After a quote is sent, the Admin Can:

- **Deep Link**: Use the **"View Booking"** button inside the message view to jump directly to the reservation details.
- **Re-Quote**: If the customer negotiates, the admin can enter a new amount and re-send the quote instantly.
- **Fulfillment**: Once marked as "Paid", staff can begin assigning the physical fleet.

---

> [!IMPORTANT]
> **Database Note**: Quoted amounts are stored in `support_messages.quote_amount` and the corresponding `bookings.total_price`. Do not manually edit these in the database unless absolutely necessary; use the Support Inbox interface to ensure all notifications are triggered.
