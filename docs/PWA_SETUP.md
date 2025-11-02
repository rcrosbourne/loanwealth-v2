# PWA Implementation Plan - Phase 1
## Complete Guide for P2P Lending Platform

---

## 📋 Implementation Overview

**Duration:** 8 weeks  
**Prerequisites:** Laravel 11 + Inertia + React + TypeScript + Vite setup  
**Goal:** Transform existing web app into installable PWA with offline support and push notifications

---

## Week 1-2: PWA Foundation Setup

### Day 1-2: Install and Configure PWA Dependencies

```bash
# Install required packages
npm install -D vite-plugin-pwa workbox-window
npm install web-push
composer require laravel-notification-channels/webpush

# TypeScript types
npm install -D @types/web-push @vite-pwa/assets-generator
```

### Day 3: Configure Vite for PWA

```typescript
// vite.config.ts
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        VitePWA({
            registerType: 'prompt', // Show installation prompt
            includeAssets: [
                'favicon.ico',
                'robots.txt',
                'apple-touch-icon.png',
                'icons/*.png',
                'fonts/*.woff2'
            ],
            manifest: {
                name: 'P2P Lending Platform',
                short_name: 'P2PLend',
                description: 'Secure peer-to-peer lending with gamification',
                theme_color: '#4F46E5',
                background_color: '#ffffff',
                display: 'standalone',
                scope: '/',
                start_url: '/',
                orientation: 'portrait',
                categories: ['finance', 'business'],
                shortcuts: [
                    {
                        name: "View Loans",
                        url: "/loans",
                        description: "Browse available loans",
                        icons: [{ src: "/icons/loans-96x96.png", sizes: "96x96" }]
                    },
                    {
                        name: "My Portfolio",
                        url: "/portfolio",
                        description: "View your investments",
                        icons: [{ src: "/icons/portfolio-96x96.png", sizes: "96x96" }]
                    }
                ],
                icons: [
                    {
                        src: '/icons/icon-72x72.png',
                        sizes: '72x72',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/icons/icon-96x96.png',
                        sizes: '96x96',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/icons/icon-128x128.png',
                        sizes: '128x128',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/icons/icon-144x144.png',
                        sizes: '144x144',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/icons/icon-152x152.png',
                        sizes: '152x152',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/icons/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/icons/icon-384x384.png',
                        sizes: '384x384',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/icons/maskable-icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable'
                    }
                ],
                screenshots: [
                    {
                        src: '/screenshots/dashboard-wide.png',
                        sizes: '1280x720',
                        type: 'image/png',
                        form_factor: 'wide'
                    },
                    {
                        src: '/screenshots/dashboard-narrow.png',
                        sizes: '720x1280',
                        type: 'image/png',
                        form_factor: 'narrow'
                    }
                ]
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,jpg,svg,woff2}'],
                cleanupOutdatedCaches: true,
                sourcemap: true,
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/api\./,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-cache',
                            networkTimeoutSeconds: 10,
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 // 24 hours
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    {
                        urlPattern: /\.(png|jpg|jpeg|svg|gif|webp)$/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'image-cache',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
                            }
                        }
                    },
                    {
                        urlPattern: /\.(woff|woff2|ttf|eot)$/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'font-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365 // 1 year
                            }
                        }
                    }
                ]
            },
            devOptions: {
                enabled: true,
                suppressWarnings: true,
                navigateFallback: '/',
                type: 'module'
            }
        })
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
```

### Day 4: Generate PWA Assets

```bash
# Create icon generation config
cat > pwa-assets.config.ts << 'EOF'
import { defineConfig } from '@vite-pwa/assets-generator/config'

export default defineConfig({
  preset: {
    transparent: {
      sizes: [64, 192, 512],
      favicons: [[64, 'favicon.ico']]
    },
    maskable: {
      sizes: [512],
      resizeOptions: {
        background: '#4F46E5'
      }
    },
    apple: {
      sizes: [180],
      resizeOptions: {
        background: '#4F46E5'
      }
    }
  },
  images: ['public/logo.svg']
})
EOF

# Generate icons
npx @vite-pwa/assets-generator --config pwa-assets.config.ts
```

### Day 5: Create Base Service Worker

```typescript
// resources/js/service-worker.ts
/// <reference lib="webworker" />
import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching';
import { clientsClaim } from 'workbox-core';
import { registerRoute, NavigationRoute } from 'workbox-routing';
import { NetworkFirst, CacheFirst, StaleWhileRevalidate } from 'workbox-strategies';

declare let self: ServiceWorkerGlobalScope;

// Self-activation
self.skipWaiting();
clientsClaim();

// Precache all static assets
cleanupOutdatedCaches();
precacheAndRoute(self.__WB_MANIFEST);

// Custom cache strategies for P2P platform
const CACHE_NAMES = {
    loans: 'loans-cache-v1',
    user: 'user-cache-v1',
    static: 'static-cache-v1',
    gamification: 'gamification-cache-v1'
};

// Cache user profile and dashboard data
registerRoute(
    ({ url }) => url.pathname.startsWith('/api/user'),
    new NetworkFirst({
        cacheName: CACHE_NAMES.user,
        networkTimeoutSeconds: 5,
        plugins: [
            {
                cacheWillUpdate: async ({ response }) => {
                    if (response && response.status === 200) {
                        return response;
                    }
                    return null;
                }
            }
        ]
    })
);

// Cache loan listings (update every 5 minutes)
registerRoute(
    ({ url }) => url.pathname.startsWith('/api/loans'),
    new StaleWhileRevalidate({
        cacheName: CACHE_NAMES.loans,
        plugins: [
            {
                cachedResponseWillBeUsed: async ({ cachedResponse, state }) => {
                    if (cachedResponse) {
                        const cachedDate = cachedResponse.headers.get('date');
                        if (cachedDate) {
                            const cachedTime = new Date(cachedDate).getTime();
                            const now = Date.now();
                            const fiveMinutes = 5 * 60 * 1000;
                            
                            if (now - cachedTime > fiveMinutes) {
                                return null; // Force network request
                            }
                        }
                    }
                    return cachedResponse;
                }
            }
        ]
    })
);

// Handle offline fallback
const FALLBACK_HTML = `
<!DOCTYPE html>
<html>
<head>
    <title>P2P Lending - Offline</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .offline-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
        }
        h1 { color: #4F46E5; }
        p { color: #6B7280; }
        button {
            background: #4F46E5;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="offline-card">
        <h1>You're Offline</h1>
        <p>It looks like you've lost your internet connection. 
           Some features may be unavailable.</p>
        <button onclick="location.reload()">Try Again</button>
    </div>
</body>
</html>
`;

// Offline fallback for navigation
registerRoute(
    new NavigationRoute(async () => {
        try {
            const response = await fetch('/');
            return response;
        } catch {
            return new Response(FALLBACK_HTML, {
                headers: { 'Content-Type': 'text/html' }
            });
        }
    })
);
```

---

## Week 3-4: Installation Flow & App Shell

### Day 1-2: PWA Installation Manager Component

```typescript
// resources/js/Components/PWA/InstallPrompt.tsx
import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, Download, Smartphone, CheckCircle } from 'lucide-react';

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

export default function InstallPrompt() {
    const [deferredPrompt, setDeferredPrompt] = useState<BeforeInstallPromptEvent | null>(null);
    const [showPrompt, setShowPrompt] = useState(false);
    const [isInstalled, setIsInstalled] = useState(false);
    const [platform, setPlatform] = useState<'ios' | 'android' | 'desktop'>('desktop');

    useEffect(() => {
        // Check if already installed
        if (window.matchMedia('(display-mode: standalone)').matches) {
            setIsInstalled(true);
            return;
        }

        // Detect platform
        const userAgent = navigator.userAgent.toLowerCase();
        if (/iphone|ipad|ipod/.test(userAgent)) {
            setPlatform('ios');
        } else if (/android/.test(userAgent)) {
            setPlatform('android');
        }

        // Listen for install prompt
        const handleBeforeInstallPrompt = (e: Event) => {
            e.preventDefault();
            setDeferredPrompt(e as BeforeInstallPromptEvent);
            
            // Show prompt after user has engaged with the site
            const engagementTime = sessionStorage.getItem('engagementTime');
            if (!engagementTime) {
                sessionStorage.setItem('engagementTime', Date.now().toString());
                setTimeout(() => setShowPrompt(true), 30000); // Show after 30 seconds
            } else {
                const elapsed = Date.now() - parseInt(engagementTime);
                if (elapsed > 30000) {
                    setShowPrompt(true);
                }
            }
        };

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

        // Listen for successful install
        window.addEventListener('appinstalled', () => {
            setIsInstalled(true);
            setShowPrompt(false);
            trackInstallation('pwa_installed');
        });

        return () => {
            window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        };
    }, []);

    const handleInstallClick = async () => {
        if (!deferredPrompt) {
            // Show manual installation instructions
            showManualInstructions();
            return;
        }

        await deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        
        if (outcome === 'accepted') {
            trackInstallation('pwa_install_accepted');
        } else {
            trackInstallation('pwa_install_dismissed');
        }
        
        setDeferredPrompt(null);
        setShowPrompt(false);
    };

    const showManualInstructions = () => {
        // Show platform-specific instructions
        if (platform === 'ios') {
            showIOSInstructions();
        } else {
            showAndroidInstructions();
        }
    };

    const showIOSInstructions = () => {
        // Implementation for iOS modal
        return (
            <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div className="bg-white rounded-2xl max-w-md w-full p-6">
                    <h3 className="text-xl font-bold mb-4">Install on iOS</h3>
                    <ol className="space-y-3">
                        <li className="flex items-start">
                            <span className="text-indigo-600 font-bold mr-2">1.</span>
                            <span>Tap the Share button <span className="inline-block">↑</span> at the bottom of Safari</span>
                        </li>
                        <li className="flex items-start">
                            <span className="text-indigo-600 font-bold mr-2">2.</span>
                            <span>Scroll down and tap "Add to Home Screen"</span>
                        </li>
                        <li className="flex items-start">
                            <span className="text-indigo-600 font-bold mr-2">3.</span>
                            <span>Tap "Add" to install the app</span>
                        </li>
                    </ol>
                    <button 
                        onClick={() => setShowPrompt(false)}
                        className="mt-6 w-full bg-indigo-600 text-white py-3 rounded-xl"
                    >
                        Got it
                    </button>
                </div>
            </div>
        );
    };

    const trackInstallation = (event: string) => {
        // Track installation events
        if (window.gtag) {
            window.gtag('event', event, {
                event_category: 'PWA',
                event_label: platform
            });
        }
    };

    if (isInstalled) {
        return null; // Already installed
    }

    return (
        <>
            {/* Mini install banner for engaged users */}
            <AnimatePresence>
                {showPrompt && !isInstalled && (
                    <motion.div
                        initial={{ y: 100, opacity: 0 }}
                        animate={{ y: 0, opacity: 1 }}
                        exit={{ y: 100, opacity: 0 }}
                        className="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-white rounded-2xl shadow-xl p-4 z-40"
                    >
                        <button
                            onClick={() => setShowPrompt(false)}
                            className="absolute top-2 right-2 text-gray-400 hover:text-gray-600"
                        >
                            <X size={20} />
                        </button>
                        
                        <div className="flex items-start space-x-4">
                            <div className="flex-shrink-0">
                                <div className="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                                    <Smartphone className="w-6 h-6 text-indigo-600" />
                                </div>
                            </div>
                            <div className="flex-1">
                                <h3 className="font-semibold text-gray-900">Install P2P Lending</h3>
                                <p className="text-sm text-gray-600 mt-1">
                                    Get quick access, offline support, and notifications
                                </p>
                                <div className="mt-3 flex space-x-2">
                                    <button
                                        onClick={handleInstallClick}
                                        className="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700"
                                    >
                                        Install App
                                    </button>
                                    <button
                                        onClick={() => {
                                            setShowPrompt(false);
                                            sessionStorage.setItem('pwa_dismissed', 'true');
                                        }}
                                        className="px-4 py-2 text-gray-600 text-sm"
                                    >
                                        Not now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>

            {/* Platform-specific instructions modal */}
            {platform === 'ios' && showPrompt && !deferredPrompt && (
                showIOSInstructions()
            )}
        </>
    );
}
```

### Day 3: App Shell Architecture

```typescript
// resources/js/Layouts/AppShell.tsx
import { useEffect, useState } from 'react';
import { registerSW } from 'virtual:pwa-register';
import NetworkStatus from '@/Components/PWA/NetworkStatus';
import UpdatePrompt from '@/Components/PWA/UpdatePrompt';
import InstallPrompt from '@/Components/PWA/InstallPrompt';

interface AppShellProps {
    children: React.ReactNode;
}

export default function AppShell({ children }: AppShellProps) {
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const [needsUpdate, setNeedsUpdate] = useState(false);
    const [updateSW, setUpdateSW] = useState<(() => Promise<void>) | null>(null);

    useEffect(() => {
        // Register service worker with update handling
        const sw = registerSW({
            onNeedRefresh() {
                setNeedsUpdate(true);
            },
            onOfflineReady() {
                console.log('App ready to work offline');
            },
            onRegistered(registration) {
                // Check for updates every hour
                setInterval(() => {
                    registration?.update();
                }, 60 * 60 * 1000);
            },
        });

        setUpdateSW(() => sw);

        // Network status monitoring
        const handleOnline = () => setIsOnline(true);
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    return (
        <>
            {/* Network status indicator */}
            <NetworkStatus isOnline={isOnline} />
            
            {/* App update prompt */}
            {needsUpdate && updateSW && (
                <UpdatePrompt 
                    onUpdate={async () => {
                        await updateSW();
                        setNeedsUpdate(false);
                    }}
                    onDismiss={() => setNeedsUpdate(false)}
                />
            )}
            
            {/* Install prompt */}
            <InstallPrompt />
            
            {/* Main app content */}
            <div className={!isOnline ? 'opacity-90' : ''}>
                {children}
            </div>
        </>
    );
}
```

### Day 4-5: Offline Support Components

```typescript
// resources/js/Components/PWA/OfflineQueue.ts
interface QueuedRequest {
    id: string;
    url: string;
    method: string;
    body?: any;
    timestamp: number;
    retries: number;
}

class OfflineQueue {
    private queue: QueuedRequest[] = [];
    private processing = false;
    private readonly MAX_RETRIES = 3;
    private readonly STORAGE_KEY = 'offline_queue';

    constructor() {
        this.loadQueue();
        this.setupEventListeners();
    }

    private loadQueue() {
        const stored = localStorage.getItem(this.STORAGE_KEY);
        if (stored) {
            this.queue = JSON.parse(stored);
        }
    }

    private saveQueue() {
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.queue));
    }

    private setupEventListeners() {
        window.addEventListener('online', () => {
            this.processQueue();
        });

        // Process queue every 30 seconds when online
        setInterval(() => {
            if (navigator.onLine && this.queue.length > 0) {
                this.processQueue();
            }
        }, 30000);
    }

    public async addToQueue(request: Omit<QueuedRequest, 'id' | 'timestamp' | 'retries'>) {
        const queuedRequest: QueuedRequest = {
            ...request,
            id: crypto.randomUUID(),
            timestamp: Date.now(),
            retries: 0
        };

        this.queue.push(queuedRequest);
        this.saveQueue();

        // Show notification
        this.showQueuedNotification(request.url);

        // Try to process immediately if online
        if (navigator.onLine) {
            this.processQueue();
        }
    }

    private async processQueue() {
        if (this.processing || this.queue.length === 0) return;
        
        this.processing = true;
        const failedRequests: QueuedRequest[] = [];

        for (const request of this.queue) {
            try {
                const response = await fetch(request.url, {
                    method: request.method,
                    body: request.body ? JSON.stringify(request.body) : undefined,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Offline-Request': 'true',
                        'X-Request-Id': request.id,
                        'X-Original-Timestamp': request.timestamp.toString()
                    }
                });

                if (response.ok) {
                    this.showSuccessNotification(request.url);
                } else if (request.retries < this.MAX_RETRIES) {
                    request.retries++;
                    failedRequests.push(request);
                }
            } catch (error) {
                if (request.retries < this.MAX_RETRIES) {
                    request.retries++;
                    failedRequests.push(request);
                }
            }
        }

        this.queue = failedRequests;
        this.saveQueue();
        this.processing = false;
    }

    private showQueuedNotification(url: string) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Action Queued', {
                body: 'Your action will be processed when connection is restored',
                icon: '/icons/icon-192x192.png',
                badge: '/icons/badge-72x72.png'
            });
        }
    }

    private showSuccessNotification(url: string) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Action Completed', {
                body: 'Your offline action has been successfully processed',
                icon: '/icons/icon-192x192.png',
                badge: '/icons/badge-72x72.png'
            });
        }
    }
}

export default new OfflineQueue();
```

---

## Week 5-6: Push Notifications

### Day 1-2: Backend Push Notification Setup

```php
// database/migrations/2024_01_01_create_push_subscriptions_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('endpoint')->unique();
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('device_info')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
```

```php
// app/Services/PushNotificationService.php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private WebPush $webPush;
    
    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];
        
        $this->webPush = new WebPush($auth);
    }
    
    /**
     * Send push notification to user
     */
    public function sendToUser(User $user, array $payload): void
    {
        $subscriptions = $user->pushSubscriptions()
            ->where('is_active', true)
            ->get();
        
        foreach ($subscriptions as $subscription) {
            $this->send($subscription, $payload);
        }
    }
    
    /**
     * Send push notification to subscription
     */
    public function send(PushSubscription $pushSubscription, array $payload): void
    {
        try {
            $subscription = Subscription::create([
                'endpoint' => $pushSubscription->endpoint,
                'publicKey' => $pushSubscription->public_key,
                'authToken' => $pushSubscription->auth_token,
                'contentEncoding' => $pushSubscription->content_encoding ?? 'aesgcm',
            ]);
            
            $report = $this->webPush->sendOneNotification(
                $subscription,
                json_encode($this->formatPayload($payload))
            );
            
            if ($report->isSuccess()) {
                $pushSubscription->update(['last_used_at' => now()]);
            } else {
                // Handle failed subscription
                if ($report->getResponse()->getStatusCode() === 410) {
                    // Subscription expired
                    $pushSubscription->update(['is_active' => false]);
                }
                
                Log::error('Push notification failed', [
                    'endpoint' => $pushSubscription->endpoint,
                    'reason' => $report->getReason(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Push notification error', [
                'error' => $e->getMessage(),
                'subscription_id' => $pushSubscription->id,
            ]);
        }
    }
    
    /**
     * Format notification payload
     */
    private function formatPayload(array $data): array
    {
        return [
            'title' => $data['title'] ?? 'P2P Lending Platform',
            'body' => $data['body'] ?? '',
            'icon' => $data['icon'] ?? '/icons/icon-192x192.png',
            'badge' => $data['badge'] ?? '/icons/badge-72x72.png',
            'url' => $data['url'] ?? '/',
            'tag' => $data['tag'] ?? 'general',
            'requireInteraction' => $data['require_interaction'] ?? false,
            'data' => $data['custom_data'] ?? [],
            'actions' => $data['actions'] ?? [],
            'vibrate' => [200, 100, 200],
            'timestamp' => time(),
        ];
    }
    
    /**
     * Send batch notifications
     */
    public function sendBatch(array $notifications): void
    {
        foreach ($notifications as $notification) {
            $this->webPush->queueNotification(
                $notification['subscription'],
                json_encode($notification['payload'])
            );
        }
        
        // Send all queued notifications
        foreach ($this->webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            
            if (!$report->isSuccess()) {
                Log::error('Batch notification failed', [
                    'endpoint' => $endpoint,
                    'reason' => $report->getReason(),
                ]);
            }
        }
    }
}
```

### Day 3: Frontend Push Notification Manager

```typescript
// resources/js/hooks/usePushNotifications.ts
import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';

interface PushNotificationState {
    permission: NotificationPermission;
    isSubscribed: boolean;
    isSupported: boolean;
    error: string | null;
}

export function usePushNotifications() {
    const [state, setState] = useState<PushNotificationState>({
        permission: 'default',
        isSubscribed: false,
        isSupported: false,
        error: null
    });

    useEffect(() => {
        const checkSupport = () => {
            const isSupported = 'Notification' in window && 
                              'serviceWorker' in navigator && 
                              'PushManager' in window;
            
            setState(prev => ({
                ...prev,
                isSupported,
                permission: isSupported ? Notification.permission : 'default'
            }));

            if (isSupported) {
                checkSubscription();
            }
        };

        checkSupport();
    }, []);

    const checkSubscription = async () => {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            
            setState(prev => ({
                ...prev,
                isSubscribed: !!subscription
            }));
        } catch (error) {
            console.error('Error checking subscription:', error);
        }
    };

    const subscribe = async () => {
        if (!state.isSupported) {
            setState(prev => ({
                ...prev,
                error: 'Push notifications not supported'
            }));
            return false;
        }

        try {
            // Request permission
            const permission = await Notification.requestPermission();
            
            setState(prev => ({
                ...prev,
                permission
            }));

            if (permission !== 'granted') {
                setState(prev => ({
                    ...prev,
                    error: 'Notification permission denied'
                }));
                return false;
            }

            // Get service worker registration
            const registration = await navigator.serviceWorker.ready;

            // Subscribe to push notifications
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(
                    import.meta.env.VITE_VAPID_PUBLIC_KEY
                )
            });

            // Send subscription to server
            await axios.post('/api/push/subscribe', {
                subscription: JSON.stringify(subscription),
                device_info: {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    language: navigator.language
                }
            });

            setState(prev => ({
                ...prev,
                isSubscribed: true,
                error: null
            }));

            return true;
        } catch (error) {
            console.error('Subscription error:', error);
            setState(prev => ({
                ...prev,
                error: 'Failed to subscribe to notifications',
                isSubscribed: false
            }));
            return false;
        }
    };

    const unsubscribe = async () => {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await subscription.unsubscribe();
                
                // Notify server
                await axios.post('/api/push/unsubscribe', {
                    endpoint: subscription.endpoint
                });

                setState(prev => ({
                    ...prev,
                    isSubscribed: false
                }));
            }
        } catch (error) {
            console.error('Unsubscribe error:', error);
            setState(prev => ({
                ...prev,
                error: 'Failed to unsubscribe'
            }));
        }
    };

    const sendTestNotification = async () => {
        try {
            await axios.post('/api/push/test');
            return true;
        } catch (error) {
            console.error('Test notification error:', error);
            return false;
        }
    };

    return {
        ...state,
        subscribe,
        unsubscribe,
        sendTestNotification,
        checkSubscription
    };
}

// Helper function to convert VAPID key
function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
```

---

## Week 7: Performance Optimization

### Day 1-2: Implement Performance Monitoring

```typescript
// resources/js/utils/performanceMonitor.ts
class PerformanceMonitor {
    private metrics: Map<string, number> = new Map();
    
    constructor() {
        this.initializeMetrics();
    }
    
    private initializeMetrics() {
        // Web Vitals monitoring
        if ('PerformanceObserver' in window) {
            // Largest Contentful Paint (LCP)
            new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                this.metrics.set('lcp', lastEntry.renderTime || lastEntry.loadTime);
                this.reportMetric('LCP', lastEntry.renderTime || lastEntry.loadTime);
            }).observe({ type: 'largest-contentful-paint', buffered: true });
            
            // First Input Delay (FID)
            new PerformanceObserver((list) => {
                const entries = list.getEntries();
                entries.forEach((entry: any) => {
                    this.metrics.set('fid', entry.processingStart - entry.startTime);
                    this.reportMetric('FID', entry.processingStart - entry.startTime);
                });
            }).observe({ type: 'first-input', buffered: true });
            
            // Cumulative Layout Shift (CLS)
            let clsValue = 0;
            new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (!(entry as any).hadRecentInput) {
                        clsValue += (entry as any).value;
                    }
                }
                this.metrics.set('cls', clsValue);
                this.reportMetric('CLS', clsValue);
            }).observe({ type: 'layout-shift', buffered: true });
        }
    }
    
    public measureApiCall(endpoint: string, startTime: number) {
        const duration = performance.now() - startTime;
        this.reportMetric('API Call', duration, { endpoint });
    }
    
    public measureRouteChange(from: string, to: string) {
        const navigationEntry = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;
        if (navigationEntry) {
            const loadTime = navigationEntry.loadEventEnd - navigationEntry.fetchStart;
            this.reportMetric('Route Change', loadTime, { from, to });
        }
    }
    
    private reportMetric(name: string, value: number, tags?: Record<string, string>) {
        // Send to analytics
        if (window.gtag) {
            window.gtag('event', 'performance', {
                event_category: 'Web Vitals',
                event_label: name,
                value: Math.round(value),
                ...tags
            });
        }
        
        // Log to console in dev
        if (import.meta.env.DEV) {
            console.log(`[Performance] ${name}: ${value}ms`, tags);
        }
    }
    
    public getMetrics() {
        return Object.fromEntries(this.metrics);
    }
}

export default new PerformanceMonitor();
```

### Day 3: Optimize Bundle Size

```typescript
// vite.config.ts - Add build optimizations
export default defineConfig({
    // ... existing config
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['react', 'react-dom'],
                    'charts': ['recharts'],
                    'animations': ['framer-motion'],
                    'utils': ['date-fns', 'clsx']
                }
            }
        },
        chunkSizeWarningLimit: 1000,
        sourcemap: false,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true
            }
        }
    }
});
```

---

## Week 8: Testing & Launch Preparation

### Day 1-2: PWA Testing Suite

```typescript
// tests/e2e/pwa.test.ts
import { test, expect } from '@playwright/test';

test.describe('PWA Installation', () => {
    test('should show install prompt after engagement', async ({ page }) => {
        await page.goto('/');
        
        // Wait for engagement time
        await page.waitForTimeout(30000);
        
        // Check if install prompt appears
        const installPrompt = await page.locator('[data-testid="install-prompt"]');
        await expect(installPrompt).toBeVisible();
    });
    
    test('should work offline', async ({ page, context }) => {
        await page.goto('/');
        
        // Go offline
        await context.setOffline(true);
        
        // Navigate to cached page
        await page.goto('/dashboard');
        
        // Should show offline indicator
        const offlineIndicator = await page.locator('[data-testid="offline-indicator"]');
        await expect(offlineIndicator).toBeVisible();
    });
    
    test('should queue actions when offline', async ({ page, context }) => {
        await page.goto('/loans');
        
        // Go offline
        await context.setOffline(true);
        
        // Try to submit a form
        await page.click('[data-testid="invest-button"]');
        
        // Should show queued message
        const queuedMessage = await page.locator('[data-testid="action-queued"]');
        await expect(queuedMessage).toBeVisible();
        
        // Go back online
        await context.setOffline(false);
        
        // Should process queued action
        await page.waitForSelector('[data-testid="action-completed"]');
    });
});

test.describe('Push Notifications', () => {
    test('should request permission', async ({ page, context }) => {
        // Grant permission
        await context.grantPermissions(['notifications']);
        
        await page.goto('/settings/notifications');
        await page.click('[data-testid="enable-notifications"]');
        
        // Should show subscribed state
        const subscribedState = await page.locator('[data-testid="notifications-enabled"]');
        await expect(subscribedState).toBeVisible();
    });
});
```

### Day 3: Lighthouse CI Setup

```yaml
# .github/workflows/lighthouse.yml
name: Lighthouse CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  lighthouse:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: 18
      
      - name: Install dependencies
        run: npm ci
      
      - name: Build application
        run: npm run build
      
      - name: Run Lighthouse CI
        run: |
          npm install -g @lhci/cli
          lhci autorun --config=lighthouserc.js
        env:
          LHCI_GITHUB_APP_TOKEN: ${{ secrets.LHCI_GITHUB_APP_TOKEN }}
```

```javascript
// lighthouserc.js
module.exports = {
    ci: {
        collect: {
            url: ['http://localhost:3000/'],
            numberOfRuns: 3,
            settings: {
                preset: 'desktop'
            }
        },
        assert: {
            assertions: {
                'categories:performance': ['error', { minScore: 0.9 }],
                'categories:accessibility': ['error', { minScore: 0.9 }],
                'categories:best-practices': ['error', { minScore: 0.9 }],
                'categories:seo': ['error', { minScore: 0.9 }],
                'categories:pwa': ['error', { minScore: 0.9 }],
                'first-contentful-paint': ['error', { maxNumericValue: 2000 }],
                'largest-contentful-paint': ['error', { maxNumericValue: 3000 }],
                'cumulative-layout-shift': ['error', { maxNumericValue: 0.1 }],
                'total-blocking-time': ['error', { maxNumericValue: 300 }]
            }
        },
        upload: {
            target: 'temporary-public-storage'
        }
    }
};
```

### Day 4-5: Pre-Launch Checklist

```markdown
## PWA Launch Checklist

### ✅ Technical Requirements
- [ ] Service Worker registered and caching properly
- [ ] Manifest file with all required properties
- [ ] HTTPS enabled on production
- [ ] Icons in all required sizes (192x192, 512x512 minimum)
- [ ] Splash screens for iOS
- [ ] Offline page implemented
- [ ] App shell loads under 3 seconds on 3G

### ✅ Installation Flow
- [ ] Install prompt appears at appropriate time
- [ ] iOS installation instructions work
- [ ] App opens in standalone mode
- [ ] Status bar color matches theme
- [ ] Orientation locked to portrait (if desired)

### ✅ Push Notifications
- [ ] VAPID keys generated and configured
- [ ] Permission prompt at appropriate time
- [ ] Notifications work on Android
- [ ] Notifications work on desktop
- [ ] Notification badges and icons display correctly
- [ ] Click action opens correct URL

### ✅ Performance
- [ ] Lighthouse PWA score > 90
- [ ] First Contentful Paint < 2s
- [ ] Time to Interactive < 5s
- [ ] Bundle size < 200KB (initial)
- [ ] Images optimized and lazy loaded

### ✅ Testing
- [ ] Works offline
- [ ] Updates handled gracefully
- [ ] Queue system for offline actions
- [ ] Cross-browser testing (Chrome, Safari, Firefox, Edge)
- [ ] Device testing (iOS, Android, Desktop)

### ✅ Analytics
- [ ] Install tracking configured
- [ ] Engagement metrics setup
- [ ] Error tracking (Sentry)
- [ ] Performance monitoring
- [ ] Push notification metrics

### ✅ Documentation
- [ ] Installation guide for users
- [ ] Troubleshooting guide
- [ ] Admin documentation
- [ ] Developer documentation
```

---

## Implementation Commands

```bash
# Generate VAPID keys
npx web-push generate-vapid-keys

# Test PWA locally
npm run build
npm run preview

# Run Lighthouse audit
npx lighthouse https://localhost:3000 --view

# Test service worker
# Chrome DevTools > Application > Service Workers

# Test offline mode
# Chrome DevTools > Network > Offline checkbox

# Monitor bundle size
npx vite-bundle-visualizer
```

---

## Environment Variables

```env
# .env
VITE_APP_NAME="P2P Lending Platform"
VITE_APP_SHORT_NAME="P2PLend"
VITE_VAPID_PUBLIC_KEY="your-public-key-here"
VAPID_PRIVATE_KEY="your-private-key-here"
VITE_API_URL="${APP_URL}/api"
```

---

## Post-Launch Monitoring

```php
// app/Console/Commands/MonitorPWAMetrics.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\AnalyticsService;

class MonitorPWAMetrics extends Command
{
    protected $signature = 'pwa:metrics';
    protected $description = 'Monitor PWA adoption and engagement metrics';

    public function handle(AnalyticsService $analytics)
    {
        $metrics = [
            'total_users' => User::count(),
            'pwa_installed' => User::where('pwa_installed', true)->count(),
            'push_enabled' => User::whereHas('pushSubscriptions', function ($q) {
                $q->where('is_active', true);
            })->count(),
            'offline_actions_queued' => $analytics->getOfflineActionsCount(),
            'install_rate' => $analytics->getInstallRate(),
            'engagement_rate' => $analytics->getEngagementRate(),
        ];

        $this->info('PWA Metrics Report:');
        foreach ($metrics as $key => $value) {
            $this->line("$key: $value");
        }

        // Send to monitoring dashboard
        $analytics->sendMetrics($metrics);
    }
}
```

This implementation plan provides everything needed to transform your Laravel application into a fully-featured PWA. The phased approach ensures you can validate each component before moving to the next, reducing risk and allowing for iterative improvements based on real user feedback.
