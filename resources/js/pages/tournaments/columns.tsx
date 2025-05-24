'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';

export type Tournament = {
    id: string;
    name: string;
    description: string;
    logo_url: string;
    level: string;
    start_at: string;
    end_at: string;
    weight_category: string;
    min_weight: string;
    max_weight: string;
    age_category: string;
    min_age: string;
    max_age: string;
    gender: string;
    status: string;
    venue_id: string;
    created_at: string;
    updated_at: string;
};

export const columns: ColumnDef<Tournament>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'start_at',
        header: 'Start Date',
    },
    {
        accessorKey: 'gender',
        header: 'Gender',
    },
    {
        accessorKey: 'age_category',
        header: 'Age Category',
    },
    {
        accessorKey: 'weight_category',
        header: 'Weight Category',
    },
    {
        accessorKey: 'status',
        header: 'Status',
    },
    {
        accessorKey: 'updated_at',
        header: 'Last Updated',
        cell: ({ row }) => formatUTCDate(row.original.updated_at),
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const tournament = row.original;

            const handleEdit = () => {
                router.visit(`/manage/tournaments/${tournament.id}/edit`);
            };

            const handleDelete = () => {
                console.log('Delete tournament:', tournament);
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
