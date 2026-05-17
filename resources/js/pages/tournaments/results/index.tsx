import HeadingSmall from '@/components/heading-small';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import CategoryLayout from '@/layouts/category/layout';
import { Tournament, TournamentCategory } from '@/pages/tournaments';
import { getCategoryDisplayName } from '@/pages/tournaments/partials/category-columns';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Info, Medal } from 'lucide-react';

interface TournamentResultRow {
    id: string;
    position: number;
    registration: {
        id: string;
        player_age: number;
        player_weight: number;
        player?: {
            id: string;
            name: string;
            profile_photo_url: string | null;
        };
        club?: {
            id: string;
            name: string;
        };
    };
}

interface Props {
    tournament: Tournament;
    category: TournamentCategory;
    results: TournamentResultRow[];
}

const positionLabel = (position: number): string => {
    if (position === 1) {
        return 'Gold';
    }
    if (position === 2) {
        return 'Silver';
    }
    if (position === 3) {
        return 'Bronze';
    }
    return `${position}th`;
};

const positionColor = (position: number): string => {
    if (position === 1) {
        return 'text-yellow-500';
    }
    if (position === 2) {
        return 'text-slate-400';
    }
    if (position === 3) {
        return 'text-amber-600';
    }
    return 'text-muted-foreground';
};

export default function TournamentResults({ tournament, category, results }: Props) {
    const categoryName = getCategoryDisplayName(category);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tournaments', href: '/manage/tournaments' },
        { title: tournament.name, href: `/manage/tournaments/${tournament.id}` },
        { title: categoryName, href: `/manage/tournaments/${tournament.id}/categories/${category.id}/edit` },
        { title: 'Results', href: `/manage/tournaments/${tournament.id}/categories/${category.id}/results` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${categoryName} – Results`} />

            <CategoryLayout tournament={tournament} category={category} activeKey="results">
                <HeadingSmall title="Final Standings" description="Final positions for this category." />

                {results.length === 0 ? (
                    <Alert className="mt-4">
                        <Info className="size-4" />
                        <AlertDescription>
                            No results recorded yet. Results are finalized after the bracket is completed.
                        </AlertDescription>
                    </Alert>
                ) : (
                    <Table className="mt-4">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-24">Position</TableHead>
                                <TableHead>Player</TableHead>
                                <TableHead>Club</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {results.map((result) => (
                                <TableRow key={result.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <Medal className={`h-4 w-4 ${positionColor(result.position)}`} />
                                            <span className="font-medium">{positionLabel(result.position)}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-3">
                                            <Avatar className="h-8 w-8">
                                                <AvatarImage
                                                    src={result.registration.player?.profile_photo_url ?? undefined}
                                                    alt={result.registration.player?.name}
                                                />
                                                <AvatarFallback>
                                                    {result.registration.player?.name
                                                        .split(' ')
                                                        .map((n) => n[0])
                                                        .join('')
                                                        .toUpperCase()
                                                        .slice(0, 2)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span>{result.registration.player?.name ?? '—'}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>{result.registration.club?.name ?? '—'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CategoryLayout>
        </AppLayout>
    );
}
