# React Native Mobile App Specifications
## LoanWealth P2P Lending Platform - Phase 2

---

## Executive Summary

This document outlines the complete specifications for the LoanWealth mobile application built with React Native. The app provides a seamless mobile experience for borrowers and lenders, with native performance, biometric authentication, real-time updates, and offline capabilities.

### Key Features
- Universal app for iOS and Android
- Biometric authentication (Face ID, Touch ID, Fingerprint)
- Real-time loan marketplace with push notifications
- Camera-based document capture and OCR
- Offline mode with data synchronization
- Native performance with 60 FPS animations
- End-to-end encryption for sensitive data

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Technology Stack](#technology-stack)
3. [Project Structure](#project-structure)
4. [Navigation Architecture](#navigation-architecture)
5. [State Management](#state-management)
6. [API Integration](#api-integration)
7. [Authentication & Security](#authentication--security)
8. [Core Features](#core-features)
9. [Platform-Specific Features](#platform-specific-features)
10. [Push Notifications](#push-notifications)
11. [Offline Capabilities](#offline-capabilities)
12. [Performance Optimization](#performance-optimization)
13. [Testing Strategy](#testing-strategy)
14. [Deployment & CI/CD](#deployment--cicd)

---

## Architecture Overview

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     React Native App                         │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────────────────────────────────────────────┐  │
│  │                  Presentation Layer                   │  │
│  │    Screens │ Components │ Navigation │ Animations    │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                   Business Logic                      │  │
│  │     Hooks │ Services │ Validators │ Formatters      │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                    State Layer                        │  │
│  │   Redux Toolkit │ RTK Query │ Redux Persist         │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                 Infrastructure Layer                  │  │
│  │    API Client │ WebSocket │ Storage │ Biometrics    │  │
│  └──────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────┤
│                     Native Modules                           │
│     iOS (Swift) │ Android (Kotlin) │ Shared C++           │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Backend API                       │
│                  (Phase 1 Implementation)                    │
└─────────────────────────────────────────────────────────────┘
```

### Design Principles

1. **Mobile-First**: Optimized for touch interactions and mobile viewports
2. **Performance**: 60 FPS animations, lazy loading, code splitting
3. **Offline-First**: Core features work offline with sync when connected
4. **Security**: Biometric auth, encrypted storage, certificate pinning
5. **Accessibility**: VoiceOver/TalkBack support, WCAG 2.1 AA compliance
6. **Platform Parity**: Consistent experience with platform-specific enhancements

---

## Technology Stack

### Core Dependencies

```json
{
  "dependencies": {
    "react": "18.2.0",
    "react-native": "0.73.0",
    "@reduxjs/toolkit": "^2.0.0",
    "react-navigation": "^6.0.0",
    "react-native-reanimated": "^3.6.0",
    "react-native-gesture-handler": "^2.14.0",
    "react-native-screens": "^3.29.0",
    "react-native-safe-area-context": "^4.8.0",
    "react-native-keychain": "^8.1.0",
    "react-native-biometrics": "^3.0.0",
    "react-native-push-notification": "^8.1.0",
    "react-native-camera": "^4.2.0",
    "react-native-document-scanner": "^2.0.0",
    "react-native-fast-image": "^8.6.0",
    "react-native-vector-icons": "^10.0.0",
    "react-native-config": "^1.5.0",
    "react-native-mmkv": "^2.11.0",
    "react-native-netinfo": "^11.2.0",
    "react-native-background-fetch": "^4.2.0",
    "react-native-codepush": "^8.1.0",
    "react-native-sentry": "^5.15.0",
    "react-native-flipper": "^0.212.0",
    "react-native-svg": "^14.1.0",
    "react-native-linear-gradient": "^2.8.0",
    "react-native-haptic-feedback": "^2.2.0",
    "socket.io-client": "^4.6.0",
    "axios": "^1.6.0",
    "date-fns": "^3.0.0",
    "yup": "^1.3.0",
    "react-hook-form": "^7.48.0"
  },
  "devDependencies": {
    "@types/react-native": "^0.73.0",
    "typescript": "^5.3.0",
    "metro-react-native-babel-preset": "^0.77.0",
    "@testing-library/react-native": "^12.4.0",
    "detox": "^20.14.0",
    "jest": "^29.7.0",
    "eslint": "^8.55.0",
    "prettier": "^3.1.0",
    "reactotron-react-native": "^5.0.0"
  }
}
```

---

## Project Structure

```
src/
├── app/                    # App entry points and configuration
│   ├── App.tsx            # Main app component
│   ├── Navigation.tsx     # Navigation container
│   └── RootLayout.tsx     # Root layout wrapper
│
├── screens/               # Screen components
│   ├── auth/
│   │   ├── LoginScreen.tsx
│   │   ├── RegisterScreen.tsx
│   │   ├── BiometricSetupScreen.tsx
│   │   └── ForgotPasswordScreen.tsx
│   ├── borrower/
│   │   ├── DashboardScreen.tsx
│   │   ├── LoanApplicationScreen.tsx
│   │   ├── LoanDetailsScreen.tsx
│   │   └── PaymentScreen.tsx
│   ├── lender/
│   │   ├── MarketplaceScreen.tsx
│   │   ├── PortfolioScreen.tsx
│   │   ├── BiddingScreen.tsx
│   │   └── WalletScreen.tsx
│   └── shared/
│       ├── ProfileScreen.tsx
│       ├── NotificationsScreen.tsx
│       └── SettingsScreen.tsx
│
├── components/            # Reusable components
│   ├── common/
│   │   ├── Button.tsx
│   │   ├── Input.tsx
│   │   ├── Card.tsx
│   │   └── Modal.tsx
│   ├── loan/
│   │   ├── LoanCard.tsx
│   │   ├── FundingProgress.tsx
│   │   └── RepaymentSchedule.tsx
│   └── charts/
│       ├── PortfolioChart.tsx
│       └── PerformanceChart.tsx
│
├── navigation/            # Navigation configuration
│   ├── types.ts
│   ├── RootNavigator.tsx
│   ├── AuthNavigator.tsx
│   ├── BorrowerNavigator.tsx
│   └── LenderNavigator.tsx
│
├── store/                 # Redux store
│   ├── index.ts
│   ├── slices/
│   │   ├── authSlice.ts
│   │   ├── loanSlice.ts
│   │   ├── walletSlice.ts
│   │   └── notificationSlice.ts
│   └── api/
│       ├── apiSlice.ts
│       └── endpoints/
│
├── services/              # Business logic and external services
│   ├── api/
│   │   ├── client.ts
│   │   └── interceptors.ts
│   ├── websocket/
│   │   └── ReverbClient.ts
│   ├── biometric/
│   │   └── BiometricService.ts
│   ├── storage/
│   │   └── SecureStorage.ts
│   └── push/
│       └── PushNotificationService.ts
│
├── hooks/                 # Custom hooks
│   ├── useAuth.ts
│   ├── useBiometric.ts
│   ├── useOffline.ts
│   └── useWebSocket.ts
│
├── utils/                 # Utility functions
│   ├── formatters.ts
│   ├── validators.ts
│   ├── constants.ts
│   └── helpers.ts
│
├── assets/               # Images, fonts, etc.
│   ├── images/
│   ├── fonts/
│   └── animations/
│
└── types/                # TypeScript type definitions
    ├── models.ts
    ├── api.ts
    └── navigation.ts
```

---

## Navigation Architecture

### Navigation Structure

```typescript
// src/navigation/RootNavigator.tsx

import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createDrawerNavigator } from '@react-navigation/drawer';

const Stack = createNativeStackNavigator<RootStackParamList>();
const Tab = createBottomTabNavigator<TabParamList>();
const Drawer = createDrawerNavigator<DrawerParamList>();

export function RootNavigator() {
  const { isAuthenticated, user } = useAuth();

  return (
    <NavigationContainer
      linking={linking}
      onStateChange={handleNavigationStateChange}
    >
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {!isAuthenticated ? (
          <Stack.Group>
            <Stack.Screen name="Welcome" component={WelcomeScreen} />
            <Stack.Screen name="Login" component={LoginScreen} />
            <Stack.Screen name="Register" component={RegisterScreen} />
            <Stack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
          </Stack.Group>
        ) : (
          <>
            {user?.user_type === 'borrower' ? (
              <Stack.Screen name="BorrowerMain" component={BorrowerNavigator} />
            ) : (
              <Stack.Screen name="LenderMain" component={LenderNavigator} />
            )}
            <Stack.Group screenOptions={{ presentation: 'modal' }}>
              <Stack.Screen name="LoanDetails" component={LoanDetailsScreen} />
              <Stack.Screen name="Payment" component={PaymentScreen} />
              <Stack.Screen name="DocumentScanner" component={DocumentScannerScreen} />
            </Stack.Group>
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}

// Borrower Tab Navigator
function BorrowerNavigator() {
  return (
    <Tab.Navigator
      screenOptions={{
        tabBarActiveTintColor: '#6366F1',
        tabBarInactiveTintColor: '#9CA3AF',
        tabBarStyle: {
          backgroundColor: '#FFFFFF',
          borderTopWidth: 1,
          borderTopColor: '#E5E7EB',
          paddingBottom: Platform.OS === 'ios' ? 20 : 10,
          height: Platform.OS === 'ios' ? 85 : 65,
        },
      }}
    >
      <Tab.Screen
        name="Dashboard"
        component={BorrowerDashboard}
        options={{
          tabBarIcon: ({ color, size }) => (
            <Icon name="home" size={size} color={color} />
          ),
        }}
      />
      <Tab.Screen
        name="MyLoans"
        component={MyLoansScreen}
        options={{
          tabBarIcon: ({ color, size }) => (
            <Icon name="file-text" size={size} color={color} />
          ),
          tabBarBadge: unreadLoans > 0 ? unreadLoans : undefined,
        }}
      />
      <Tab.Screen
        name="Apply"
        component={LoanApplicationScreen}
        options={{
          tabBarIcon: ({ color, size }) => (
            <View style={styles.applyButton}>
              <Icon name="plus" size={size} color="#FFFFFF" />
            </View>
          ),
        }}
      />
      <Tab.Screen
        name="Payments"
        component={PaymentsScreen}
        options={{
          tabBarIcon: ({ color, size }) => (
            <Icon name="credit-card" size={size} color={color} />
          ),
        }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileScreen}
        options={{
          tabBarIcon: ({ color, size }) => (
            <Icon name="user" size={size} color={color} />
          ),
        }}
      />
    </Tab.Navigator>
  );
}
```

### Deep Linking Configuration

```typescript
// src/navigation/linking.ts

export const linking = {
  prefixes: ['loanwealth://', 'https://app.loanwealth.com'],
  config: {
    screens: {
      Welcome: 'welcome',
      Login: 'login',
      Register: 'register',
      BorrowerMain: {
        screens: {
          Dashboard: 'dashboard',
          MyLoans: 'loans',
          Apply: 'apply',
          Payments: 'payments',
          Profile: 'profile',
        },
      },
      LenderMain: {
        screens: {
          Marketplace: 'marketplace',
          Portfolio: 'portfolio',
          Wallet: 'wallet',
        },
      },
      LoanDetails: 'loan/:id',
      Payment: 'payment/:loanId',
    },
  },
};
```

---

## State Management

### Redux Toolkit Setup

```typescript
// src/store/index.ts

import { configureStore } from '@reduxjs/toolkit';
import { setupListeners } from '@reduxjs/toolkit/query';
import {
  persistStore,
  persistReducer,
  FLUSH,
  REHYDRATE,
  PAUSE,
  PERSIST,
  PURGE,
  REGISTER,
} from 'redux-persist';
import { MMKV } from 'react-native-mmkv';

// Custom MMKV storage adapter
const storage = new MMKV({
  id: 'loanwealth-storage',
  encryptionKey: 'your-encryption-key',
});

const MMKVStorage = {
  setItem: (key: string, value: string) => {
    storage.set(key, value);
    return Promise.resolve(true);
  },
  getItem: (key: string) => {
    const value = storage.getString(key);
    return Promise.resolve(value);
  },
  removeItem: (key: string) => {
    storage.delete(key);
    return Promise.resolve();
  },
};

const persistConfig = {
  key: 'root',
  version: 1,
  storage: MMKVStorage,
  whitelist: ['auth', 'user', 'cache'],
};

const rootReducer = combineReducers({
  auth: authSlice.reducer,
  loans: loanSlice.reducer,
  wallet: walletSlice.reducer,
  notifications: notificationSlice.reducer,
  offline: offlineSlice.reducer,
  [apiSlice.reducerPath]: apiSlice.reducer,
});

const persistedReducer = persistReducer(persistConfig, rootReducer);

export const store = configureStore({
  reducer: persistedReducer,
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: [FLUSH, REHYDRATE, PAUSE, PERSIST, PURGE, REGISTER],
      },
    }).concat(apiSlice.middleware),
});

export const persistor = persistStore(store);
setupListeners(store.dispatch);
```

### Auth Slice

```typescript
// src/store/slices/authSlice.ts

import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { BiometricService } from '@/services/biometric/BiometricService';
import { SecureStorage } from '@/services/storage/SecureStorage';

interface AuthState {
  user: User | null;
  token: string | null;
  refreshToken: string | null;
  biometricEnabled: boolean;
  isAuthenticated: boolean;
  loading: boolean;
}

export const loginWithBiometric = createAsyncThunk(
  'auth/loginWithBiometric',
  async () => {
    const isAuthenticated = await BiometricService.authenticate();
    if (!isAuthenticated) {
      throw new Error('Biometric authentication failed');
    }

    const credentials = await SecureStorage.getCredentials();
    if (!credentials) {
      throw new Error('No stored credentials');
    }

    const response = await api.post('/auth/login', credentials);
    return response.data;
  }
);

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    logout: (state) => {
      state.user = null;
      state.token = null;
      state.refreshToken = null;
      state.isAuthenticated = false;
      SecureStorage.clearAll();
    },
    updateUser: (state, action) => {
      state.user = { ...state.user, ...action.payload };
    },
    enableBiometric: (state) => {
      state.biometricEnabled = true;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(loginWithBiometric.pending, (state) => {
        state.loading = true;
      })
      .addCase(loginWithBiometric.fulfilled, (state, action) => {
        state.loading = false;
        state.isAuthenticated = true;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.refreshToken = action.payload.refreshToken;
      })
      .addCase(loginWithBiometric.rejected, (state) => {
        state.loading = false;
        state.isAuthenticated = false;
      });
  },
});
```

---

## API Integration

### API Client Configuration

```typescript
// src/services/api/client.ts

import axios, { AxiosInstance } from 'axios';
import Config from 'react-native-config';
import NetInfo from '@react-native-community/netinfo';
import { store } from '@/store';
import { SecureStorage } from '../storage/SecureStorage';

class APIClient {
  private client: AxiosInstance;
  private isRefreshing = false;
  private failedQueue: any[] = [];

  constructor() {
    this.client = axios.create({
      baseURL: Config.API_URL,
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        'X-Platform': Platform.OS,
        'X-App-Version': Config.APP_VERSION,
      },
    });

    this.setupInterceptors();
  }

  private setupInterceptors() {
    // Request interceptor
    this.client.interceptors.request.use(
      async (config) => {
        const token = await SecureStorage.getToken();
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }

        // Add request ID for tracking
        config.headers['X-Request-ID'] = this.generateRequestId();

        // Check network connectivity
        const netInfo = await NetInfo.fetch();
        if (!netInfo.isConnected) {
          return Promise.reject(new Error('No internet connection'));
        }

        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor
    this.client.interceptors.response.use(
      (response) => response,
      async (error) => {
        const originalRequest = error.config;

        if (error.response?.status === 401 && !originalRequest._retry) {
          if (this.isRefreshing) {
            return new Promise((resolve, reject) => {
              this.failedQueue.push({ resolve, reject });
            }).then((token) => {
              originalRequest.headers.Authorization = `Bearer ${token}`;
              return this.client(originalRequest);
            });
          }

          originalRequest._retry = true;
          this.isRefreshing = true;

          try {
            const refreshToken = await SecureStorage.getRefreshToken();
            const response = await this.refreshTokenRequest(refreshToken);
            const { token } = response.data;

            await SecureStorage.setToken(token);
            this.processQueue(null, token);
            return this.client(originalRequest);
          } catch (err) {
            this.processQueue(err, null);
            store.dispatch(logout());
            return Promise.reject(err);
          } finally {
            this.isRefreshing = false;
          }
        }

        return Promise.reject(error);
      }
    );
  }

  private processQueue(error: any, token: string | null) {
    this.failedQueue.forEach((prom) => {
      if (error) {
        prom.reject(error);
      } else {
        prom.resolve(token);
      }
    });
    this.failedQueue = [];
  }

  private generateRequestId(): string {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }

  // API methods
  async get<T>(url: string, config?: any): Promise<T> {
    const response = await this.client.get<T>(url, config);
    return response.data;
  }

  async post<T>(url: string, data?: any, config?: any): Promise<T> {
    const response = await this.client.post<T>(url, data, config);
    return response.data;
  }

  async put<T>(url: string, data?: any, config?: any): Promise<T> {
    const response = await this.client.put<T>(url, data, config);
    return response.data;
  }

  async delete<T>(url: string, config?: any): Promise<T> {
    const response = await this.client.delete<T>(url, config);
    return response.data;
  }

  async upload(url: string, formData: FormData, onProgress?: (progress: number) => void) {
    const response = await this.client.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        if (onProgress && progressEvent.total) {
          const progress = (progressEvent.loaded / progressEvent.total) * 100;
          onProgress(Math.round(progress));
        }
      },
    });
    return response.data;
  }
}

export default new APIClient();
```

### RTK Query API Slice

```typescript
// src/store/api/apiSlice.ts

import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { SecureStorage } from '@/services/storage/SecureStorage';

export const apiSlice = createApi({
  reducerPath: 'api',
  baseQuery: fetchBaseQuery({
    baseUrl: Config.API_URL,
    prepareHeaders: async (headers) => {
      const token = await SecureStorage.getToken();
      if (token) {
        headers.set('Authorization', `Bearer ${token}`);
      }
      return headers;
    },
  }),
  tagTypes: ['User', 'Loan', 'Wallet', 'Bid', 'Payment'],
  endpoints: (builder) => ({
    // User endpoints
    getProfile: builder.query({
      query: () => '/profile',
      providesTags: ['User'],
    }),
    updateProfile: builder.mutation({
      query: (data) => ({
        url: '/profile',
        method: 'PUT',
        body: data,
      }),
      invalidatesTags: ['User'],
    }),

    // Loan endpoints
    getLoans: builder.query({
      query: (params) => ({
        url: '/loans',
        params,
      }),
      providesTags: ['Loan'],
    }),
    createLoan: builder.mutation({
      query: (data) => ({
        url: '/loans',
        method: 'POST',
        body: data,
      }),
      invalidatesTags: ['Loan'],
    }),

    // Real-time subscription example
    subscribeLoanUpdates: builder.query({
      query: (loanId) => `/loans/${loanId}`,
      async onCacheEntryAdded(
        arg,
        { updateCachedData, cacheDataLoaded, cacheEntryRemoved }
      ) {
        const ws = new WebSocket(`${Config.WS_URL}/loans/${arg}`);

        try {
          await cacheDataLoaded;

          ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            updateCachedData((draft) => {
              Object.assign(draft, data);
            });
          };
        } catch {
          // Handle error
        }

        await cacheEntryRemoved;
        ws.close();
      },
    }),
  }),
});

export const {
  useGetProfileQuery,
  useUpdateProfileMutation,
  useGetLoansQuery,
  useCreateLoanMutation,
  useSubscribeLoanUpdatesQuery,
} = apiSlice;
```

---

## Authentication & Security

### Biometric Authentication

```typescript
// src/services/biometric/BiometricService.ts

import ReactNativeBiometrics, { BiometryTypes } from 'react-native-biometrics';
import { Platform } from 'react-native';

class BiometricService {
  private rnBiometrics = new ReactNativeBiometrics({
    allowDeviceCredentials: true,
  });

  async isBiometricAvailable(): Promise<{
    available: boolean;
    biometryType: string | null;
  }> {
    try {
      const { available, biometryType } = await this.rnBiometrics.isSensorAvailable();
      return {
        available,
        biometryType: this.getBiometryTypeString(biometryType),
      };
    } catch (error) {
      console.error('Biometric check failed:', error);
      return { available: false, biometryType: null };
    }
  }

  async authenticate(reason?: string): Promise<boolean> {
    try {
      const { success } = await this.rnBiometrics.simplePrompt({
        promptMessage: reason || 'Authenticate to access LoanWealth',
        cancelButtonText: 'Cancel',
        fallbackPromptMessage: 'Use passcode',
      });
      return success;
    } catch (error) {
      console.error('Biometric authentication failed:', error);
      return false;
    }
  }

  async createKeys(): Promise<boolean> {
    try {
      const { publicKey } = await this.rnBiometrics.createKeys();
      // Send public key to server for registration
      await api.post('/auth/biometric/register', { publicKey });
      return true;
    } catch (error) {
      console.error('Key creation failed:', error);
      return false;
    }
  }

  async createSignature(payload: string): Promise<string | null> {
    try {
      const { signature } = await this.rnBiometrics.createSignature({
        promptMessage: 'Sign transaction',
        payload,
      });
      return signature;
    } catch (error) {
      console.error('Signature creation failed:', error);
      return null;
    }
  }

  private getBiometryTypeString(type: string | null): string | null {
    if (!type) return null;

    const typeMap: Record<string, string> = {
      [BiometryTypes.TouchID]: 'Touch ID',
      [BiometryTypes.FaceID]: 'Face ID',
      [BiometryTypes.Biometrics]: Platform.OS === 'ios' ? 'Face ID' : 'Fingerprint',
    };

    return typeMap[type] || type;
  }
}

export default new BiometricService();
```

### Secure Storage

```typescript
// src/services/storage/SecureStorage.ts

import * as Keychain from 'react-native-keychain';
import CryptoJS from 'crypto-js';
import { MMKV } from 'react-native-mmkv';

class SecureStorage {
  private storage = new MMKV({
    id: 'secure-storage',
    encryptionKey: this.getEncryptionKey(),
  });

  private async getEncryptionKey(): Promise<string> {
    try {
      const credentials = await Keychain.getInternetCredentials('loanwealth-encryption');
      if (credentials) {
        return credentials.password;
      }

      // Generate new encryption key
      const key = CryptoJS.lib.WordArray.random(256/8).toString();
      await Keychain.setInternetCredentials(
        'loanwealth-encryption',
        'encryption-key',
        key
      );
      return key;
    } catch (error) {
      console.error('Failed to get encryption key:', error);
      throw error;
    }
  }

  async setToken(token: string): Promise<void> {
    await Keychain.setInternetCredentials(
      'loanwealth-api',
      'token',
      token
    );
  }

  async getToken(): Promise<string | null> {
    try {
      const credentials = await Keychain.getInternetCredentials('loanwealth-api');
      return credentials ? credentials.password : null;
    } catch (error) {
      console.error('Failed to get token:', error);
      return null;
    }
  }

  async setCredentials(email: string, password: string): Promise<void> {
    const encrypted = CryptoJS.AES.encrypt(
      JSON.stringify({ email, password }),
      await this.getEncryptionKey()
    ).toString();

    await Keychain.setInternetCredentials(
      'loanwealth-credentials',
      email,
      encrypted
    );
  }

  async getCredentials(): Promise<{ email: string; password: string } | null> {
    try {
      const credentials = await Keychain.getInternetCredentials('loanwealth-credentials');
      if (!credentials) return null;

      const decrypted = CryptoJS.AES.decrypt(
        credentials.password,
        await this.getEncryptionKey()
      ).toString(CryptoJS.enc.Utf8);

      return JSON.parse(decrypted);
    } catch (error) {
      console.error('Failed to get credentials:', error);
      return null;
    }
  }

  setSensitiveData(key: string, value: any): void {
    const encrypted = CryptoJS.AES.encrypt(
      JSON.stringify(value),
      this.getEncryptionKey()
    ).toString();
    this.storage.set(key, encrypted);
  }

  getSensitiveData<T>(key: string): T | null {
    const encrypted = this.storage.getString(key);
    if (!encrypted) return null;

    try {
      const decrypted = CryptoJS.AES.decrypt(
        encrypted,
        this.getEncryptionKey()
      ).toString(CryptoJS.enc.Utf8);
      return JSON.parse(decrypted);
    } catch (error) {
      console.error('Failed to decrypt data:', error);
      return null;
    }
  }

  async clearAll(): Promise<void> {
    await Keychain.resetInternetCredentials('loanwealth-api');
    await Keychain.resetInternetCredentials('loanwealth-credentials');
    this.storage.clearAll();
  }
}

export default new SecureStorage();
```

---

## Core Features

### Borrower Features

#### Loan Application Screen

```typescript
// src/screens/borrower/LoanApplicationScreen.tsx

import React, { useState } from 'react';
import {
  View,
  ScrollView,
  Text,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useForm, Controller } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import Animated, {
  useSharedValue,
  useAnimatedStyle,
  withSpring,
  interpolate,
} from 'react-native-reanimated';

const schema = yup.object({
  amount: yup
    .number()
    .required('Amount is required')
    .min(1000, 'Minimum loan amount is $1,000')
    .max(50000, 'Maximum loan amount is $50,000'),
  term: yup
    .number()
    .required('Loan term is required')
    .oneOf([6, 12, 24, 36], 'Invalid loan term'),
  purpose: yup
    .string()
    .required('Loan purpose is required'),
  description: yup
    .string()
    .required('Description is required')
    .min(50, 'Please provide at least 50 characters'),
});

export function LoanApplicationScreen({ navigation }) {
  const [step, setStep] = useState(1);
  const [calculatedRate, setCalculatedRate] = useState<number | null>(null);
  const progress = useSharedValue(0);

  const { control, handleSubmit, watch, formState: { errors } } = useForm({
    resolver: yupResolver(schema),
    defaultValues: {
      amount: '',
      term: 12,
      purpose: '',
      description: '',
    },
  });

  const animatedStyle = useAnimatedStyle(() => ({
    width: `${interpolate(progress.value, [0, 3], [0, 100])}%`,
  }));

  const calculateLoan = async (data: any) => {
    try {
      const response = await api.post('/loans/calculate', {
        amount: data.amount,
        term: data.term,
      });
      setCalculatedRate(response.data.interestRate);
      setStep(2);
      progress.value = withSpring(2);
    } catch (error) {
      showError('Failed to calculate loan');
    }
  };

  const submitApplication = async (data: any) => {
    try {
      setLoading(true);
      const response = await api.post('/loans/apply', data);

      // Track analytics
      analytics.track('loan_application_submitted', {
        amount: data.amount,
        term: data.term,
        purpose: data.purpose,
      });

      navigation.navigate('LoanDetails', { loanId: response.data.id });
    } catch (error) {
      showError('Failed to submit application');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={styles.container}
    >
      <View style={styles.header}>
        <Text style={styles.title}>Apply for a Loan</Text>
        <View style={styles.progressContainer}>
          <Animated.View style={[styles.progressBar, animatedStyle]} />
        </View>
        <Text style={styles.stepText}>Step {step} of 3</Text>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {step === 1 && (
          <Animated.View entering={FadeIn} exiting={FadeOut}>
            <Controller
              control={control}
              name="amount"
              render={({ field: { onChange, value } }) => (
                <CurrencyInput
                  label="Loan Amount"
                  value={value}
                  onChangeValue={onChange}
                  error={errors.amount?.message}
                  min={1000}
                  max={50000}
                />
              )}
            />

            <Controller
              control={control}
              name="term"
              render={({ field: { onChange, value } }) => (
                <SegmentedControl
                  label="Loan Term (Months)"
                  options={[
                    { label: '6', value: 6 },
                    { label: '12', value: 12 },
                    { label: '24', value: 24 },
                    { label: '36', value: 36 },
                  ]}
                  value={value}
                  onValueChange={onChange}
                />
              )}
            />

            <LoanCalculator
              amount={watch('amount')}
              term={watch('term')}
              rate={calculatedRate}
            />

            <Button
              title="Calculate My Rate"
              onPress={handleSubmit(calculateLoan)}
              style={styles.button}
            />
          </Animated.View>
        )}

        {step === 2 && (
          <Animated.View entering={FadeIn} exiting={FadeOut}>
            <RateOffer
              amount={watch('amount')}
              term={watch('term')}
              rate={calculatedRate}
              monthlyPayment={calculateMonthlyPayment(
                watch('amount'),
                calculatedRate,
                watch('term')
              )}
            />

            <Controller
              control={control}
              name="purpose"
              render={({ field: { onChange, value } }) => (
                <Picker
                  label="Loan Purpose"
                  value={value}
                  onValueChange={onChange}
                  items={[
                    { label: 'Debt Consolidation', value: 'debt_consolidation' },
                    { label: 'Home Improvement', value: 'home_improvement' },
                    { label: 'Medical Expenses', value: 'medical' },
                    { label: 'Education', value: 'education' },
                    { label: 'Business', value: 'business' },
                    { label: 'Other', value: 'other' },
                  ]}
                  error={errors.purpose?.message}
                />
              )}
            />

            <Controller
              control={control}
              name="description"
              render={({ field: { onChange, value } }) => (
                <TextArea
                  label="Tell us more about your loan purpose"
                  value={value}
                  onChangeText={onChange}
                  error={errors.description?.message}
                  maxLength={500}
                  numberOfLines={4}
                />
              )}
            />

            <View style={styles.buttonRow}>
              <Button
                title="Back"
                onPress={() => {
                  setStep(1);
                  progress.value = withSpring(1);
                }}
                variant="secondary"
                style={styles.halfButton}
              />
              <Button
                title="Continue"
                onPress={() => {
                  setStep(3);
                  progress.value = withSpring(3);
                }}
                style={styles.halfButton}
              />
            </View>
          </Animated.View>
        )}

        {step === 3 && (
          <Animated.View entering={FadeIn}>
            <ApplicationSummary
              amount={watch('amount')}
              term={watch('term')}
              purpose={watch('purpose')}
              description={watch('description')}
              rate={calculatedRate}
            />

            <DocumentUpload
              onUpload={handleDocumentUpload}
              required={['income_proof', 'identity_proof']}
            />

            <AgreementCheckbox
              text="I agree to the terms and conditions"
              checked={termsAccepted}
              onToggle={setTermsAccepted}
            />

            <View style={styles.buttonRow}>
              <Button
                title="Back"
                onPress={() => {
                  setStep(2);
                  progress.value = withSpring(2);
                }}
                variant="secondary"
                style={styles.halfButton}
              />
              <Button
                title="Submit Application"
                onPress={handleSubmit(submitApplication)}
                loading={loading}
                disabled={!termsAccepted}
                style={styles.halfButton}
              />
            </View>
          </Animated.View>
        )}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB',
  },
  header: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 20,
    paddingTop: 20,
    paddingBottom: 15,
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    color: '#111827',
    marginBottom: 15,
  },
  progressContainer: {
    height: 4,
    backgroundColor: '#E5E7EB',
    borderRadius: 2,
    overflow: 'hidden',
  },
  progressBar: {
    height: '100%',
    backgroundColor: '#6366F1',
  },
  stepText: {
    fontSize: 14,
    color: '#6B7280',
    marginTop: 8,
  },
  scrollContent: {
    padding: 20,
  },
  button: {
    marginTop: 20,
  },
  buttonRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 20,
  },
  halfButton: {
    flex: 0.48,
  },
});
```

### Lender Features

#### Marketplace with Real-time Updates

```typescript
// src/screens/lender/MarketplaceScreen.tsx

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  FlatList,
  RefreshControl,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { useWebSocket } from '@/hooks/useWebSocket';
import { useInfiniteQuery } from '@tanstack/react-query';
import Animated, { FadeInDown } from 'react-native-reanimated';

export function MarketplaceScreen({ navigation }) {
  const [filters, setFilters] = useState({
    riskGrade: [],
    termMonths: [],
    minAmount: null,
    maxAmount: null,
  });

  const {
    data,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    refetch,
    isLoading,
  } = useInfiniteQuery({
    queryKey: ['marketplace', filters],
    queryFn: ({ pageParam = 1 }) =>
      api.get('/marketplace/loans', {
        params: { ...filters, page: pageParam },
      }),
    getNextPageParam: (lastPage) =>
      lastPage.hasMore ? lastPage.page + 1 : undefined,
  });

  // Real-time updates
  useWebSocket('marketplace', {
    onMessage: (event) => {
      if (event.type === 'loan.funded') {
        updateLoanInList(event.loan);
      }
    },
  });

  const renderLoanCard = useCallback(({ item, index }) => (
    <Animated.View
      entering={FadeInDown.delay(index * 50).springify()}
    >
      <LoanCard
        loan={item}
        onPress={() => navigation.navigate('LoanDetails', { loanId: item.id })}
        onQuickBid={() => handleQuickBid(item)}
      />
    </Animated.View>
  ), []);

  return (
    <View style={styles.container}>
      <MarketplaceHeader
        onFilterPress={() => setShowFilters(true)}
        activeFilters={getActiveFilterCount(filters)}
      />

      <FlatList
        data={loans}
        renderItem={renderLoanCard}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContent}
        refreshControl={
          <RefreshControl refreshing={isLoading} onRefresh={refetch} />
        }
        onEndReached={fetchNextPage}
        onEndReachedThreshold={0.5}
        ListFooterComponent={() =>
          isFetchingNextPage ? <ActivityIndicator /> : null
        }
        ListEmptyComponent={<EmptyState />}
      />

      <FilterModal
        visible={showFilters}
        filters={filters}
        onApply={setFilters}
        onClose={() => setShowFilters(false)}
      />
    </View>
  );
}
```

---

## Platform-Specific Features

### iOS-Specific Implementation

```swift
// ios/LoanWealth/AppDelegate.swift

import UIKit
import Firebase
import CodePush

@UIApplicationMain
class AppDelegate: UIResponder, UIApplicationDelegate {

    func application(_ application: UIApplication,
                    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?) -> Bool {

        // Configure Firebase
        FirebaseApp.configure()

        // Configure CodePush
        CodePush.setDeploymentKey("YOUR_IOS_DEPLOYMENT_KEY")

        // Configure push notifications
        UNUserNotificationCenter.current().delegate = self
        application.registerForRemoteNotifications()

        // Configure appearance
        configureAppearance()

        return true
    }

    private func configureAppearance() {
        // Navigation bar
        UINavigationBar.appearance().tintColor = UIColor(hex: "#6366F1")
        UINavigationBar.appearance().largeTitleTextAttributes = [
            .foregroundColor: UIColor(hex: "#111827")
        ]

        // Tab bar
        UITabBar.appearance().tintColor = UIColor(hex: "#6366F1")
    }
}

// Widget Extension
class LoanWealthWidget: Widget {
    let kind: String = "LoanWealthWidget"

    var body: some WidgetConfiguration {
        StaticConfiguration(kind: kind, provider: Provider()) { entry in
            LoanWealthWidgetEntryView(entry: entry)
        }
        .configurationDisplayName("LoanWealth")
        .description("Track your loans and investments")
        .supportedFamilies([.systemSmall, .systemMedium])
    }
}
```

### Android-Specific Implementation

```kotlin
// android/app/src/main/java/com/loanwealth/MainActivity.kt

package com.loanwealth

import android.os.Bundle
import com.facebook.react.ReactActivity
import com.facebook.react.ReactActivityDelegate
import com.facebook.react.defaults.DefaultNewArchitectureEntryPoint.fabricEnabled
import com.facebook.react.defaults.DefaultReactActivityDelegate
import android.content.Intent
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat

class MainActivity : ReactActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Configure biometric authentication
        setupBiometricAuthentication()

        // Handle deep links
        handleDeepLink(intent)
    }

    override fun getMainComponentName(): String = "LoanWealth"

    override fun createReactActivityDelegate(): ReactActivityDelegate =
        DefaultReactActivityDelegate(this, mainComponentName, fabricEnabled)

    private fun setupBiometricAuthentication() {
        val executor = ContextCompat.getMainExecutor(this)
        val biometricPrompt = BiometricPrompt(this, executor,
            object : BiometricPrompt.AuthenticationCallback() {
                override fun onAuthenticationSucceeded(
                    result: BiometricPrompt.AuthenticationResult
                ) {
                    super.onAuthenticationSucceeded(result)
                    // Send event to React Native
                    sendEvent("BiometricAuthSuccess", null)
                }

                override fun onAuthenticationFailed() {
                    super.onAuthenticationFailed()
                    sendEvent("BiometricAuthFailed", null)
                }
            })
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        handleDeepLink(intent)
    }

    private fun handleDeepLink(intent: Intent) {
        intent.data?.let { uri ->
            // Send deep link to React Native
            sendEvent("DeepLink", uri.toString())
        }
    }
}
```

---

## Push Notifications

### Push Notification Service

```typescript
// src/services/push/PushNotificationService.ts

import PushNotification from 'react-native-push-notification';
import PushNotificationIOS from '@react-native-community/push-notification-ios';
import messaging from '@react-native-firebase/messaging';
import { Platform } from 'react-native';

class PushNotificationService {
  constructor() {
    this.configure();
    this.createChannels();
  }

  configure() {
    PushNotification.configure({
      onRegister: async (token) => {
        console.log('FCM Token:', token);
        await this.registerToken(token.token);
      },

      onNotification: (notification) => {
        console.log('Notification received:', notification);
        this.handleNotification(notification);

        // Required on iOS
        if (Platform.OS === 'ios') {
          notification.finish(PushNotificationIOS.FetchResult.NoData);
        }
      },

      onAction: (notification) => {
        console.log('Notification action:', notification.action);
        this.handleNotificationAction(notification);
      },

      permissions: {
        alert: true,
        badge: true,
        sound: true,
      },

      popInitialNotification: true,
      requestPermissions: true,
    });
  }

  createChannels() {
    PushNotification.createChannel(
      {
        channelId: 'loans',
        channelName: 'Loan Updates',
        channelDescription: 'Updates about your loans and applications',
        importance: 4,
        vibrate: true,
      },
      (created) => console.log(`Channel 'loans' created: ${created}`)
    );

    PushNotification.createChannel(
      {
        channelId: 'payments',
        channelName: 'Payment Reminders',
        channelDescription: 'Payment due dates and confirmations',
        importance: 5,
        vibrate: true,
      },
      (created) => console.log(`Channel 'payments' created: ${created}`)
    );

    PushNotification.createChannel(
      {
        channelId: 'marketplace',
        channelName: 'Investment Opportunities',
        channelDescription: 'New loans matching your criteria',
        importance: 3,
        vibrate: false,
      },
      (created) => console.log(`Channel 'marketplace' created: ${created}`)
    );
  }

  async registerToken(token: string) {
    try {
      await api.post('/notifications/register', {
        token,
        platform: Platform.OS,
        device_id: getUniqueId(),
      });
    } catch (error) {
      console.error('Failed to register FCM token:', error);
    }
  }

  handleNotification(notification: any) {
    const { data } = notification;

    switch (data.type) {
      case 'loan_funded':
        this.navigateToLoan(data.loan_id);
        break;
      case 'payment_due':
        this.navigateToPayment(data.payment_id);
        break;
      case 'new_bid':
        this.navigateToBids(data.loan_id);
        break;
      default:
        this.navigateToNotifications();
    }
  }

  handleNotificationAction(notification: any) {
    const { action, data } = notification;

    switch (action) {
      case 'VIEW':
        this.navigateToDetail(data);
        break;
      case 'PAY_NOW':
        this.navigateToPayment(data.payment_id);
        break;
      case 'ACCEPT_BID':
        this.acceptBid(data.bid_id);
        break;
    }
  }

  scheduleLocalNotification({
    title,
    message,
    date,
    data,
    channelId = 'default',
  }: LocalNotificationParams) {
    PushNotification.localNotificationSchedule({
      channelId,
      title,
      message,
      date,
      data,
      allowWhileIdle: true,
      repeatType: 'day',
      userInfo: data,
    });
  }

  cancelAllLocalNotifications() {
    PushNotification.cancelAllLocalNotifications();
  }

  setBadgeCount(count: number) {
    PushNotification.setApplicationIconBadgeNumber(count);
  }

  async requestPermissions(): Promise<boolean> {
    if (Platform.OS === 'ios') {
      const authStatus = await messaging().requestPermission();
      return authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
             authStatus === messaging.AuthorizationStatus.PROVISIONAL;
    }
    return true;
  }
}

export default new PushNotificationService();
```

---

## Offline Capabilities

### Offline Manager

```typescript
// src/services/offline/OfflineManager.ts

import NetInfo, { NetInfoState } from '@react-native-community/netinfo';
import { MMKV } from 'react-native-mmkv';
import BackgroundFetch from 'react-native-background-fetch';

class OfflineManager {
  private storage = new MMKV({ id: 'offline-storage' });
  private queue: OfflineRequest[] = [];
  private isOnline = true;

  constructor() {
    this.initializeNetworkListener();
    this.configureBackgroundSync();
  }

  private initializeNetworkListener() {
    NetInfo.addEventListener((state: NetInfoState) => {
      const wasOffline = !this.isOnline;
      this.isOnline = state.isConnected ?? false;

      if (wasOffline && this.isOnline) {
        this.syncOfflineData();
      }

      store.dispatch(setNetworkStatus({
        isOnline: this.isOnline,
        type: state.type,
      }));
    });
  }

  private configureBackgroundSync() {
    BackgroundFetch.configure(
      {
        minimumFetchInterval: 15, // 15 minutes
        forceAlarmManager: false,
        stopOnTerminate: false,
        startOnBoot: true,
        enableHeadless: true,
      },
      async (taskId) => {
        console.log('[BackgroundFetch] taskId:', taskId);
        await this.syncOfflineData();
        BackgroundFetch.finish(taskId);
      },
      (error) => {
        console.log('[BackgroundFetch] failed to start:', error);
      }
    );
  }

  async queueRequest(request: OfflineRequest) {
    const queue = this.getQueue();
    queue.push({
      ...request,
      id: generateId(),
      timestamp: Date.now(),
      retryCount: 0,
    });
    this.saveQueue(queue);

    if (this.isOnline) {
      await this.processQueue();
    }
  }

  private async syncOfflineData() {
    try {
      // Sync queued requests
      await this.processQueue();

      // Sync cached data
      await this.syncCachedData();

      // Update local database
      await this.updateLocalData();

      showToast('Data synchronized successfully');
    } catch (error) {
      console.error('Sync failed:', error);
    }
  }

  private async processQueue() {
    const queue = this.getQueue();
    const failedRequests: OfflineRequest[] = [];

    for (const request of queue) {
      try {
        await this.executeRequest(request);
      } catch (error) {
        if (request.retryCount < 3) {
          failedRequests.push({
            ...request,
            retryCount: request.retryCount + 1,
          });
        } else {
          // Log failed request for manual intervention
          this.logFailedRequest(request, error);
        }
      }
    }

    this.saveQueue(failedRequests);
  }

  private async executeRequest(request: OfflineRequest) {
    const { method, url, data, headers } = request;

    const response = await api.request({
      method,
      url,
      data,
      headers: {
        ...headers,
        'X-Offline-Request': 'true',
        'X-Request-Timestamp': request.timestamp.toString(),
      },
    });

    // Handle conflict resolution
    if (response.status === 409) {
      return this.resolveConflict(request, response.data);
    }

    return response.data;
  }

  private async resolveConflict(request: OfflineRequest, serverData: any) {
    // Implement conflict resolution strategy
    const resolution = await this.getConflictResolution(request, serverData);

    if (resolution.action === 'retry') {
      return this.executeRequest({
        ...request,
        data: resolution.data,
      });
    } else if (resolution.action === 'discard') {
      // Discard local changes
      return serverData;
    } else {
      // Manual resolution required
      store.dispatch(addConflict({
        request,
        serverData,
      }));
    }
  }

  cacheResponse(key: string, data: any, ttl: number = 3600000) {
    this.storage.set(key, JSON.stringify({
      data,
      timestamp: Date.now(),
      ttl,
    }));
  }

  getCachedResponse<T>(key: string): T | null {
    const cached = this.storage.getString(key);
    if (!cached) return null;

    const { data, timestamp, ttl } = JSON.parse(cached);

    if (Date.now() - timestamp > ttl) {
      this.storage.delete(key);
      return null;
    }

    return data;
  }

  private getQueue(): OfflineRequest[] {
    const queueData = this.storage.getString('offline-queue');
    return queueData ? JSON.parse(queueData) : [];
  }

  private saveQueue(queue: OfflineRequest[]) {
    this.storage.set('offline-queue', JSON.stringify(queue));
  }
}

export default new OfflineManager();
```

---

## Performance Optimization

### Performance Monitoring

```typescript
// src/utils/performance.ts

import { InteractionManager } from 'react-native';
import performance from 'react-native-performance';
import analytics from '@react-native-firebase/analytics';

class PerformanceMonitor {
  private marks: Map<string, number> = new Map();
  private measures: Map<string, number[]> = new Map();

  startTrace(name: string) {
    this.marks.set(name, performance.now());
  }

  endTrace(name: string, metadata?: Record<string, any>) {
    const startTime = this.marks.get(name);
    if (!startTime) return;

    const duration = performance.now() - startTime;
    this.marks.delete(name);

    // Store measure
    if (!this.measures.has(name)) {
      this.measures.set(name, []);
    }
    this.measures.get(name)?.push(duration);

    // Log to analytics
    analytics().logEvent('performance_trace', {
      name,
      duration,
      ...metadata,
    });

    // Log slow operations
    if (duration > 1000) {
      console.warn(`Slow operation detected: ${name} took ${duration}ms`);
      Sentry.captureMessage(`Slow operation: ${name}`, {
        level: 'warning',
        extra: { duration, metadata },
      });
    }
  }

  measureRender(componentName: string, callback: () => void) {
    const startTime = performance.now();

    InteractionManager.runAfterInteractions(() => {
      const renderTime = performance.now() - startTime;

      analytics().logEvent('component_render', {
        component: componentName,
        duration: renderTime,
      });

      callback();
    });
  }

  getAverageTime(name: string): number {
    const measures = this.measures.get(name);
    if (!measures || measures.length === 0) return 0;

    return measures.reduce((a, b) => a + b, 0) / measures.length;
  }

  clearMeasures() {
    this.measures.clear();
  }
}

export default new PerformanceMonitor();
```

### Optimized List Component

```typescript
// src/components/optimized/OptimizedList.tsx

import React, { memo, useCallback, useMemo } from 'react';
import {
  FlatList,
  FlatListProps,
  ViewToken,
  View,
} from 'react-native';
import { FlashList } from '@shopify/flash-list';

interface OptimizedListProps<T> extends Omit<FlatListProps<T>, 'renderItem'> {
  data: T[];
  renderItem: (item: T, index: number) => React.ReactElement;
  estimatedItemSize?: number;
  useFlashList?: boolean;
}

function OptimizedListComponent<T>({
  data,
  renderItem,
  estimatedItemSize = 100,
  useFlashList = true,
  ...props
}: OptimizedListProps<T>) {
  const keyExtractor = useCallback(
    (item: T, index: number) => {
      if (typeof item === 'object' && item !== null && 'id' in item) {
        return String(item.id);
      }
      return String(index);
    },
    []
  );

  const getItemLayout = useCallback(
    (_: any, index: number) => ({
      length: estimatedItemSize,
      offset: estimatedItemSize * index,
      index,
    }),
    [estimatedItemSize]
  );

  const viewabilityConfig = useMemo(
    () => ({
      itemVisiblePercentThreshold: 50,
      minimumViewTime: 350,
    }),
    []
  );

  const onViewableItemsChanged = useCallback(
    ({ viewableItems }: { viewableItems: ViewToken[] }) => {
      // Track visible items for analytics
      const visibleIds = viewableItems.map(item => item.key);
      analytics.track('list_items_viewed', { items: visibleIds });
    },
    []
  );

  if (useFlashList && data.length > 50) {
    return (
      <FlashList
        data={data}
        renderItem={({ item, index }) => renderItem(item, index)}
        estimatedItemSize={estimatedItemSize}
        keyExtractor={keyExtractor}
        {...props}
      />
    );
  }

  return (
    <FlatList
      data={data}
      renderItem={({ item, index }) => renderItem(item, index)}
      keyExtractor={keyExtractor}
      getItemLayout={getItemLayout}
      viewabilityConfig={viewabilityConfig}
      onViewableItemsChanged={onViewableItemsChanged}
      removeClippedSubviews={true}
      maxToRenderPerBatch={10}
      initialNumToRender={10}
      windowSize={21}
      updateCellsBatchingPeriod={50}
      {...props}
    />
  );
}

export const OptimizedList = memo(OptimizedListComponent) as typeof OptimizedListComponent;
```

---

## Testing Strategy

### Unit Tests

```typescript
// src/services/__tests__/BiometricService.test.ts

import { BiometricService } from '../biometric/BiometricService';
import ReactNativeBiometrics from 'react-native-biometrics';

jest.mock('react-native-biometrics');

describe('BiometricService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('isBiometricAvailable', () => {
    it('should return available when biometrics are supported', async () => {
      (ReactNativeBiometrics.prototype.isSensorAvailable as jest.Mock).mockResolvedValue({
        available: true,
        biometryType: 'FaceID',
      });

      const result = await BiometricService.isBiometricAvailable();

      expect(result).toEqual({
        available: true,
        biometryType: 'Face ID',
      });
    });

    it('should handle errors gracefully', async () => {
      (ReactNativeBiometrics.prototype.isSensorAvailable as jest.Mock).mockRejectedValue(
        new Error('Sensor error')
      );

      const result = await BiometricService.isBiometricAvailable();

      expect(result).toEqual({
        available: false,
        biometryType: null,
      });
    });
  });

  describe('authenticate', () => {
    it('should return true on successful authentication', async () => {
      (ReactNativeBiometrics.prototype.simplePrompt as jest.Mock).mockResolvedValue({
        success: true,
      });

      const result = await BiometricService.authenticate('Test authentication');

      expect(result).toBe(true);
      expect(ReactNativeBiometrics.prototype.simplePrompt).toHaveBeenCalledWith({
        promptMessage: 'Test authentication',
        cancelButtonText: 'Cancel',
        fallbackPromptMessage: 'Use passcode',
      });
    });
  });
});
```

### Integration Tests

```typescript
// e2e/tests/loanApplication.test.ts

describe('Loan Application Flow', () => {
  beforeAll(async () => {
    await device.launchApp();
  });

  beforeEach(async () => {
    await device.reloadReactNative();
  });

  it('should complete loan application successfully', async () => {
    // Login
    await element(by.id('email-input')).typeText('borrower@test.com');
    await element(by.id('password-input')).typeText('password123');
    await element(by.id('login-button')).tap();

    // Navigate to loan application
    await element(by.id('apply-tab')).tap();

    // Fill loan details
    await element(by.id('amount-input')).typeText('10000');
    await element(by.id('term-12')).tap();
    await element(by.id('calculate-button')).tap();

    // Wait for calculation
    await waitFor(element(by.id('rate-display')))
      .toBeVisible()
      .withTimeout(5000);

    // Continue with application
    await element(by.id('purpose-picker')).tap();
    await element(by.text('Debt Consolidation')).tap();
    await element(by.id('description-input')).typeText(
      'Consolidating credit card debt to reduce interest payments'
    );

    // Submit application
    await element(by.id('submit-button')).tap();

    // Verify success
    await expect(element(by.id('success-message'))).toBeVisible();
    await expect(element(by.text('Application Submitted'))).toBeVisible();
  });
});
```

### E2E Test Configuration

```javascript
// .detoxrc.js

module.exports = {
  testRunner: {
    args: {
      $0: 'jest',
      config: 'e2e/config.js',
    },
    jest: {
      setupFilesAfterEnv: ['<rootDir>/e2e/init.js'],
    },
  },
  apps: {
    'ios.debug': {
      type: 'ios.app',
      binaryPath: 'ios/build/Build/Products/Debug-iphonesimulator/LoanWealth.app',
      build: 'xcodebuild -workspace ios/LoanWealth.xcworkspace -scheme LoanWealth -configuration Debug -sdk iphonesimulator -derivedDataPath ios/build',
    },
    'android.debug': {
      type: 'android.apk',
      binaryPath: 'android/app/build/outputs/apk/debug/app-debug.apk',
      build: 'cd android && ./gradlew assembleDebug assembleAndroidTest -DtestBuildType=debug',
    },
  },
  devices: {
    simulator: {
      type: 'ios.simulator',
      device: {
        type: 'iPhone 14 Pro',
      },
    },
    emulator: {
      type: 'android.emulator',
      device: {
        avdName: 'Pixel_5_API_31',
      },
    },
  },
  configurations: {
    'ios.sim.debug': {
      device: 'simulator',
      app: 'ios.debug',
    },
    'android.emu.debug': {
      device: 'emulator',
      app: 'android.debug',
    },
  },
};
```

---

## Deployment & CI/CD

### GitHub Actions Workflow

```yaml
# .github/workflows/mobile-ci.yml

name: Mobile CI/CD

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

env:
  NODE_VERSION: '18.x'
  RUBY_VERSION: '3.0'
  JAVA_VERSION: '11'

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: ${{ env.NODE_VERSION }}
          cache: 'npm'

      - name: Install dependencies
        run: |
          npm ci
          npx pod-install ios

      - name: Lint
        run: npm run lint

      - name: Type check
        run: npm run type-check

      - name: Unit tests
        run: npm test -- --coverage

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage/lcov.info

  build-ios:
    runs-on: macos-latest
    needs: test
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: ${{ env.NODE_VERSION }}

      - name: Setup Ruby
        uses: ruby/setup-ruby@v1
        with:
          ruby-version: ${{ env.RUBY_VERSION }}
          bundler-cache: true

      - name: Install dependencies
        run: |
          npm ci
          cd ios && pod install

      - name: Build iOS
        run: |
          cd ios
          xcodebuild -workspace LoanWealth.xcworkspace \
            -scheme LoanWealth \
            -configuration Release \
            -sdk iphonesimulator \
            -derivedDataPath build

      - name: Run Detox E2E tests
        run: |
          npm run e2e:build:ios
          npm run e2e:test:ios

  build-android:
    runs-on: ubuntu-latest
    needs: test
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: ${{ env.NODE_VERSION }}

      - name: Setup Java
        uses: actions/setup-java@v3
        with:
          java-version: ${{ env.JAVA_VERSION }}
          distribution: 'temurin'

      - name: Install dependencies
        run: npm ci

      - name: Build Android
        run: |
          cd android
          ./gradlew assembleRelease

      - name: Run Detox E2E tests
        run: |
          npm run e2e:build:android
          npm run e2e:test:android

      - name: Upload APK
        uses: actions/upload-artifact@v3
        with:
          name: app-release.apk
          path: android/app/build/outputs/apk/release/app-release.apk

  deploy-staging:
    runs-on: ubuntu-latest
    needs: [build-ios, build-android]
    if: github.ref == 'refs/heads/develop'
    steps:
      - uses: actions/checkout@v3

      - name: Deploy to CodePush (Staging)
        run: |
          npm install -g appcenter-cli
          appcenter login --token ${{ secrets.APPCENTER_TOKEN }}
          appcenter codepush release-react \
            -a LoanWealth/iOS-Staging \
            -d Staging
          appcenter codepush release-react \
            -a LoanWealth/Android-Staging \
            -d Staging

  deploy-production:
    runs-on: ubuntu-latest
    needs: [build-ios, build-android]
    if: github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v3

      - name: Deploy to App Store Connect
        run: |
          cd ios
          fastlane release

      - name: Deploy to Google Play
        run: |
          cd android
          fastlane release
```

### Fastlane Configuration

```ruby
# ios/fastlane/Fastfile

default_platform(:ios)

platform :ios do
  desc "Push a new release build to TestFlight"
  lane :release do
    increment_build_number(xcodeproj: "LoanWealth.xcodeproj")

    build_app(
      workspace: "LoanWealth.xcworkspace",
      scheme: "LoanWealth",
      export_method: "app-store",
      export_options: {
        provisioningProfiles: {
          "com.loanwealth.app" => "LoanWealth Distribution"
        }
      }
    )

    upload_to_testflight(
      skip_waiting_for_build_processing: true
    )

    slack(
      message: "Successfully deployed new iOS version to TestFlight!"
    )
  end
end

# android/fastlane/Fastfile

default_platform(:android)

platform :android do
  desc "Deploy a new version to Google Play"
  lane :release do
    gradle(
      task: "bundle",
      build_type: "Release"
    )

    upload_to_play_store(
      track: "internal",
      release_status: "draft",
      skip_upload_metadata: true,
      skip_upload_images: true,
      skip_upload_screenshots: true
    )

    slack(
      message: "Successfully deployed new Android version to Play Store!"
    )
  end
end
```

---

## App Store Optimization

### App Store Metadata

```json
{
  "ios": {
    "name": "LoanWealth - P2P Lending",
    "subtitle": "Invest & Borrow Smartly",
    "keywords": [
      "p2p lending",
      "peer to peer",
      "loans",
      "investment",
      "finance",
      "borrowing",
      "lending",
      "marketplace"
    ],
    "description": "LoanWealth connects borrowers seeking fair rates with lenders looking for better returns. Our secure platform uses advanced risk assessment to match loans with investors.\n\nFor Borrowers:\n• Competitive rates based on your profile\n• Quick approval process\n• Flexible repayment terms\n• No hidden fees\n\nFor Lenders:\n• Higher returns than traditional savings\n• Diversified investment portfolio\n• Auto-invest features\n• Real-time tracking\n\nFeatures:\n• Bank-level security\n• Biometric authentication\n• Real-time notifications\n• Document scanner\n• Offline support",
    "screenshots": [
      "Dashboard showing loan status",
      "Marketplace with available loans",
      "Loan application process",
      "Portfolio performance charts",
      "Payment schedule view"
    ]
  },
  "android": {
    "title": "LoanWealth: P2P Lending Platform",
    "shortDescription": "Connect borrowers with lenders for better rates",
    "fullDescription": "Similar to iOS description...",
    "category": "FINANCE",
    "contentRating": "Everyone",
    "tags": ["finance", "loans", "investment", "p2p"]
  }
}
```

---

## Monitoring & Analytics

### Analytics Setup

```typescript
// src/services/analytics/Analytics.ts

import analytics from '@react-native-firebase/analytics';
import { Mixpanel } from 'mixpanel-react-native';
import Config from 'react-native-config';

class Analytics {
  private mixpanel: Mixpanel;

  async initialize() {
    // Firebase Analytics is auto-initialized

    // Initialize Mixpanel
    this.mixpanel = new Mixpanel(Config.MIXPANEL_TOKEN);
    await this.mixpanel.init();
  }

  async identify(userId: string, properties?: Record<string, any>) {
    await analytics().setUserId(userId);
    await this.mixpanel.identify(userId);

    if (properties) {
      await analytics().setUserProperties(properties);
      await this.mixpanel.getPeople().set(properties);
    }
  }

  async track(event: string, properties?: Record<string, any>) {
    // Firebase Analytics
    await analytics().logEvent(event, properties);

    // Mixpanel
    await this.mixpanel.track(event, properties);

    // Custom backend analytics
    api.post('/analytics/events', {
      event,
      properties,
      timestamp: new Date().toISOString(),
    }).catch(console.error);
  }

  async screen(name: string, properties?: Record<string, any>) {
    await analytics().logScreenView({
      screen_name: name,
      screen_class: name,
      ...properties,
    });

    await this.track('screen_view', {
      screen_name: name,
      ...properties,
    });
  }
}

export default new Analytics();
```

---

This comprehensive React Native mobile app specification provides everything needed to build a professional P2P lending mobile application that integrates seamlessly with your Laravel backend from Phase 1. The app includes native features, real-time updates, offline support, and platform-specific optimizations for both iOS and Android.