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

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

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
                                                            perPageSelect = [50, 100, 150, 200],
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

    return (
        <nav className="mt-4">
            <div className={isCompact ? 'flex flex-col items-end space-y-2' : 'flex items-center justify-between'}>
                {/* Pagination */}
                {!isCompact && (
                    <div className="flex h-8 items-center text-sm">
                        {links[0]?.url && (
                            <Button variant="outline" className={'rounded-r-none'}
                                    onClick={() => changePageNumber(links[0].url)}>
                                <span className="sr-only">Previous</span>
                                <ChevronLeft />
                            </Button>
                        )}

                        {links.slice(1, links.length - 1).map((pageLink, index) => (
                            <Button
                                variant={pageLink.active ? 'secondary' : 'outline'}
                                disabled={pageLink.active}
                                className={`rounded-none ${!links[0]?.url && pageLink.url === links[1]?.url ? 'rounded-l-lg' : ''} ${
                                    !links[links.length - 1]?.url && pageLink.url === links[links.length - 2]?.url ? 'rounded-r-lg' : ''
                                }`}
                                onClick={() => changePageNumber(pageLink.url)}
                            >
                                {pageLink.label}
                            </Button>
                        ))}

                        {links[links.length - 1]?.url && (
                            <Button variant="outline" className={'rounded-l-none'}
                                    onClick={() => changePageNumber(links[links.length - 1].url)}>
                                <span className="sr-only">Next</span>
                                <ChevronRight />
                            </Button>
                        )}
                    </div>
                )}

                {/* Per Page Selector */}
                <div className="flex items-center space-x-2 text-sm">
                    <Label>Per page</Label>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" className="mr-2 ml-auto" title="Per page">
                                {perPage}
                                <ChevronDown />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            {perPageSelect.map((count, idx) => (
                                <div key={count}>
                                    <DropdownMenuItem
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
