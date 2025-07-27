import { FlashType, PageProps } from '@/types';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/react/20/solid';
import { useState } from 'react';

const getStyles = (type: FlashType) => {
    switch (type) {
        case 'error':
            return {
                bg: 'bg-red-50 dark:bg-red-900',
                text: 'text-red-800 dark:text-red-200',
                icon: 'text-red-400 dark:text-red-300',
                button: 'bg-red-50 dark:bg-red-900 text-red-500 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-800',
            };
        case 'success':
            return {
                bg: 'bg-green-50 dark:bg-green-900',
                text: 'text-green-800 dark:text-green-200',
                icon: 'text-green-400 dark:text-green-300',
                button: 'bg-green-50 dark:bg-green-900 text-green-500 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-800',
            };
        case 'info':
            return {
                bg: 'bg-blue-50 dark:bg-blue-900',
                text: 'text-blue-800 dark:text-blue-200',
                icon: 'text-blue-400 dark:text-blue-300',
                button: 'bg-blue-50 dark:bg-blue-900 text-blue-500 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-800',
            };
        case 'warning':
            return {
                bg: 'bg-yellow-50 dark:bg-yellow-900',
                text: 'text-yellow-800 dark:text-yellow-200',
                icon: 'text-yellow-400 dark:text-yellow-300',
                button: 'bg-yellow-50 dark:bg-yellow-900 text-yellow-500 dark:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-800',
            };
        default:
            return {
                bg: '',
                text: '',
                icon: '',
                button: '',
            };
    }
};

import clsx from 'clsx';

const FlashMessage = ({ type, message, onDismiss }: { type: FlashType; message: string; onDismiss: () => void }) => {
    const styles = getStyles(type);

    if (!message) {
        return null;
    }

    return (
        <div className={clsx('mb-4 rounded-md p-4', styles.bg)}>
            <div className="flex">
                <div className="shrink-0">
                    <CheckCircleIcon aria-hidden="true" className={clsx('size-5', styles.icon)} />
                </div>
                <div className="ml-3">
                    <p className={clsx('text-sm font-medium', styles.text)}>{message}</p>
                </div>
                <div className="ml-auto pl-3">
                    <div className="-mx-1.5 -my-1.5">
                        <button
                            onClick={onDismiss}
                            type="button"
                            className={clsx(
                                'inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2',
                                styles.button
                            )}
                        >
                            <span className="sr-only">Dismiss</span>
                            <XMarkIcon aria-hidden="true" className="size-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default function Flash({ flash }: { flash: PageProps['flash'] }) {
    const [shown, setShown] = useState<boolean>(!!flash);

    const handleDismiss = () => setShown(false);

    setTimeout(handleDismiss, 10000);

    if (!flash || !shown) {
        return null;
    }

    // Check each flash type and render the first one found
    if (flash.success) {
        return <FlashMessage type="success" message={flash.success} onDismiss={handleDismiss} />;
    }
    if (flash.error) {
        return <FlashMessage type="error" message={flash.error} onDismiss={handleDismiss} />;
    }
    if (flash.info) {
        return <FlashMessage type="info" message={flash.info} onDismiss={handleDismiss} />;
    }
    if (flash.warning) {
        return <FlashMessage type="warning" message={flash.warning} onDismiss={handleDismiss} />;
    }

    return null;
}
