'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { certificationLevelLabels, Referee } from '@/pages/referees/index';
import { formatUTCDate } from '@/utils/date';
import { router } from '@inertiajs/react';

type RefereeColumnsProps = {
    onDelete: (referee: Referee) => void;
};

export const getRefereeColumns = ({ onDelete }: RefereeColumnsProps): ColumnDef<Referee>[] => [
    {
        accessorKey: 'full_name',
        header: 'Name',
    },
    {
        accessorKey: 'country',
        header: 'Nationality',
        cell: ({ row }) => row.original.country?.name ?? '-',
    },
    {
        accessorKey: 'certification_level',
        header: 'Certification Level',
        cell: ({ row }) => {
            const level = row.original.certification_level;
            return level !== null && level !== undefined ? certificationLevelLabels[level] : '-';
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
            const referee = row.original;

            const handleEdit = () => {
                router.visit(`/manage/referees/${referee.id}/edit`);
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
                        <DropdownMenuItem onClick={() => onDelete(referee)}>Delete</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
