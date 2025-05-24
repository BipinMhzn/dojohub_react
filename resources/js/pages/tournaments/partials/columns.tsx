'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tournament, TournamentGender, TournamentLevel } from '@/pages/tournaments';
import { StatusBadge } from '@/pages/tournaments/partials/status-badge';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';

export const columns: ColumnDef<Tournament>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => {
            const tournament = row.original;
            return (
                <div className="flex items-center gap-3">
                    <Avatar className="h-8 w-8">
                        <AvatarImage src={tournament.logo_url} alt={tournament.name} />
                        <AvatarFallback>
                            {tournament.name
                                .split(' ')
                                .map((n) => n[0])
                                .join('')
                                .toUpperCase()
                                .slice(0, 2)}
                        </AvatarFallback>
                    </Avatar>
                    <span>{tournament.name}</span>
                </div>
            );
        },
    },
    {
        accessorKey: 'level',
        header: 'Level',
        cell: ({ row }) => {
            const level = Number(row.original.level);
            switch (level) {
                case TournamentLevel.LOCAL:
                    return 'Local';
                case TournamentLevel.REGIONAL:
                    return 'Regional';
                case TournamentLevel.NATIONAL:
                    return 'National';
                default:
                    return 'Unknown';
            }
        },
    },
    {
        accessorKey: 'start_at',
        header: 'Start Date',
        cell: ({ row }) => formatUTCDate(row.original.updated_at),
    },
    {
        accessorKey: 'end_at',
        header: 'End Date',
        cell: ({ row }) => {
            if (row.original.end_at) {
                return formatUTCDate(row.original.updated_at);
            } else {
                return '-';
            }
        },
    },
    {
        accessorKey: 'gender',
        header: 'Gender',
        cell: ({ row }) => {
            return row.original.gender === TournamentGender.MALE ? 'Male' : 'Female';
        },
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
        cell: ({ row }) => {
            return <StatusBadge status={Number(row.original.status)} />;
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
