'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

export enum PlayerGender {
    MALE = 0,
    FEMALE = 1,
}

export type Player = {
    id: string;
    name: string;
    gender: PlayerGender;
    dob: string;
    weight: number;
    profile_photo_url: string;
    phone_number: string;
    created_at: string;
    updated_at: string;
};

function calculateAge(dob: string): number {
    const today = new Date();
    const birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }

    return age;
}

export const columns: ColumnDef<Player>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => {
            const player = row.original;
            return (
                <div className="flex items-center gap-3">
                    <Avatar className="h-8 w-8">
                        <AvatarImage
                            src={player.profile_photo_url}
                            alt={player.name}
                        />
                        <AvatarFallback>
                            {player.name
                                .split(' ')
                                .map(n => n[0])
                                .join('')
                                .toUpperCase()
                                .slice(0, 2)}
                        </AvatarFallback>
                    </Avatar>
                    <span>{player.name}</span>
                </div>
            );
        },

    },
    {
        accessorKey: 'gender',
        header: 'Gender',
        cell: ({ row }) => {
            return row.original.gender === PlayerGender.MALE ? 'Male' : 'Female';
        },
    },
    {
        accessorKey: 'dob',
        header: 'Age',
        cell: ({ row }) => calculateAge(row.original.dob),
    },
    {
        accessorKey: 'weight',
        header: 'Weight (Kg)',
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
