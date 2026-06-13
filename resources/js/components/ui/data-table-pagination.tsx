import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { ChevronDown, ChevronLeft, ChevronRight } from 'lucide-react';
import React from 'react';
import { type PaginationLink } from '@/types';

type PaginationProps = {
    links: PaginationLink[];
    perPage: number;
    perPageSelect?: number[];
    onPageChange: (page: number) => void;
    onPerPageCountUpdated: (perPage: number) => void;
};

const DataTablePagination: React.FC<PaginationProps> = ({
                                                            links = [],
                                                            perPage = 50,
                                                            perPageSelect = [10, 50, 100, 200],
                                                            onPageChange,
                                                            onPerPageCountUpdated
                                                        }) => {
    const changePageNumber = (url: string | null) => {
        if (!url) return;
        const urlObj = new URL(url, window.location.origin);
        const page = urlObj.searchParams.get('page');
        if (page) {
            onPageChange(Number(page));
        }
    };

    const handlePerPageChange = (perPageCount: number) => {
        if (perPage !== perPageCount) {
            onPerPageCountUpdated(perPageCount);
        }
    };

    const isCompact = links.length <= 3;
    const firstLink = links[0];
    const lastLink = links[links.length - 1];
    const pageLinks = links.slice(1, links.length - 1);

    return (
        <nav className="mt-4">
            <div className={isCompact ? 'flex flex-col items-end space-y-2' : 'flex items-center justify-between'}>
                {!isCompact && (
                    <div className="flex h-8 items-center text-sm">
                        {firstLink?.url && (
                            <Button variant="outline" className={'rounded-r-none'}
                                    size={'sm'}
                                    onClick={() => changePageNumber(firstLink.url)}>
                                <span className="sr-only">Previous</span>
                                <ChevronLeft />
                            </Button>
                        )}

                        {pageLinks.map((pageLink) => (
                            <Button
                                key={pageLink.label}
                                variant={pageLink.active ? 'secondary' : 'outline'}
                                size={'sm'}
                                disabled={pageLink.active}
                                className={`rounded-none ${!firstLink?.url && pageLink.url === links[1]?.url ? 'rounded-l-lg' : ''} ${
                                    !lastLink?.url && pageLink.url === links[links.length - 2]?.url ? 'rounded-r-lg' : ''
                                }`}
                                onClick={() => changePageNumber(pageLink.url)}
                            >
                                {pageLink.label}
                            </Button>
                        ))}

                        {lastLink?.url && (
                            <Button variant="outline" className={'rounded-l-none'}
                                    size={'sm'}
                                    onClick={() => changePageNumber(lastLink.url)}>
                                <span className="sr-only">Next</span>
                                <ChevronRight />
                            </Button>
                        )}
                    </div>
                )}

                <div className="flex items-center space-x-2 text-sm">
                    <Label>Per page</Label>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" className="mr-2 ml-auto" title="Per page" size="sm">
                                {perPage}
                                <ChevronDown />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="min-w-[4rem]">
                            {perPageSelect.map((count, idx) => (
                                <div key={count}>
                                    <DropdownMenuItem
                                        className="px-2 py-1"
                                        onClick={() => handlePerPageChange(count)}>{count}</DropdownMenuItem>
                                    {idx < perPageSelect.length - 1 && <DropdownMenuSeparator />}
                                </div>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </nav>
    );
};

export default DataTablePagination;
