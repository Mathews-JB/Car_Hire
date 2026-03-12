# 📱 Car Hire — Mobile App Build Guide

> **App Name:** Car Hire  
> **Package ID:** `com.carhire.app`  
> **Framework:** Capacitor 8 + Gradle (Android)  
> **Web Directory:** `www/`

---

## 📋 Table of Contents

1. [Prerequisites](#-prerequisites)
2. [Project Structure](#-project-structure)
3. [One-Time Setup](#-one-time-setup)
4. [Build Workflow (Step-by-Step)](#-build-workflow-step-by-step)
5. [Quick Build Cheat Sheet](#-quick-build-cheat-sheet)
6. [Finding Your APK](#-finding-your-apk)
7. [Common Errors & Fixes](#-common-errors--fixes)
8. [Updating the App](#-updating-the-app)
9. [Building a Release APK (for Play Store)](#-building-a-release-apk-for-play-store)

---

## 🔧 Prerequisites

Before you can build, make sure you have these installed on your Windows machine:

| Tool            | Required Version | How to Check         | Download Link                                                 |
| --------------- | ---------------- | -------------------- | ------------------------------------------------------------- |
| **Node.js**     | 18+              | `node -v`            | [nodejs.org](https://nodejs.org)                              |
| **npm**         | 9+               | `npm -v`             | Comes with Node.js                                            |
| **Java JDK**    | 17+              | `java -version`      | [adoptium.net](https://adoptium.net)                          |
| **Android SDK** | API 36           | Check Android Studio | [developer.android.com](https://developer.android.com/studio) |
| **Gradle**      | 8+               | `gradle -v`          | Bundled with the project                                      |

### Environment Variables (Windows)

Make sure these are set in your System Environment Variables:

```
ANDROID_HOME = C:\Users\milot\AppData\Local\Android\Sdk
JAVA_HOME    = C:\Program Files\Eclipse Adoptium\jdk-17.x.x  (or your JDK path)
```

Also add these to your **PATH**:

```
%ANDROID_HOME%\platform-tools
%ANDROID_HOME%\tools
%JAVA_HOME%\bin
```

---

## 📂 Project Structure

Here's what matters for the mobile build:

```
Car_Higher/
├── www/                          ← 🌐 Your web app goes here (what gets bundled)
│   ├── index.html                ← Main entry point for the mobile app
│   └── logo.png                  ← App logo
├── capacitor.config.json         ← ⚙️ Capacitor configuration
├── package.json                  ← 📦 Node dependencies (Capacitor packages)
├── node_modules/                 ← Installed packages
└── android/                      ← 🤖 Android native project
    ├── app/
    │   ├── build.gradle          ← App-level build config
    │   └── src/main/
    │       ├── assets/           ← Web files copied here by Capacitor
    │       ├── res/              ← App icons, splash screens
    │       └── AndroidManifest.xml
    ├── build.gradle              ← Project-level build config
    ├── variables.gradle          ← SDK versions & dependency versions
    ├── gradlew.bat               ← ⭐ Gradle wrapper (USE THIS to build)
    └── local.properties          ← Your local SDK path
```

---

## 🏁 One-Time Setup

Run these commands **only once** when setting up on a new machine:

### Step 1: Install Node dependencies

```powershell
cd c:\xampp\htdocs\Car_Higher
npm install
```

### Step 2: Verify Android SDK is found

```powershell
# Check that local.properties points to your SDK
type android\local.properties
# Should show: sdk.dir=C\:\Users\milot\AppData\Local\Android\Sdk
```

### Step 3: Verify Java

```powershell
java -version
# Should show version 17 or higher
```

That's it for one-time setup! ✅

---

## 🔨 Build Workflow (Step-by-Step)

Follow these steps **every time** you want to build a new APK after making changes:

### Step 1: Update your web files in `www/`

The `www/` folder is what Capacitor bundles into the app. Make sure your latest web content is inside `www/`.

If you manually manage `www/`, just edit the files directly.

### Step 2: Sync Capacitor

This copies your `www/` files into the Android project and syncs any plugin changes:

```powershell
cd c:\xampp\htdocs\Car_Higher
npx cap sync android
```

**What this does:**

- Copies everything from `www/` → `android/app/src/main/assets/public/`
- Updates `capacitor.config.json` inside the Android project
- Syncs any Capacitor plugin native code

### Step 3: Build the Debug APK

```powershell
cd c:\xampp\htdocs\Car_Higher\android
.\gradlew.bat assembleDebug
```

**Wait for it...** This can take 1-5 minutes depending on your machine. You'll see:

```
BUILD SUCCESSFUL in Xm Xs
```

### Step 4: Find your APK! 🎉

Your APK will be at:

```
c:\xampp\htdocs\Car_Higher\android\app\build\outputs\apk\debug\app-debug.apk
```

You can copy it to your desktop:

```powershell
copy "c:\xampp\htdocs\Car_Higher\android\app\build\outputs\apk\debug\app-debug.apk" "$env:USERPROFILE\Desktop\CarHire.apk"
```

Or send it to your phone via USB, email, WhatsApp, etc.

---

## ⚡ Quick Build Cheat Sheet

Once you're comfortable, here's the **entire build process in 3 commands**:

```powershell
# Run these from: c:\xampp\htdocs\Car_Higher

# 1. Sync web files to Android
npx cap sync android

# 2. Build the APK
cd android && .\gradlew.bat assembleDebug && cd ..

# 3. Copy to Desktop (optional)
copy "android\app\build\outputs\apk\debug\app-debug.apk" "$env:USERPROFILE\Desktop\CarHire.apk"
```

**That's it!** Three commands. 🚀

---

## 📍 Finding Your APK

| Build Type           | APK Location                                               |
| -------------------- | ---------------------------------------------------------- |
| **Debug**            | `android\app\build\outputs\apk\debug\app-debug.apk`        |
| **Release**          | `android\app\build\outputs\apk\release\app-release.apk`    |
| **AAB (Play Store)** | `android\app\build\outputs\bundle\release\app-release.aab` |

---

## 🔥 Common Errors & Fixes

### ❌ `JAVA_HOME is not set`

```powershell
# Fix: Set JAVA_HOME temporarily
$env:JAVA_HOME = "C:\Program Files\Eclipse Adoptium\jdk-17.0.x"
# Or find your Java installation:
where java
```

### ❌ `SDK location not found`

```powershell
# Fix: Make sure local.properties exists in the android/ folder
echo "sdk.dir=C\:\\Users\\milot\\AppData\\Local\\Android\\Sdk" > android\local.properties
```

### ❌ `Could not determine java version`

```powershell
# Fix: Make sure you have JDK 17+, not just JRE
java -version
# If it says JRE, install JDK from https://adoptium.net
```

### ❌ `npx cap sync` fails with module errors

```powershell
# Fix: Reinstall node modules
Remove-Item -Recurse -Force node_modules
npm install
npx cap sync android
```

### ❌ Build fails with "license not accepted"

```powershell
# Fix: Accept Android SDK licenses
cd $env:ANDROID_HOME\tools\bin
.\sdkmanager.bat --licenses
# Type 'y' for all prompts
```

### ❌ Gradle build is very slow

```powershell
# Tip: Add these to android\gradle.properties for faster builds:
# org.gradle.daemon=true
# org.gradle.parallel=true
# org.gradle.caching=true
```

---

## 🔄 Updating the App

### When you change web content (PHP pages, CSS, JS):

1. Update the files in `www/` (or copy them there)
2. Run `npx cap sync android`
3. Run `.\gradlew.bat assembleDebug` in the `android/` folder

### When you want to add a new Capacitor plugin:

```powershell
cd c:\xampp\htdocs\Car_Higher

# 1. Install the plugin
npm install @capacitor/camera    # example plugin

# 2. Sync to Android
npx cap sync android

# 3. Rebuild
cd android && .\gradlew.bat assembleDebug
```

### When you want to change app settings (name, icon, splash):

- **App Name / ID:** Edit `capacitor.config.json`
- **Splash Screen:** Replace images in `android\app\src\main\res\drawable\`
- **App Icon:** Replace images in `android\app\src\main\res\mipmap-*\` folders
- **SDK Versions:** Edit `android\variables.gradle`

After changes, run `npx cap sync android` and rebuild.

---

## 🏪 Building a Release APK (for Play Store)

When you're ready to publish:

### Step 1: Generate a Signing Key (one-time)

```powershell
keytool -genkey -v -keystore my-release-key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias car-hire
```

⚠️ **SAVE THIS KEY FILE AND PASSWORD!** You'll need them for every future update.

### Step 2: Build Release APK

```powershell
cd c:\xampp\htdocs\Car_Higher\android
.\gradlew.bat assembleRelease
```

### Step 3: Sign the APK

```powershell
# Use Android's apksigner
"%ANDROID_HOME%\build-tools\36.0.0\apksigner.bat" sign --ks ..\my-release-key.jks --out app-release-signed.apk app\build\outputs\apk\release\app-release-unsigned.apk
```

### Step 4: Upload to Play Store

1. Go to [Google Play Console](https://play.google.com/console)
2. Create a new app
3. Upload your signed APK or AAB
4. Fill in store listing details
5. Submit for review

---

## 📝 Version Bumping

Before each release, update the version in `android\app\build.gradle`:

```gradle
defaultConfig {
    versionCode 2        // ← Increment this by 1 each release (integer)
    versionName "1.1"    // ← Human-readable version string
}
```

---

## 💡 Pro Tips

1. **Use `npx cap open android`** to open the project in Android Studio for advanced debugging
2. **Use `npx cap run android`** to build AND install directly to a connected phone
3. **Clean build** if things go wrong: `cd android && .\gradlew.bat clean && .\gradlew.bat assembleDebug`
4. **Live reload** during development: Add `"url": "http://YOUR_PC_IP:80"` to `capacitor.config.json` under `server`

---

_Last updated: March 8, 2026_
