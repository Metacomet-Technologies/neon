import { XMarkIcon } from '@heroicons/react/20/solid';
import clsx from 'clsx';
import React from 'react';

interface InlineAlertProps {
    children: React.ReactNode;
    color?: 'red' | 'amber' | 'green' | 'blue';
    onClose?: () => void;
    className?: string;
}

const colorClasses = {
    red: 'bg-red-50 text-red-800 dark:bg-red-900/10 dark:text-red-400',
    amber: 'bg-amber-50 text-amber-800 dark:bg-amber-900/10 dark:text-amber-400',
    green: 'bg-green-50 text-green-800 dark:bg-green-900/10 dark:text-green-400',
    blue: 'bg-blue-50 text-blue-800 dark:bg-blue-900/10 dark:text-blue-400',
};

export function InlineAlert({ children, color = 'blue', onClose, className }: InlineAlertProps) {
    return (
        <div className={clsx('rounded-lg p-4 relative', colorClasses[color], className)}>
            <div className="flex">
                <div className="flex-1">{children}</div>
                {onClose && (
                    <button
                        type="button"
                        onClick={onClose}
                        className="ml-3 inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    >
                        <span className="sr-only">Dismiss</span>
                        <XMarkIcon className="h-5 w-5" aria-hidden="true" />
                    </button>
                )}
            </div>
        </div>
    );
}