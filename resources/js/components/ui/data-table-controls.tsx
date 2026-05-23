import React from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import SortAscendingIcon from '@/components/svg/sort-ascending';
import SortDescendingIcon from '@/components/svg/sort-descending';
import { ChevronDown } from 'lucide-react';

type Options = Record<string, string>;

interface DataTableControlsProps {
    search: string;
    onSearchChange: (value: string) => void;
    sortBy: string;
    sortOrder: string;
    onSortByChange: (value: string) => void;
    onSortOrderChange: (value: string) => void;
    sortOptions: Options;
    statusOptions?: Options;
    statusValue?: string;
    onStatusChange?: (value: string) => void;
}

export function DataTableControls({
    search,
    onSearchChange,
    sortBy,
    sortOrder,
    onSortByChange,
    onSortOrderChange,
    sortOptions,
    statusOptions,
    statusValue,
    onStatusChange,
}: DataTableControlsProps) {
    return (
        <div className="flex items-center gap-2 pb-4">
            <Input
                placeholder="Search..."
                value={search}
                onChange={(e) => onSearchChange(e.target.value)}
                className="block max-w-sm"
            />

            <DropdownMenu>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" className="ml-auto">
                                {sortOptions[sortBy] ?? 'Sort By'}
                                <ChevronDown className="ml-2 h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                    </TooltipTrigger>
                    <TooltipContent>Sort by</TooltipContent>
                </Tooltip>
                <DropdownMenuContent align="end">
                    {Object.entries(sortOptions).map(([key, label], idx) => (
                        <div key={key}>
                            <DropdownMenuItem onClick={() => onSortByChange(key)}>
                                {label}
                            </DropdownMenuItem>
                            {idx < Object.entries(sortOptions).length - 1 && <DropdownMenuSeparator />}
                        </div>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>

            <ToggleGroup
                type="single"
                variant="outline"
                value={sortOrder}
                onValueChange={(value) => value && onSortOrderChange(value)}
            >
                <Tooltip>
                    <TooltipTrigger asChild>
                        <ToggleGroupItem value="asc" aria-label="Ascending order">
                            <SortAscendingIcon />
                        </ToggleGroupItem>
                    </TooltipTrigger>
                    <TooltipContent>Ascending</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <ToggleGroupItem value="desc" aria-label="Descending order">
                            <SortDescendingIcon />
                        </ToggleGroupItem>
                    </TooltipTrigger>
                    <TooltipContent>Descending</TooltipContent>
                </Tooltip>
            </ToggleGroup>

            {statusOptions && statusValue !== undefined && onStatusChange && (
                <>
                    <div className="bg-border h-6 w-px" />
                    <ToggleGroup
                        type="single"
                        variant="outline"
                        value={statusValue}
                        onValueChange={onStatusChange}
                    >
                        {Object.entries(statusOptions).map(([value, label]) => (
                            <ToggleGroupItem key={value} value={value} className="text-xs">
                                {label}
                            </ToggleGroupItem>
                        ))}
                    </ToggleGroup>
                </>
            )}
        </div>
    );
}
