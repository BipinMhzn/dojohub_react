'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ColumnDef } from '@tanstack/react-table';
import { ClubCoachMembership } from '@/pages/clubs/coaches/index';
import { formatUTCDate } from '@/utils/date';

type ClubCoachColumnsProps = {
    onEndMembership: (membership: ClubCoachMembership) => void;
};

export const getClubCoachColumns = ({ onEndMembership }: ClubCoachColumnsProps): ColumnDef<ClubCoachMembership>[] => [
    {
        id: 'coach',
        header: 'Coach',
        cell: ({ row }) => row.original.coach?.full_name ?? row.original.coach_id,
    },
    {
        accessorKey: 'started_at',
        header: 'Joined',
        cell: ({ row }) => formatUTCDate(row.original.started_at),
    },
    {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const { ended_at } = row.original;
            if (ended_at) {
                return <Badge variant="secondary">Ended {formatUTCDate(ended_at)}</Badge>;
            }
            return <Badge variant="default">Active</Badge>;
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const membership = row.original;
            if (membership.ended_at) {
                return null;
            }
            return (
                <Button variant="outline" size="sm" onClick={() => onEndMembership(membership)}>
                    End Membership
                </Button>
            );
        },
    },
];
