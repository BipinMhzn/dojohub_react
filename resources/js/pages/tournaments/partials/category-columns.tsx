'use client';

import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { CategoryStatus, Gender, TournamentCategory } from '@/pages/tournaments';
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
    0: 'Draft',
    1: 'Registration Open',
    2: 'Registration Closed',
    3: 'Bracket Generated',
    4: 'In Progress',
    5: 'Completed',
    6: 'Cancelled',
};

const categoryStatusVariant: Record<number, string> = {
    0: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    1: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    2: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    3: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
    4: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    5: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    6: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
};

// Manager-triggered transitions only (excludes system transitions BracketGenerated, InProgress, Completed)
export const managerTransitions: Record<number, Array<{ target: CategoryStatus; label: string }>> = {
    [CategoryStatus.Draft]: [
        { target: CategoryStatus.RegistrationOpen, label: 'Open Registration' },
        { target: CategoryStatus.Cancelled, label: 'Cancel' },
    ],
    [CategoryStatus.RegistrationOpen]: [
        { target: CategoryStatus.RegistrationClosed, label: 'Close Registration' },
        { target: CategoryStatus.Cancelled, label: 'Cancel' },
    ],
    [CategoryStatus.RegistrationClosed]: [
        { target: CategoryStatus.RegistrationOpen, label: 'Re-open Registration' },
        { target: CategoryStatus.Cancelled, label: 'Cancel' },
    ],
    [CategoryStatus.BracketGenerated]: [
        { target: CategoryStatus.Cancelled, label: 'Cancel' },
    ],
    [CategoryStatus.InProgress]: [
        { target: CategoryStatus.Cancelled, label: 'Cancel' },
    ],
    [CategoryStatus.Completed]: [],
    [CategoryStatus.Cancelled]: [],
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

export function transitionCategory(tournamentId: string, categoryId: string, target: CategoryStatus): void {
    router.post(`/manage/tournaments/${tournamentId}/categories/${categoryId}/transition`, { target });
}

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
            const statusNum = Number(category.status);
            const transitions = managerTransitions[statusNum] ?? [];
            const isTerminal = statusNum === CategoryStatus.Completed || statusNum === CategoryStatus.Cancelled;

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
                            {transitions.map(({ target, label }) => (
                                <DropdownMenuItem
                                    key={target}
                                    onClick={() => transitionCategory(tournamentId, category.id, target)}
                                    className={target === CategoryStatus.Cancelled ? 'text-destructive focus:text-destructive' : ''}
                                >
                                    {label}
                                </DropdownMenuItem>
                            ))}
                            {!isTerminal && transitions.length > 0 && <DropdownMenuSeparator />}
                            {!isTerminal && (
                                <DropdownMenuItem onClick={() => router.visit(`/manage/tournaments/${tournamentId}/categories/${category.id}/edit`)}>
                                    Edit
                                </DropdownMenuItem>
                            )}
                            {!isTerminal && (
                                <>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem onClick={() => onDelete(category)}>Delete</DropdownMenuItem>
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            );
        },
    },
];
