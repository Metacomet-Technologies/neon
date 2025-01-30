import { Layout } from '@/Layout/Layout';

import ReactMarkdown from 'react-markdown';

export default function Markdown({ content }: { content: string }) {
    return (
        <div className="prose prose-lg dark:prose-invert">
            <ReactMarkdown>{content}</ReactMarkdown>
        </div>
    );
}

Markdown.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
