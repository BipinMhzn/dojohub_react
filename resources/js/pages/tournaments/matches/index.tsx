import HeadingSmall from '@/components/heading-small';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import CategoryLayout from '@/layouts/category/layout';
import {
    BracketMatch,
    BracketRound,
    BracketStructure,
    BracketType,
    CompetitorResult,
    ConfirmedRegistrationOption,
    MatchStatus,
    Tournament,
    TournamentCategory,
    TournamentPool,
} from '@/pages/tournaments';
import { getCategoryDisplayName } from '@/pages/tournaments/partials/category-columns';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Circle, Info, Trash2, Trophy } from 'lucide-react';
import { useState } from 'react';

interface TournamentBracket {
    id: string;
    type: number;
    status: number;
}

interface GenerationRequirement {
    label: string;
    met: boolean;
    detail: string | null;
}

interface GenerationReadiness {
    ready: boolean;
    requirements: GenerationRequirement[];
}

interface Props {
    tournament: Tournament;
    category: TournamentCategory;
    bracket: TournamentBracket | null;
    bracketStructure: BracketStructure | null;
    pools: TournamentPool[];
    confirmedRegistrations: ConfirmedRegistrationOption[];
    readiness: GenerationReadiness | null;
}

export default function TournamentMatches({ tournament, category, bracket, bracketStructure, pools, confirmedRegistrations, readiness }: Props) {
    const categoryName = getCategoryDisplayName(category);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tournaments', href: '/manage/tournaments' },
        { title: tournament.name, href: `/manage/tournaments/${tournament.id}` },
        { title: categoryName, href: `/manage/tournaments/${tournament.id}/categories/${category.id}/edit` },
        { title: 'Matches', href: `/manage/tournaments/${tournament.id}/categories/${category.id}/matches` },
    ];

    const [generateType, setGenerateType] = useState('0');
    const [generateSeedingMethod, setGenerateSeedingMethod] = useState('0');
    const [generatePoolCount, setGeneratePoolCount] = useState('2');
    const [generateDialogOpen, setGenerateDialogOpen] = useState(false);
    const [selectedMatch, setSelectedMatch] = useState<BracketMatch | null>(null);
    const [resultDialogOpen, setResultDialogOpen] = useState(false);
    const [winnerPosition, setWinnerPosition] = useState<string>('');

    const isPoolBracket = bracket?.type === BracketType.PoolFinal;
    const assignedRegistrationIds = new Set(pools.flatMap((p) => (p.competitors ?? []).map((c) => c.registration_id)));
    const unassignedRegistrations = confirmedRegistrations.filter((r) => !assignedRegistrationIds.has(r.id));

    // Pool management
    const [redistributeOpen, setRedistributeOpen] = useState(false);
    const [redistributeCount, setRedistributeCount] = useState(String(Math.max(pools.length, 2)));
    const [assignByPool, setAssignByPool] = useState<Record<string, string>>({});

    const basePath = `/manage/tournaments/${tournament.id}/categories/${category.id}`;

    const handleGenerateBracket = () => {
        router.post(
            `${basePath}/matches/generate`,
            {
                type: parseInt(generateType),
                seeding_method: parseInt(generateSeedingMethod),
                pool_count: generateType === String(BracketType.PoolFinal) ? parseInt(generatePoolCount) : null,
            },
            { onSuccess: () => setGenerateDialogOpen(false) },
        );
    };

    const handleRedistribute = () => {
        router.post(
            `${basePath}/pools/redistribute`,
            { pool_count: parseInt(redistributeCount) },
            { preserveScroll: true, onSuccess: () => setRedistributeOpen(false) },
        );
    };

    const handleAssignToPool = (poolId: string) => {
        const registrationId = assignByPool[poolId];
        if (!registrationId) return;
        router.post(
            `${basePath}/pools/${poolId}/competitors`,
            { registration_id: registrationId },
            { preserveScroll: true, onSuccess: () => setAssignByPool((prev) => ({ ...prev, [poolId]: '' })) },
        );
    };

    const handleRemoveFromPool = (poolId: string, poolCompetitorId: string) => {
        router.delete(`${basePath}/pools/${poolId}/competitors/${poolCompetitorId}`, { preserveScroll: true });
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
            {
                onSuccess: () => {
                    setResultDialogOpen(false);
                    setSelectedMatch(null);
                    setWinnerPosition('');
                },
            },
        );
    };

    const allRounds: BracketRound[] = bracketStructure ? Object.values(bracketStructure.sections).flat() : [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Matches – ${categoryName}`} />

            <CategoryLayout tournament={tournament} category={category} activeKey="matches">
                <div className="flex items-center justify-between">
                    <HeadingSmall
                        title={isPoolBracket ? 'Pools' : 'Bracket'}
                        description={isPoolBracket ? 'Pool assignments for this category.' : 'Match-by-match bracket view for this category.'}
                    />
                    <div className="flex gap-2">
                        {isPoolBracket && (
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setRedistributeCount(String(Math.max(pools.length, 2)));
                                    setRedistributeOpen(true);
                                }}
                            >
                                Redistribute Pools
                            </Button>
                        )}
                        {!bracket && (
                            <Button
                                onClick={() => setGenerateDialogOpen(true)}
                                disabled={!readiness?.ready}
                                title={readiness?.ready ? 'Generate the bracket or pools' : 'Complete the requirements below first'}
                            >
                                Generate Bracket
                            </Button>
                        )}
                    </div>
                </div>

                {isPoolBracket ? (
                    <div className="mt-4 space-y-4">
                        {unassignedRegistrations.length > 0 && (
                            <Alert>
                                <Info className="size-4" />
                                <AlertDescription>
                                    {unassignedRegistrations.length} confirmed competitor(s) are not yet in a pool. Add them below or redistribute.
                                </AlertDescription>
                            </Alert>
                        )}
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {pools.map((pool) => (
                                <Card key={pool.id}>
                                    <CardContent className="space-y-3 p-4">
                                        <div className="flex items-center justify-between">
                                            <h3 className="font-semibold">{pool.name}</h3>
                                            <Badge variant="outline">{pool.competitors?.length ?? 0}</Badge>
                                        </div>
                                        <div className="space-y-1">
                                            {(pool.competitors?.length ?? 0) > 0 ? (
                                                pool.competitors?.map((c) => (
                                                    <div key={c.id} className="flex items-center justify-between rounded px-2 py-1 text-sm">
                                                        <span>{c.registration?.player?.name ?? 'Competitor'}</span>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            title="Remove from pool"
                                                            onClick={() => handleRemoveFromPool(pool.id, c.id)}
                                                        >
                                                            <Trash2 size={14} />
                                                        </Button>
                                                    </div>
                                                ))
                                            ) : (
                                                <p className="text-muted-foreground text-sm">No competitors.</p>
                                            )}
                                        </div>
                                        {unassignedRegistrations.length > 0 && (
                                            <div className="flex items-end gap-2 border-t pt-3">
                                                <Select
                                                    value={assignByPool[pool.id] ?? ''}
                                                    onValueChange={(v) => setAssignByPool((prev) => ({ ...prev, [pool.id]: v }))}
                                                >
                                                    <SelectTrigger className="w-full">
                                                        <SelectValue placeholder="Add competitor" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {unassignedRegistrations.map((r) => (
                                                            <SelectItem key={r.id} value={r.id}>
                                                                {r.player?.name ?? 'Competitor'}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <Button size="sm" onClick={() => handleAssignToPool(pool.id)} disabled={!assignByPool[pool.id]}>
                                                    Add
                                                </Button>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                ) : !bracketStructure ? (
                    <Card className="mt-4 max-w-xl">
                        <CardContent className="space-y-4 p-5">
                            <div>
                                <h3 className="font-semibold">Before you can generate</h3>
                                <p className="text-muted-foreground text-sm">
                                    {readiness?.ready
                                        ? 'All requirements are met — click "Generate Bracket" to choose a format and create the draw.'
                                        : 'Complete the following before the bracket or pools can be generated.'}
                                </p>
                            </div>
                            <ul className="space-y-2">
                                {readiness?.requirements.map((req) => (
                                    <li key={req.label} className="flex items-start gap-2 text-sm">
                                        {req.met ? (
                                            <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-green-600 dark:text-green-400" />
                                        ) : (
                                            <Circle className="text-muted-foreground mt-0.5 size-4 shrink-0" />
                                        )}
                                        <span className={req.met ? '' : 'text-muted-foreground'}>
                                            {req.label}
                                            {req.detail && <span className="text-muted-foreground"> — {req.detail}</span>}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="mt-4 space-y-8 overflow-x-auto">
                        {allRounds.map((round) => (
                            <div key={`${round.round_number}-${round.round_name}`}>
                                <h3 className="text-muted-foreground mb-3 text-xs font-semibold tracking-wide uppercase">{round.round_name}</h3>
                                <div className="flex flex-wrap gap-4">
                                    {round.matches.map((match) => (
                                        <Card
                                            key={match.id}
                                            className={`w-64 cursor-pointer transition-shadow hover:shadow-md ${match.status === MatchStatus.Completed ? 'opacity-75' : ''}`}
                                            onClick={() => {
                                                if (match.status !== MatchStatus.Completed && match.white && match.blue) {
                                                    setSelectedMatch(match);
                                                    setResultDialogOpen(true);
                                                }
                                            }}
                                        >
                                            <CardContent className="space-y-1 p-3">
                                                {[match.white, match.blue].map((competitor, idx) => (
                                                    <div
                                                        key={idx}
                                                        className={`flex items-center justify-between rounded px-2 py-1 text-sm ${
                                                            competitor &&
                                                            (competitor.result === CompetitorResult.Win ||
                                                                competitor.result === CompetitorResult.WalkoverWin)
                                                                ? 'bg-primary/10 font-semibold'
                                                                : ''
                                                        }`}
                                                    >
                                                        <div className="flex items-center gap-2">
                                                            {competitor &&
                                                                (competitor.result === CompetitorResult.Win ||
                                                                    competitor.result === CompetitorResult.WalkoverWin) && (
                                                                    <Trophy className="h-3 w-3 text-yellow-500" />
                                                                )}
                                                            <span>
                                                                {competitor ? (
                                                                    competitor.name
                                                                ) : (
                                                                    <span className="text-muted-foreground italic">TBD</span>
                                                                )}
                                                            </span>
                                                            {competitor?.seed && (
                                                                <Badge variant="outline" className="px-1 py-0 text-xs">
                                                                    #{competitor.seed}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        {competitor?.score != null && (
                                                            <span className="text-muted-foreground text-xs">{competitor.score}</span>
                                                        )}
                                                    </div>
                                                ))}
                                                <div className="text-muted-foreground text-right text-xs">
                                                    {match.status === MatchStatus.Completed
                                                        ? 'Completed'
                                                        : match.scheduled_at
                                                          ? new Date(match.scheduled_at).toLocaleString()
                                                          : 'Not scheduled'}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CategoryLayout>

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
                                    <SelectItem value={String(BracketType.SingleElimination)}>Single Elimination</SelectItem>
                                    <SelectItem value={String(BracketType.PoolFinal)}>Pool + Final</SelectItem>
                                    <SelectItem value={String(BracketType.Repechage)}>Repechage (IJF)</SelectItem>
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
                        {generateType === String(BracketType.PoolFinal) && (
                            <div>
                                <Label htmlFor="pool_count">Number of pools</Label>
                                <Input
                                    id="pool_count"
                                    type="number"
                                    min="1"
                                    max="32"
                                    value={generatePoolCount}
                                    onChange={(e) => setGeneratePoolCount(e.target.value)}
                                    className="mt-1"
                                />
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setGenerateDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleGenerateBracket}>Generate</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Redistribute Pools Dialog */}
            <Dialog open={redistributeOpen} onOpenChange={setRedistributeOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Redistribute Pools</DialogTitle>
                        <DialogDescription>
                            This clears the current pools and re-distributes all confirmed competitors across the new number of pools.
                        </DialogDescription>
                    </DialogHeader>
                    <div>
                        <Label htmlFor="redistribute_count">Number of pools</Label>
                        <Input
                            id="redistribute_count"
                            type="number"
                            min="1"
                            max="32"
                            value={redistributeCount}
                            onChange={(e) => setRedistributeCount(e.target.value)}
                            className="mt-1"
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRedistributeOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleRedistribute}>Redistribute</Button>
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
                        <Button
                            variant="outline"
                            onClick={() => {
                                setResultDialogOpen(false);
                                setWinnerPosition('');
                            }}
                        >
                            Cancel
                        </Button>
                        <Button onClick={handleRecordResult} disabled={winnerPosition === ''}>
                            Record Result
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
