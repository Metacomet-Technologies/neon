import {
    Pagination,
    PaginationGap,
    PaginationList,
    PaginationNext,
    PaginationPage,
    PaginationPrevious,
} from '@/Components/catalyst/pagination';
import { PaginationLink } from '@/types';

export default function PaginationRow({
    simple = false,
    links,
    className,
}: {
    simple?: boolean | null;
    links: PaginationLink[];
    className?: string;
}) {
    // find the commands that contain the label 'Previous' and get the url
    const previousLink = links.find((link) => link.label.includes('Previous'));
    const nextLink = links.find((link) => link.label.includes('Next'));

    const otherLinks = links.filter((link) => !link.label.includes('Previous') && !link.label.includes('Next'));

    const renderLinks = () => {
        if (otherLinks.length <= 5) {
            return otherLinks.map((link) => (
                <PaginationPage key={link.url} href={link.url ?? '#'} current={link.active || false}>
                    {link.label}
                </PaginationPage>
            ));
        }

        const firstThree = otherLinks.slice(0, 3);
        const lastTwo = otherLinks.slice(-2);

        return (
            <>
                {firstThree.map((link) => (
                    <PaginationPage key={link.url} href={link.url ?? '#'} current={link.active || false}>
                        {link.label}
                    </PaginationPage>
                ))}
                <PaginationGap />
                {lastTwo.map((link) => (
                    <PaginationPage key={link.url} href={link.url ?? '#'} current={link.active || false}>
                        {link.label}
                    </PaginationPage>
                ))}
            </>
        );
    };

    return (
        <Pagination className={className}>
            <PaginationPrevious href={previousLink?.url} />
            {!simple && <PaginationList>{renderLinks()}</PaginationList>}
            <PaginationNext href={nextLink?.url} />
        </Pagination>
    );
}
