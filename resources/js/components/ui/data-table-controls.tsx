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
import { ChevronDown, Filter } from 'lucide-react';

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
                            <Button variant="outline" className="ml-auto cursor-pointer">
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
                        <ToggleGroupItem
                            value="asc"
                            aria-label="Ascending order"
                            className="cursor-pointer aria-checked:bg-accent aria-checked:text-accent-foreground"
                        >
                            <SortAscendingIcon />
                        </ToggleGroupItem>
                    </TooltipTrigger>
                    <TooltipContent>Ascending</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <ToggleGroupItem
                            value="desc"
                            aria-label="Descending order"
                            className="cursor-pointer aria-checked:bg-accent aria-checked:text-accent-foreground"
                        >
                            <SortDescendingIcon />
                        </ToggleGroupItem>
                    </TooltipTrigger>
                    <TooltipContent>Descending</TooltipContent>
                </Tooltip>
            </ToggleGroup>

            {statusOptions && statusValue !== undefined && onStatusChange && (
                <>
                    <div className="bg-border h-6 w-px" />
                    <DropdownMenu>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" className="cursor-pointer">
                                        <Filter className="mr-2 h-4 w-4" />
                                        {statusOptions[statusValue] ?? 'Status'}
                                        <ChevronDown className="ml-2 h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                            </TooltipTrigger>
                            <TooltipContent>Filter by status</TooltipContent>
                        </Tooltip>
                        <DropdownMenuContent align="end">
                            {Object.entries(statusOptions).map(([value, label], idx) => (
                                <div key={value}>
                                    <DropdownMenuItem onClick={() => onStatusChange(value)}>
                                        {label}
                                    </DropdownMenuItem>
                                    {idx < Object.entries(statusOptions).length - 1 && <DropdownMenuSeparator />}
                                </div>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </>
            )}
        </div>
    );
}
