'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Tournament, TournamentGender, TournamentLevel } from '@/pages/tournaments';
import { StatusBadge } from '@/pages/tournaments/partials/status-badge';
import { formatUTCDate } from '@/utils/date';
import { Link, router } from '@inertiajs/react';

type TournamentColumnsProps = {
    onDelete: (player: Tournament) => void;
};

export const getTournamentColumns = ({ onDelete }: TournamentColumnsProps): ColumnDef<Tournament>[] => [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => {
            const tournament = row.original;
            return (
                <Link href={`/manage/tournaments/${tournament.id}/registrations`} className="flex items-center gap-3">
                    <Avatar className="h-8 w-8">
                        <AvatarImage src={tournament.logo_url ?? undefined} alt={tournament.name} />
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
                </Link>
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
        accessorKey: 'duration',
        header: 'Duration',
        cell: ({ row }) => {
            const start = formatUTCDate(row.original.start_at);
            const end = row.original.end_at ? formatUTCDate(row.original.end_at) : 'TBD';
            return `${start} - ${end}`;
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
        cell: ({ row }) => {
            const tournament = row.original;
            return (
                <Tooltip>
                    <TooltipTrigger>{tournament.age_category}</TooltipTrigger>
                    <TooltipContent>
                        <p>Min Age: {tournament.min_age}</p>
                        <p>Max Age: {tournament.max_age}</p>
                    </TooltipContent>
                </Tooltip>
            );
        },
    },
    {
        accessorKey: 'weight_category',
        header: 'Weight Category',
        cell: ({ row }) => {
            const tournament = row.original;
            return (
                <Tooltip>
                    <TooltipTrigger>{tournament.weight_category}</TooltipTrigger>
                    <TooltipContent>
                        <p>Min Weight: {tournament.min_weight}kg</p>
                        <p>Max Weight: {tournament.max_weight}kg</p>
                    </TooltipContent>
                </Tooltip>
            );
        },
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

            return (
                <>
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
                            <DropdownMenuItem onClick={() => onDelete(tournament)}>Delete</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </>
            );
        },
    },
];
