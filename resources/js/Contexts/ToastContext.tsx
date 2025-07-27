import { router } from '@inertiajs/react';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useRef } from 'react';
import { ExternalToast, toast, Toaster } from 'sonner';

type ToastType = 'success' | 'error' | 'info' | 'warning' | 'message';

interface ToastOptions extends ExternalToast {
    id?: string;
}

interface ToastContextType {
    showToast: (message: string, type?: ToastType, options?: ToastOptions) => void;
    success: (message: string, options?: ToastOptions) => void;
    error: (message: string, options?: ToastOptions) => void;
    info: (message: string, options?: ToastOptions) => void;
    warning: (message: string, options?: ToastOptions) => void;
}

const ToastContext = createContext<ToastContextType | undefined>(undefined);

interface ToastProviderProps {
    children: React.ReactNode;
}

export function ToastProvider({ children }: ToastProviderProps) {
    const shownMessages = useRef<Set<string>>(new Set());
    const messageTimeouts = useRef<Map<string, NodeJS.Timeout>>(new Map());

    // Generate a unique ID for deduplication
    const generateMessageId = useCallback((message: string, type?: ToastType): string => {
        return `${type || 'message'}-${message}`;
    }, []);

    // Clear old messages from deduplication set after 5 seconds
    const scheduleMessageCleanup = useCallback((messageId: string) => {
        const existingTimeout = messageTimeouts.current.get(messageId);
        if (existingTimeout) {
            clearTimeout(existingTimeout);
        }

        const timeout = setTimeout(() => {
            shownMessages.current.delete(messageId);
            messageTimeouts.current.delete(messageId);
        }, 5000);

        messageTimeouts.current.set(messageId, timeout);
    }, []);

    // Show toast with deduplication
    const showToast = useCallback(
        (message: string, type: ToastType = 'message', options?: ToastOptions) => {
            const messageId = options?.id || generateMessageId(message, type);

            // Check for duplicate
            if (shownMessages.current.has(messageId)) {
                return;
            }

            // Add to shown messages
            shownMessages.current.add(messageId);
            scheduleMessageCleanup(messageId);

            // Show the toast based on type
            const toastOptions: ToastOptions = {
                ...options,
                id: messageId,
            };

            switch (type) {
                case 'success':
                    toast.success(message, toastOptions);
                    break;
                case 'error':
                    toast.error(message, toastOptions);
                    break;
                case 'info':
                    toast.info(message, toastOptions);
                    break;
                case 'warning':
                    toast.warning(message, toastOptions);
                    break;
                default:
                    toast(message, toastOptions);
            }
        },
        [generateMessageId, scheduleMessageCleanup]
    );

    // Convenience methods
    const success = useCallback(
        (message: string, options?: ToastOptions) => {
            showToast(message, 'success', options);
        },
        [showToast]
    );

    const error = useCallback(
        (message: string, options?: ToastOptions) => {
            showToast(message, 'error', options);
        },
        [showToast]
    );

    const info = useCallback(
        (message: string, options?: ToastOptions) => {
            showToast(message, 'info', options);
        },
        [showToast]
    );

    const warning = useCallback(
        (message: string, options?: ToastOptions) => {
            showToast(message, 'warning', options);
        },
        [showToast]
    );

    // Handle flash messages from Laravel backend
    useEffect(() => {
        // This will be handled by a separate component that has access to Inertia context
    }, []);

    // Handle Inertia navigation flash messages
    useEffect(() => {
        const removeFinishListener = router.on('finish', () => {
            // Flash messages are already handled by the initial page props
            // This is here for future use if needed
        });

        return () => {
            removeFinishListener();
        };
    }, [showToast]);

    // Setup Laravel Reverb event listeners
    // Note: This is commented out as it should be in a separate component
    // that has access to Inertia context and can use hooks properly

    // Cleanup timeouts on unmount
    useEffect(() => {
        return () => {
            messageTimeouts.current.forEach((timeout) => clearTimeout(timeout));
            messageTimeouts.current.clear();
            shownMessages.current.clear();
        };
    }, []);

    const contextValue = useMemo(
        () => ({
            showToast,
            success,
            error,
            info,
            warning,
        }),
        [showToast, success, error, info, warning]
    );

    return (
        <ToastContext.Provider value={contextValue}>
            {children}
            <Toaster
                position="top-right"
                toastOptions={{
                    duration: 5000,
                    className: 'sonner-toast',
                    style: {
                        background: 'var(--toast-bg)',
                        color: 'var(--toast-text)',
                        border: '1px solid var(--toast-border)',
                    },
                }}
                theme="system"
                richColors
                closeButton
            />
        </ToastContext.Provider>
    );
}

export function useToast(): ToastContextType {
    const context = useContext(ToastContext);
    if (!context) {
        throw new Error('useToast must be used within a ToastProvider');
    }
    return context;
}
