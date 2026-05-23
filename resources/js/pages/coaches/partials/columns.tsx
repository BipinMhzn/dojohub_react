'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Coach } from '@/pages/coaches/index';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';

type CoachColumnsProps = {
    onDelete: (coach: Coach) => void;
};

export const getCoachColumns = ({ onDelete }: CoachColumnsProps): ColumnDef<Coach>[] => [
    {
        accessorKey: 'full_name',
        header: 'Name',
    },
    {
        id: 'clubs',
        header: 'Active Clubs',
        cell: ({ row }) => {
            const memberships = row.original.active_memberships ?? [];
            if (memberships.length === 0) {
                return <span className="text-muted-foreground">—</span>;
            }
            return memberships.map((m) => m.club?.name ?? m.club_id).join(', ');
        },
    },
    {
        accessorKey: 'email',
        header: 'Email',
        cell: ({ row }) => row.original.email ?? <span className="text-muted-foreground">—</span>,
    },
    {
        accessorKey: 'phone_number',
        header: 'Phone',
        cell: ({ row }) => row.original.phone_number ?? <span className="text-muted-foreground">—</span>,
    },
    {
        accessorKey: 'updated_at',
        header: 'Last Updated',
        cell: ({ row }) => formatUTCDate(row.original.updated_at),
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const coach = row.original;

            const handleEdit = () => {
                router.visit(`/manage/coaches/${coach.id}/edit`);
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
                        <DropdownMenuItem onClick={() => onDelete(coach)}>Delete</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
