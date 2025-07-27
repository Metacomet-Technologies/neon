import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { useToast } from '@/Contexts/ToastContext';
import { useEcho, useEchoPublic } from '@laravel/echo-react';

export function ToastHandler() {
    const { props } = usePage<PageProps>();
    const { showToast } = useToast();
    const userId = props.auth?.user?.id;

    // Handle flash messages from Laravel backend
    useEffect(() => {
        if (props.flash) {
            if (props.flash.success) {
                showToast(props.flash.success, 'success');
            }
            if (props.flash.error) {
                showToast(props.flash.error, 'error');
            }
            if (props.flash.info) {
                showToast(props.flash.info, 'info');
            }
            if (props.flash.warning) {
                showToast(props.flash.warning, 'warning');
            }
        }
    }, [props.flash, showToast]);

    // Listen for toast events on the user's private channel
    if (userId) {
        useEcho(
            `App.Models.User.${userId}`,
            'ToastNotification',
            (event: any) => {
                const { message, type = 'message', options = {} } = event;
                showToast(message, type, options);
            }
        );
    }



    // Listen for system-wide toast events
    useEchoPublic(
        'system',
        'SystemToast',
        (event: any) => {
            const { message, type = 'info', options = {} } = event;
            showToast(message, type, options);
        }
    );

    return null;
}
