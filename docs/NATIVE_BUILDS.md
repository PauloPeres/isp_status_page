# Native Mobile Builds (Capacitor)

The ISP Status frontend is built with Angular + Ionic and uses Capacitor 8 for native iOS and Android builds.

## Prerequisites

- **Node.js >= 22.0** (required by Capacitor 8 CLI)
- **iOS**: macOS with Xcode 15+ and CocoaPods
- **Android**: Android Studio with SDK 34+

## Initial Setup

```bash
cd frontend

# Install dependencies
npm install

# Build the Angular app
npm run build:prod

# Add native platforms (first time only)
npx cap add ios
npx cap add android
```

## Building

### Android

```bash
# Build web + sync to Android
npm run build:android

# Open in Android Studio
npm run cap:android

# Or build APK from command line
cd android && ./gradlew assembleDebug
```

### iOS

```bash
# Build web + sync to iOS
npm run build:ios

# Open in Xcode
npm run cap:ios
```

## App Icons & Splash Screens

Source assets are in `resources/`:
- `resources/icon.svg` — App icon (1024x1024 source)
- `resources/splash.svg` — Splash screen (2732x2732 source)

### Generate all icon sizes

```bash
# Install the asset generator
npm install -D @capacitor/assets

# Generate icons and splash screens for all platforms
npm run icons
```

This generates all required sizes for iOS (AppIcon.appiconset) and Android (mipmap-*).

## Configuration

The Capacitor config is in `capacitor.config.ts`:

| Setting | Value |
|---------|-------|
| App ID | `com.ispstatus.app` |
| App Name | ISP Status |
| Web Dir | `dist/frontend/browser` |
| URL Scheme | `https` (both platforms) |
| Brand Color | `#6366F1` (Indigo) |

### Plugins Configured

| Plugin | Purpose |
|--------|---------|
| SplashScreen | 2s branded splash on launch |
| StatusBar | Light style, brand-colored background |
| PushNotifications | Badge, sound, alert presentation |
| Keyboard | Body resize mode |

## API URL Configuration

For production builds, update `src/environments/environment.prod.ts`:

```typescript
export const environment = {
  production: true,
  apiUrl: 'https://your-domain.com/api/v2',
  appName: 'ISP Status',
};
```

## Signing

### Android

Create `android/keystore.properties`:
```
storePassword=<password>
keyPassword=<password>
keyAlias=ispstatus
storeFile=../keystore/release.jks
```

Generate a keystore:
```bash
keytool -genkey -v -keystore keystore/release.jks -keyalg RSA -keysize 2048 -validity 10000 -alias ispstatus
```

### iOS

Configure signing in Xcode:
1. Open `ios/App/App.xcworkspace`
2. Select the App target
3. Under "Signing & Capabilities", select your team and bundle ID

## Build Troubleshooting

- **"webDir not found"**: Run `npm run build:prod` first
- **Capacitor CLI requires Node >= 22**: Upgrade Node.js (`nvm install 22`)
- **Gradle sync failed**: Open Android Studio and let it sync/update
- **CocoaPods not found**: `sudo gem install cocoapods`
