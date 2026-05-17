import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { AgeCategory, BracketMatch, BracketRound, BracketStructure, BracketType, CompetitorResult, Gender, MatchStatus, Tournament, TournamentCategory, WeightCategory } from '@/pages/tournaments';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Info, Trophy } from 'lucide-react';
import { useState } from 'react';

const ageCategoryLabel: Record<number, string> = {
    0: 'Preparation', 1: 'Super Mini', 2: 'Mini', 3: 'Pupils', 4: 'Juveniles',
    5: 'Cadets', 6: 'Juniors', 7: 'Under 23', 8: 'Seniors', 9: 'Veterans',
};

const weightCategoryLabel: Record<number, string> = {
    0: '-48kg', 1: '-52kg', 2: '-57kg', 3: '-60kg', 4: '-63kg',
    5: '-66kg', 6: '-70kg', 7: '-73kg', 8: '-78kg', 9: '-81kg',
    10: '-90kg', 11: '-100kg', 12: '+100kg', 13: '+78kg',
};

interface TournamentBracket {
    id: string;
    type: number;
    status: number;
}

interface Props {
    tournament: Tournament;
    category: TournamentCategory;
    bracket: TournamentBracket | null;
    bracketStructure: BracketStructure | null;
}

export default function TournamentMatches({ tournament, category, bracket, bracketStructure }: Props) {
    const categoryName = category.name ?? `${Number(category.gender) === Gender.Male ? 'Male' : 'Female'} ${ageCategoryLabel[Number(category.age_category)]} ${weightCategoryLabel[Number(category.weight_category)]}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tournaments', href: '/manage/tournaments' },
        { title: tournament.name, href: `/manage/tournaments/${tournament.id}/categories` },
        { title: 'Categories', href: `/manage/tournaments/${tournament.id}/categories` },
        { title: categoryName, href: `/manage/tournaments/${tournament.id}/categories/${category.id}/registrations` },
        { title: 'Matches', href: '#' },
    ];

    const [generateType, setGenerateType] = useState('0');
    const [generateSeedingMethod, setGenerateSeedingMethod] = useState('0');
    const [generateDialogOpen, setGenerateDialogOpen] = useState(false);
    const [selectedMatch, setSelectedMatch] = useState<BracketMatch | null>(null);
    const [resultDialogOpen, setResultDialogOpen] = useState(false);
    const [winnerPosition, setWinnerPosition] = useState<string>('');
    const [scheduledAt, setScheduledAt] = useState('');

    const handleGenerateBracket = () => {
        router.post(
            `/manage/tournaments/${tournament.id}/categories/${category.id}/matches/generate`,
            { type: parseInt(generateType), seeding_method: parseInt(generateSeedingMethod) },
            { onSuccess: () => setGenerateDialogOpen(false) },
        );
    };

    const handleRecordResult = () => {
        if (!selectedMatch || winnerPosition === '') return;
        router.patch(
            `/manage/tournaments/${tournament.id}/categories/${category.id}/matches/${selectedMatch.id}/result`,
            {
                winner_position: parseInt(winnerPosition),
                white_score: selectedMatch.white?.score ?? null,
                white_penalties: selectedMatch.white?.penalties ?? null,
                blue_score: selectedMatch.blue?.score ?? null,
                blue_penalties: selectedMatch.blue?.penalties ?? null,
            },
            { onSuccess: () => { setResultDialogOpen(false); setSelectedMatch(null); setWinnerPosition(''); } },
        );
    };

    const allRounds: BracketRound[] = bracketStructure
        ? Object.values(bracketStructure.sections).flat()
        : [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Matches – ${categoryName}`} />

            <div className="px-4 py-6">
                <div className="mb-4 flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-bold">{categoryName} – Bracket</h1>
                        <p className="text-sm text-muted-foreground">Age {category.min_age}–{category.max_age} · Weight {category.min_weight}–{category.max_weight} kg</p>
                    </div>
                    <div className="flex gap-2">
                        {!bracket && (
                            <Button onClick={() => setGenerateDialogOpen(true)}>Generate Bracket</Button>
                        )}
                        <Button variant="outline" asChild>
                            <a href={`/manage/tournaments/${tournament.id}/categories`}>← Categories</a>
                        </Button>
                    </div>
                </div>

                {!bracketStructure ? (
                    <Alert>
                        <Info className="size-4" />
                        <AlertDescription>
                            No bracket generated yet. Click "Generate Bracket" to create matches from registered players.
                        </AlertDescription>
                    </Alert>
                ) : (
                    <div className="space-y-8 overflow-x-auto">
                        {allRounds.map((round) => (
                            <div key={`${round.round_number}-${round.round_name}`}>
                                <h3 className="mb-3 font-semibold text-muted-foreground uppercase text-xs tracking-wide">
                                    {round.round_name}
                                </h3>
                                <div className="flex flex-wrap gap-4">
                                    {round.matches.map((match) => (
                                        <Card
                                            key={match.id}
                                            className={`w-64 cursor-pointer hover:shadow-md transition-shadow ${match.status === MatchStatus.Completed ? 'opacity-75' : ''}`}
                                            onClick={() => {
                                                if (match.status !== MatchStatus.Completed && match.white && match.blue) {
                                                    setSelectedMatch(match);
                                                    setResultDialogOpen(true);
                                                }
                                            }}
                                        >
                                            <CardContent className="p-3 space-y-1">
                                                {[match.white, match.blue].map((competitor, idx) => (
                                                    <div
                                                        key={idx}
                                                        className={`flex items-center justify-between rounded px-2 py-1 text-sm ${
                                                            competitor && match.winner_id === competitor.player_id
                                                                ? 'bg-primary/10 font-semibold'
                                                                : ''
                                                        }`}
                                                    >
                                                        <div className="flex items-center gap-2">
                                                            {competitor && match.winner_id === competitor.player_id && (
                                                                <Trophy className="h-3 w-3 text-yellow-500" />
                                                            )}
                                                            <span>{competitor ? competitor.name : <span className="text-muted-foreground italic">TBD</span>}</span>
                                                            {competitor?.seed && (
                                                                <Badge variant="outline" className="text-xs px-1 py-0">#{competitor.seed}</Badge>
                                                            )}
                                                        </div>
                                                        {competitor?.score != null && (
                                                            <span className="text-xs text-muted-foreground">{competitor.score}</span>
                                                        )}
                                                    </div>
                                                ))}
                                                <div className="text-xs text-muted-foreground text-right">
                                                    {match.status === MatchStatus.Completed ? 'Completed' : match.scheduled_at ? new Date(match.scheduled_at).toLocaleString() : 'Not scheduled'}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Generate Bracket Dialog */}
            <Dialog open={generateDialogOpen} onOpenChange={setGenerateDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Generate Bracket</DialogTitle>
                        <DialogDescription>Choose bracket type and seeding method.</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium">Bracket Type</label>
                            <Select value={generateType} onValueChange={setGenerateType}>
                                <SelectTrigger className="mt-1 w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="0">Single Elimination</SelectItem>
                                    <SelectItem value="3">Repechage (IJF)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <label className="text-sm font-medium">Seeding Method</label>
                            <Select value={generateSeedingMethod} onValueChange={setGenerateSeedingMethod}>
                                <SelectTrigger className="mt-1 w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="0">Random</SelectItem>
                                    <SelectItem value="1">Manual</SelectItem>
                                    <SelectItem value="2">By Rank</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setGenerateDialogOpen(false)}>Cancel</Button>
                        <Button onClick={handleGenerateBracket}>Generate</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Record Result Dialog */}
            <Dialog open={resultDialogOpen} onOpenChange={setResultDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Record Result</DialogTitle>
                        <DialogDescription>Select the winner for this match.</DialogDescription>
                    </DialogHeader>
                    {selectedMatch && (
                        <div className="space-y-4">
                            <div>
                                <label className="text-sm font-medium">Winner</label>
                                <div className="mt-2 flex gap-3">
                                    <Button
                                        variant={winnerPosition === '0' ? 'default' : 'outline'}
                                        className="flex-1"
                                        onClick={() => setWinnerPosition('0')}
                                    >
                                        {selectedMatch.white?.name ?? 'White'}
                                    </Button>
                                    <Button
                                        variant={winnerPosition === '1' ? 'default' : 'outline'}
                                        className="flex-1"
                                        onClick={() => setWinnerPosition('1')}
                                    >
                                        {selectedMatch.blue?.name ?? 'Blue'}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                    <DialogFooter>
                        <Button variant="outline" onClick={() => { setResultDialogOpen(false); setWinnerPosition(''); }}>Cancel</Button>
                        <Button onClick={handleRecordResult} disabled={winnerPosition === ''}>Record Result</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
