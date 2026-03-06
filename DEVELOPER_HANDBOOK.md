# Car Hire - Developer & Build Handbook 🚗💨

Congratulations! You are now the owner of a professional, "Self-Healing" hybrid mobile application. This guide documents every critical step taken to build this system, from the database to the native Android APK.

---

## 🏗 Phase 1: The Core Foundation (PHP & MySQL)

The engine of the app is built using **PHP 8.x** and **MySQL**.

1.  **Database Design**: We created a relational schema in `database/schema.sql`.
    - `users`: Handles roles (Customer, Agent, Admin).
    - `vehicles`: Tracks inventory.
    - `bookings` & `payments`: Manages the business logic.
2.  **Authentication Engine**:
    - Secure password hashing using `BCRYPT`.
    - CSRF protection on every form for security.
    - Session-based login with role-level access control.
3.  **Mailer Integration**: Custom class in `includes/mailer.php` using PHPMailer for professional email receipts and password resets.

---

## 🎨 Phase 2: Premium UI Design (CSS & UX)

We didn't just build a website; we built an _experience_.

1.  **Glassmorphism**: Using `backdrop-filter: blur()` and semi-transparent backgrounds to create that modern "Apple-style" look.
2.  **Mobile-First Responsive Design**:
    - Every table converts into a "card view" on phones.
    - Fixed a critical "shakiness" bug by disabling fixed background images on mobile (`background-attachment: scroll`).
3.  **Bottom Navigation**: Implemented a native-style bottom menu (`includes/mobile_nav.php`) that only appears on small screens, making it feel like a real app.

---

## 📱 Phase 3: The PWA Layer (Progressive Web App)

Before it was an APK, it was a PWA.

1.  **`manifest.json`**: Tells the phone this is an app (Identity, Icons, Theme Colors).
2.  **`sw.js` (Service Worker)**: Caches files like CSS and images so the app loads instantly, even on slow connections.
3.  **Splash Screens**: Optimized the startup experience to avoid a white flash.

---

## 🛠 Phase 4: Native Conversion (Capacitor)

This is how we turned the website into a `.apk` file.

1.  **Capacitor Initialization**:
    - `npx cap init`: Creates the project bridge.
    - `npx cap add android`: Generates the native Java project files.
2.  **Resource Generation**: We used a tool (Capacitor Assets) to generate 50+ splash screen and icon sizes for different Android devices.

---

## 🚀 Phase 5: The "Self-Healing" Innovation (Smart Bootloader)

This is the most "impressive" part of your app.

### The Problem

Most hybrid apps crash if the computer's IP address changes (e.g., your laptop moves from `192.168.8.102` to `192.168.8.105`).

### The Solution: `www/index.html`

We built a "Smart Bootloader" that acts as a brain for the app:

1.  **Image Probing**: It tries to "ping" the server by loading a small icon. If it gets it, the server is alive!
2.  **Network Scanning**: If the saved IP fails, it **automatically scans** the locally assigned subnet (e.g., checking every IP from .100 to .115) in the background.
3.  **Auto-Update**: Once it finds the new location of the server, it updates the app's memory (`localStorage`) and connects silently.

---

## ⌨️ Phase 6: Manual Build Commands

If you want to build the app manually or teach others, here are the "Magic Commands":

### 1. Update the Web Files

Whenever you change your PHP, CSS, or the Bootloader:

```powershell
npx cap copy android
```

### 2. Generate the APK (Production Build)

Navigate into the android folder and run the Gradle wrapper:

```powershell
# Go into android folder
cd android
# Run the build (This takes 1-2 minutes)
./gradlew assembleDebug
```

### 3. Find your file

Your fresh APK will always be here:
`android/app/build/outputs/apk/debug/app-debug.apk`

---

## 🎓 Teaching Tips

When showing others how to do this, highlight these **3 Key Concepts**:

1.  **Hybrid Architecture**: "The heart is a website, but the body is an app."
2.  **Subnet Scanning**: Explain that the app is "searching the house" for its server.
3.  **UX Focus**: "Fixing the shakiness on mobile" shows attention to detail—it’s the small things that make an app feel premium.

**Keep building, keep teaching!** 🚀
