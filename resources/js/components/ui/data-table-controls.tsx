import React from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import SortAscendingIcon from '@/components/svg/sort-ascending';
import SortDescendingIcon from '@/components/svg/sort-descending';
import { ChevronDown } from 'lucide-react';

type SortOptions = Record<string, string>;

interface DataTableControlsProps {
    search: string;
    onSearchChange: (value: string) => void;
    sortBy: string;
    sortOrder: string;
    onSortByChange: (value: string) => void;
    onSortOrderChange: (value: string) => void;
    sortOptions: SortOptions;
}

export function DataTableControls({
                                      search,
                                      onSearchChange,
                                      sortBy,
                                      sortOrder,
                                      onSortByChange,
                                      onSortOrderChange,
                                      sortOptions
                                  }: DataTableControlsProps) {
    return (
        <div className="flex items-center pb-4">
            <Input
                placeholder="Search..."
                value={search}
                onChange={(e) => onSearchChange(e.target.value)}
                className="mr-2 block max-w-sm"
            />

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" className="mr-2 ml-auto" title="Sort by">
                        {sortOptions[sortBy] ?? 'Sort By'}
                        <ChevronDown className="ml-2 h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    {Object.entries(sortOptions).map(([key, label]) => (
                        <DropdownMenuItem key={key} onClick={() => onSortByChange(key)}>
                            {label}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>

            <ToggleGroup
                type="single"
                variant="outline"
                value={sortOrder}
                onValueChange={(value) => value && onSortOrderChange(value)}
            >
                <ToggleGroupItem value="asc" aria-label="Ascending order">
                    <SortAscendingIcon />
                </ToggleGroupItem>
                <ToggleGroupItem value="desc" aria-label="Descending order">
                    <SortDescendingIcon />
                </ToggleGroupItem>
            </ToggleGroup>
        </div>
    );
}
