'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';

export type Player = {
    id: string;
    name: string;
    gender: number;
    dob: Date;
    weight: number;
    profile_photo_url: string;
    phone_number: string;
    created_at: string;
    updated_at: string;
};

export const columns: ColumnDef<Player>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'gender',
        header: 'Gender',
    },
    {
        accessorKey: 'dob',
        header: 'Age',
    },
    {
        accessorKey: 'weight',
        header: 'Weight',
    },
    {
        accessorKey: 'updated_at',
        header: 'Last Updated',
        cell: ({ row }) => formatUTCDate(row.original.updated_at),
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const player = row.original;

            const handleEdit = () => {
                router.visit(`/manage/players/${player.id}/edit`);
            };

            const handleDelete = () => {
                console.log('Delete player:', player);
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
