'use client';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ColumnDef } from '@tanstack/react-table';
import { formatUTCDate } from '@/utils/date';
import { BeltRank, BELT_RANK_LABELS, ClubMembership } from '@/pages/clubs/players/index';

type ClubPlayerColumnsProps = {
    onEndMembership: (membership: ClubMembership) => void;
};

export const getClubPlayerColumns = ({ onEndMembership }: ClubPlayerColumnsProps): ColumnDef<ClubMembership>[] => [
    {
        id: 'player',
        header: 'Player',
        cell: ({ row }) => {
            const { player } = row.original;
            return (
                <div className="flex items-center gap-3">
                    <Avatar className="h-8 w-8">
                        <AvatarImage src={player.profile_photo_url ?? undefined} alt={player.name} />
                        <AvatarFallback>
                            {player.name
                                .split(' ')
                                .map((n) => n[0])
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
        id: 'belt_rank',
        header: 'Belt Rank',
        cell: ({ row }) => {
            const rank = row.original.belt_rank;
            if (rank === null || rank === undefined) {
                return <span className="text-muted-foreground">—</span>;
            }
            return <span>{BELT_RANK_LABELS[rank as BeltRank]}</span>;
        },
    },
    {
        accessorKey: 'grade',
        header: 'Grade',
        cell: ({ row }) => row.original.grade ?? <span className="text-muted-foreground">—</span>,
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
                return (
                    <Badge variant="secondary">
                        Ended {formatUTCDate(ended_at)}
                    </Badge>
                );
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
