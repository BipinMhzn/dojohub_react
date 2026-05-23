'use client';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Venue } from '@/pages/venues';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

type VenueColumnsProps = { onDelete: (venue: Venue) => void };

export const getVenueColumns = ({ onDelete }: VenueColumnsProps): ColumnDef<Venue>[] => [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => <span className="font-medium">{row.original.name}</span>,
    },
    {
        id: 'address',
        header: 'Address',
        cell: ({ row }) => row.original.address?.address_line_1 ?? '—',
    },
    {
        id: 'city',
        header: 'City',
        cell: ({ row }) => row.original.address?.city ?? '—',
    },
    {
        id: 'country',
        header: 'Country',
        cell: ({ row }) => row.original.address?.country?.name ?? row.original.address?.country_code ?? '—',
    },
    {
        id: 'mats',
        header: 'Mats',
        cell: ({ row }) => row.original.mats_count ?? 0,
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
            return (
                <div onClick={(e) => e.stopPropagation()}>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 cursor-pointer p-0">
                                <span className="sr-only">Open menu</span>
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => router.visit(`/manage/venues/${venue.id}/edit`)}>Edit</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => onDelete(venue)}>Delete</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            );
        },
    },
];
