import { Button } from '@/Components/catalyst/button';
import { StackedLayout } from '@/Components/catalyst/stacked-layout';
import { Text } from '@/Components/catalyst/text';
import ThemeToggle from '@/Components/ThemeToggle';
import { ArrowLeftIcon } from '@heroicons/react/16/solid';
import ReactMarkdown from 'react-markdown';

/**
 * Markdown component to render markdown content.
 *
 * @param {Object} props - Component props
 * @param {string} props.content - The markdown content to render
 * @returns {JSX.Element} The rendered markdown content
 */
export default function Markdown({ content }: { content: string }): React.JSX.Element {
    return (
        <StackedLayout sidebar={false} navbar={false}>
            <div className="container mx-auto max-w-3xl">
                <Topbar />
                <div className="prose dark:prose-invert">
                    <ReactMarkdown>{content}</ReactMarkdown>
                </div>
            </div>
        </StackedLayout>
    );
}

/**
 * Topbar component to display navigation and theme toggle button.
 *
 * @returns {JSX.Element} The topbar component
 */
function Topbar(): React.JSX.Element {
    return (
        <div className="flex justify-between items-center mb-4">
            <Button plain className="flex items-center gap-2" href={route('home')}>
                <ArrowLeftIcon className="h-6 w-6 text-gray-500" />
                <Text>Home</Text>
            </Button>

            <ThemeToggle />
        </div>
    );
}
