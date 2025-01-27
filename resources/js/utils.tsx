import { CheckCircleIcon, XCircleIcon } from '@heroicons/react/16/solid';

export function booleanToIconForTables(value: boolean) {
    return value ? (
        <CheckCircleIcon className="text-green-500 dark:text-green-400 size-6" />
    ) : (
        <XCircleIcon className="text-red-500 dark:text-red-400 size-6" />
    );
}

export function formatDateTime(dateTime: string | null) {
    if (dateTime === null) {
        return '-';
    }
    return new Date(dateTime).toLocaleString();
}
