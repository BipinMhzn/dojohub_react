'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Tournament, TournamentGender, TournamentLevel } from '@/pages/tournaments';
import { StatusBadge } from '@/pages/tournaments/partials/status-badge';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export const columns: ColumnDef<Tournament>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => {
            const tournament = row.original;
            return (
                <div className="flex items-center gap-3">
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
            const [showDeleteDialog, setShowDeleteDialog] = useState(false);

            const handleEdit = () => {
                router.visit(`/manage/tournaments/${tournament.id}/edit`);
            };

            const handleDelete = () => {
                router.delete(`/manage/tournaments/${tournament.id}`);
                setShowDeleteDialog(false);
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
                            <DropdownMenuItem onClick={() => setShowDeleteDialog(true)}>Delete</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Delete Tournament</DialogTitle>
                                <DialogDescription>
                                    Are you sure you want to delete {tournament.name}? This action cannot be undone.
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setShowDeleteDialog(false)}>
                                    Cancel
                                </Button>
                                <Button variant="destructive" onClick={handleDelete}>
                                    Delete
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </>
            );
        },
    },
];
