# Car Hire - Mobile App Deployment Guide (APK & Play Store)

This guide explains how to convert the **Car Hire** web system into a native Android application (APK) using **Trusted Web Activities (TWA)**. This is the official and recommended way by Google to bring PWAs to the Play Store.

## Prerequisites

1. **Live HTTPS URL**: The app MUST be hosted on a public server with SSL (e.g., `https://CarHire.zm`). Localhost will not work for the Play Store.
2. **Node.js**: Installed on your development machine.
3. **Android Studio**: Required for generating signing keys and testing locally.

---

## Phase 1: PWA Verification

We have already implemented the foundation for a Progressive Web App:

- [x] `manifest.json`: Defines the app name, colors, and icons.
- [x] `sw.js`: Enables offline support and performance caching.
- [x] Service Worker Registration: Integrated into `index.php` and `our-fleet.php`.

**Action Needed**: Replace the placeholder icons in `public/images/` with your actual logo:

- `icon-192x192.png`
- `icon-512x512.png`

---

## Phase 2: Generating the APK (using Bubblewrap)

We recommend using **Bubblewrap**, a CLI tool from Google that automates the creation of Android projects from PWAs.

### 1. Install Bubblewrap

Open your terminal and run:

```bash
npm install -g @bubblewrap/cli
```

### 2. Initialize the Project

Navigate to a clean directory where you want to store your Android project:

```bash
bubblewrap init --manifest=https://your-domain.com/manifest.json
```

Follow the prompts:

- It will detect your app name, colors, and icons.
- It will ask for a **Package ID** (e.g., `com.CarHire.app`).
- It will generate a signing key (if you don't have one). **Keep the password safe!**

### 3. Build the APK

```bash
bubblewrap build
```

This command will generate two files:

- `app-release-bundle.aab`: Use this to upload to the **Google Play Console**.
- `app-release-signed.apk`: Use this to install directly on your phone for testing.

---

## Phase 3: Linking Web & App (Digital Asset Links)

To remove the browser address bar in your app (making it look truly native), you must prove ownership of the domain.

1. Bubblewrap will generate a file called `assetlinks.json` in a folder named `.well-known/`.
2. **Upload this file** to your web server at:
   `https://your-domain.com/.well-known/assetlinks.json`

Once uploaded, the Android app will automatically hide the browser UI and look 100% native.

---

## Phase 4: Play Store Submission

1. Create a developer account at [Google Play Console](https://play.google.com/console).
2. Create a new app and upload the `.aab` file generated in Phase 2.
3. Fill in the store listing (descriptions, screenshots).
4. Submit for review!

---

## Phase 5: Native Wrapper (using Capacitor)

If you need deeper native features (Camera, Geolocation, Push Notifications) or want a dedicated Android Studio project, use **Capacitor**.

### 1. Requirements

We have already initialized the following:

- [x] `@capacitor/core`, `@capacitor/cli`, `@capacitor/android` installed.
- [x] `capacitor.config.json` configured.
- [x] `android` directory created.
- [x] `www` directory created (used as a fallback/redirect shell).

### 2. Configure Live URL

Since this is a PHP application, the native app must point to your hosted URL.
Edit `capacitor.config.json` and change the `server` block:

```json
"server": {
  "url": "https://your-hosted-domain.com",
  "cleartext": true
}
```

### 3. Sync and Open in Android Studio

After making changes to the config or icons:

```bash
npx cap sync
npx cap open android
```

- This will open **Android Studio**.
- From Android Studio, you can run the app on an Emulator or a real device, and generate a **Signed APK/Bundle** for the Play Store.

### 4. Updating the App

If you change your PHP code on the server, the app updates automatically because it loads the URL. If you change native code or Capacitor settings:

```bash
npx cap copy
npx cap update
```

---

### Need Help?

If you encounter issues with Bubblewrap or Capacitor, or need help generating the icons, let me know!
