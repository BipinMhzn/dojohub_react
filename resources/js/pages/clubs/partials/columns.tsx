'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Club } from '@/pages/clubs';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';

type ClubColumnsProps = {
    onDelete: (player: Club) => void;
};

export const getClubColumns = ({ onDelete }: ClubColumnsProps): ColumnDef<Club>[] => [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => {
            const club = row.original;
            return (
                <div className="flex items-center gap-3">
                    <Avatar className="h-8 w-8">
                        <AvatarImage src={club.logo_url ?? undefined} alt={club.name} />
                        <AvatarFallback>
                            {club.name
                                .split(' ')
                                .map((n) => n[0])
                                .join('')
                                .toUpperCase()
                                .slice(0, 2)}
                        </AvatarFallback>
                    </Avatar>
                    <span className="font-medium">{club.name}</span>
                </div>
            );
        },
    },
    {
        accessorKey: 'abbreviation',
        header: 'Abbreviation',
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
        accessorKey: 'phone_number',
        header: 'Phone Number',
        cell: ({ row }) => {
            const club = row.original;
            if (!club.phone_number) {
                return '—';
            }
            return (
                <a href={`tel:${club.phone_number}`} onClick={(e) => e.stopPropagation()}>
                    <span>{club.phone_number}</span>
                </a>
            );
        },
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
                            <DropdownMenuItem onClick={() => router.visit(`/manage/clubs/${club.id}`)}>Edit</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => onDelete(club)}>Delete</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            );
        },
    },
];
