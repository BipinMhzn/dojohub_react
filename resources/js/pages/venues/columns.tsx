'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

export type Payment = {
    id: string;
    amount: number;
    status: 'pending' | 'processing' | 'success' | 'failed';
    email: string;
};

export type Venue = {
    id: string;
    name: string;
    address: string;
    city: string;
    state: string;
    country: string;
    created_at: string;
    updated_at: string;
};

export const columns: ColumnDef<Venue>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'address',
        header: 'Address',
    },
    {
        accessorKey: 'city',
        header: 'City',
    },
    {
        accessorKey: 'state',
        header: 'State',
    },
    {
        accessorKey: 'country',
        header: 'Country',
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const venue = row.original;

            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Open menu</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => console.log(venue)}>Edit</DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => console.log(venue)}>Delete</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
