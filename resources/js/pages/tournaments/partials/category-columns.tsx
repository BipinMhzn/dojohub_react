'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Gender, TournamentCategory } from '@/pages/tournaments';
import { router } from '@inertiajs/react';

const ageCategoryLabel: Record<number, string> = {
    0: 'Preparation', 1: 'Super Mini', 2: 'Mini', 3: 'Pupils', 4: 'Juveniles',
    5: 'Cadets', 6: 'Juniors', 7: 'Under 23', 8: 'Seniors', 9: 'Veterans',
};

const weightCategoryLabel: Record<number, string> = {
    0: '-48kg', 1: '-52kg', 2: '-57kg', 3: '-60kg', 4: '-63kg',
    5: '-66kg', 6: '-70kg', 7: '-73kg', 8: '-78kg', 9: '-81kg',
    10: '-90kg', 11: '-100kg', 12: '+100kg', 13: '+78kg',
};

const categoryStatusLabel: Record<number, string> = {
    0: 'Pending', 1: 'Seeding', 2: 'Active', 3: 'Completed',
};

const categoryStatusVariant: Record<number, string> = {
    0: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    1: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    2: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    3: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
};

export function getCategoryDisplayName(category: TournamentCategory): string {
    if (category.name) {
        return category.name;
    }
    const gender = Number(category.gender) === Gender.Male ? 'Male' : 'Female';
    const age = ageCategoryLabel[Number(category.age_category)] ?? '';
    const weight = weightCategoryLabel[Number(category.weight_category)] ?? '';
    return `${gender} ${age} ${weight}`.trim();
}

type CategoryColumnsProps = {
    tournamentId: string;
    onDelete: (category: TournamentCategory) => void;
};

export const getCategoryColumns = ({ tournamentId, onDelete }: CategoryColumnsProps): ColumnDef<TournamentCategory>[] => [
    {
        accessorKey: 'name',
        header: 'Category',
        cell: ({ row }) => <span className="font-medium text-foreground">{getCategoryDisplayName(row.original)}</span>,
    },
    {
        accessorKey: 'age_range',
        header: 'Age',
        cell: ({ row }) => `${row.original.min_age}–${row.original.max_age} yrs`,
    },
    {
        accessorKey: 'weight_range',
        header: 'Weight',
        cell: ({ row }) => `${row.original.min_weight}–${row.original.max_weight} kg`,
    },
    {
        accessorKey: 'registrations_count',
        header: 'Registered',
        cell: ({ row }) => row.original.registrations_count ?? 0,
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => (
            <Badge className={categoryStatusVariant[Number(row.original.status)]}>
                {categoryStatusLabel[Number(row.original.status)] ?? 'Unknown'}
            </Badge>
        ),
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const category = row.original;
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
                            <DropdownMenuItem onClick={() => router.visit(`/manage/tournaments/${tournamentId}/categories/${category.id}/edit`)}>Edit</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => onDelete(category)}>Delete</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            );
        },
    },
];
