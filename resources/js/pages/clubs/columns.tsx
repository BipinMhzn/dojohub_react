'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

export type Club = {
    id: string;
    name: string;
    abbreviation: string;
    logo_url: string;
    address: string;
    phone_number: string;
    created_at: string;
    updated_at: string;
};

export const columns: ColumnDef<Club>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'abbreviation',
        header: 'Abbreviation',
    },
    {
        accessorKey: 'address',
        header: 'Address',
    },
    {
        accessorKey: 'phone_number',
        header: 'Phone Number',
    },
    {
        accessorKey: 'updated_at',
        header: 'Last Updated',
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const club = row.original;

            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Open menu</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={() => console.log(club)}>Edit</DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => console.log(club)}>Delete</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
