import { useToast } from '@/Contexts/ToastContext';

// Example usage in a React component
export function ExampleComponent() {
    const toast = useToast();

    const handleSuccess = () => {
        toast.success('Operation completed successfully!');
    };

    const handleError = () => {
        toast.error('Something went wrong. Please try again.');
    };

    const handleInfo = () => {
        toast.info('New features are available!');
    };

    const handleWarning = () => {
        toast.warning('This action cannot be undone.');
    };

    const handleCustom = () => {
        toast.showToast('Custom message', 'message', {
            duration: 10000,
            description: 'This is a custom toast with extra options',
        });
    };

    return (
        <div>
            {/* Component content */}
        </div>
    );
}

// Example usage in Laravel controllers:
// 
// return redirect()->back()->with('success', 'License assigned successfully!');
// return redirect()->back()->with('error', 'Failed to assign license.');
// return redirect()->back()->with('info', 'Please check your email.');
// return redirect()->back()->with('warning', 'License will expire soon.');

// Example broadcasting toast notifications:
// 
// use App\Events\ToastNotification;
// use App\Events\SystemToast;
// 
// // Send to specific user
// ToastNotification::dispatch($user, 'Your payment was processed!', 'success');
// 
// // Send to all users (system-wide)
// SystemToast::dispatch('System maintenance scheduled for tonight', 'warning');