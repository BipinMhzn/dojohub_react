'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Venue } from '@/pages/venues';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';

type VenueColumnsProps = {
    onDelete: (venue: Venue) => void;
};

export const getVenueColumns = ({ onDelete }: VenueColumnsProps): ColumnDef<Venue>[] => [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        id: 'address',
        header: 'Address',
        cell: ({ row }) => row.original.address?.address ?? '—',
    },
    {
        id: 'city',
        header: 'City',
        cell: ({ row }) => row.original.address?.city ?? '—',
    },
    {
        id: 'state',
        header: 'State',
        cell: ({ row }) => row.original.address?.state ?? '—',
    },
    {
        id: 'country',
        header: 'Country',
        cell: ({ row }) => row.original.address?.country ?? '—',
    },
    {
        accessorKey: 'mat_count',
        header: 'Mats',
    },
    {
        accessorKey: 'updated_at',
        header: 'Last Updated',
        cell: ({ row }) => formatUTCDate(row.original.updated_at),
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const venue = row.original;

            const handleEdit = () => {
                router.visit(`/manage/venues/${venue.id}/edit`);
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
                        <DropdownMenuItem onClick={() => onDelete(venue)}>Delete</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
