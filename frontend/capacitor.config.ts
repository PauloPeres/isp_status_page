import type { CapacitorConfig } from '@capacitor/cli';
import { BRAND } from './src/app/core/config/brand.config';

const config: CapacitorConfig = {
  appId: 'com.ispstatus.app',
  appName: BRAND.name,
  webDir: 'dist/frontend/browser',
  server: {
    androidScheme: 'https',
    iosScheme: 'https',
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      launchAutoHide: true,
      backgroundColor: '#1A2332',
      showSpinner: false,
      splashImmersive: true,
      splashFullScreen: true,
    },
    StatusBar: {
      style: 'LIGHT',
      backgroundColor: '#1A2332',
    },
    PushNotifications: {
      presentationOptions: ['badge', 'sound', 'alert'],
    },
    Keyboard: {
      resize: 'body',
      resizeOnFullScreen: true,
    },
  },
  ios: {
    contentInset: 'automatic',
    preferredContentMode: 'mobile',
    scheme: BRAND.name,
  },
  android: {
    allowMixedContent: false,
    backgroundColor: '#1A2332',
  },
};

export default config;
