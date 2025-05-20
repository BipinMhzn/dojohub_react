import SortAscendingIcon from '@/components/svg/sort-ascending';
import SortDescendingIcon from '@/components/svg/sort-descending';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import debounce from 'lodash/debounce';
import { ChevronDown } from 'lucide-react';
import React, { useCallback, useEffect, useState } from 'react';

type ToolbarProps = {
    keyword: string;
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    sortOptions: Record<string, string>;
    onSearchUpdated: (val: string) => void;
    onSortByUpdated: (val: string) => void;
    onSortOrderUpdated: (order: 'asc' | 'desc') => void;
    onPageNumberReset?: () => void;
};

const DataTableTopBar: React.FC<ToolbarProps> = ({
    keyword,
    sortBy,
    sortOrder,
    sortOptions,
    onSearchUpdated,
    onSortByUpdated,
    onSortOrderUpdated,
    onPageNumberReset,
}) => {
    const [searchText, setSearchText] = useState(keyword);

    // Debounced search
    const debouncedSearch = useCallback(
        debounce((value: string) => {
            onPageNumberReset?.();
            onSearchUpdated(value);
        }, 500),
        [],
    );

    useEffect(() => {
        debouncedSearch(searchText);
    }, [searchText]);

    return (
        <div className="flex flex-wrap items-center gap-2 pb-4">
            <Input placeholder="Search..." value={searchText} onChange={(e) => setSearchText(e.target.value)} className="mr-2 block max-w-sm" />

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" className="mr-2 ml-auto" title="Sort by">
                        {sortOptions[sortBy] || 'Sort By'}
                        <ChevronDown className="ml-1 h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    {Object.entries(sortOptions).map(([value, label], idx, arr) => (
                        <React.Fragment key={value}>
                            <DropdownMenuItem onClick={() => onSortByUpdated(value)} className={sortBy === value ? 'bg-gray-100' : ''}>
                                {label}
                            </DropdownMenuItem>
                            {idx !== arr.length - 1 && <DropdownMenuSeparator />}
                        </React.Fragment>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>

            <ToggleGroup type="single" variant="outline">
                <ToggleGroupItem value="asc" aria-label="Ascending order" onClick={() => onSortOrderUpdated('asc')}>
                    <SortAscendingIcon />
                </ToggleGroupItem>
                <ToggleGroupItem value="desc" aria-label="Descending order" onClick={() => onSortOrderUpdated('desc')}>
                    <SortDescendingIcon />
                </ToggleGroupItem>
            </ToggleGroup>
        </div>
    );
};

export default DataTableTopBar;
