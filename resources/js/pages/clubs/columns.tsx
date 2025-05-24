'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';

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
        cell: ({ row }) => {
            const club = row.original;
            return (
                <div className="flex items-center gap-3">
                    <Avatar className="h-8 w-8">
                        <AvatarImage src={club.logo_url} alt={club.name} />
                        <AvatarFallback>
                            {club.name
                                .split(' ')
                                .map((n) => n[0])
                                .join('')
                                .toUpperCase()
                                .slice(0, 2)}
                        </AvatarFallback>
                    </Avatar>
                    <span>{club.name}</span>
                </div>
            );
        },
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
        cell: ({ row }) => formatUTCDate(row.original.updated_at),
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const club = row.original;

            const handleEdit = () => {
                router.visit(`/manage/clubs/${club.id}/edit`);
            };

            const handleDelete = () => {
                console.log('Delete club:', club);
            };

            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Open menu</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={handleEdit}>Edit</DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={handleDelete}>Delete</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
