import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import TournamentHeader from '@/pages/tournaments/partials/tournament-header';
import TournamentTabs from '@/pages/tournaments/partials/tournament-tabs';
import BracketContainer from '@/components/brackets/bracket-container';
import MatchDetailDialog from '@/components/brackets/match-detail-dialog';
import { Button } from '@/components/ui/button';
import { Tournament, BracketStructure, Match } from '@/pages/tournaments';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Info } from 'lucide-react';

interface Props {
    tournament: Tournament;
    bracket: BracketStructure | null;
}

export default function TournamentMatches({ tournament, bracket }: Props) {
    const [selectedMatch, setSelectedMatch] = useState<Match | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);

    const breadcrumbs = [
        {
            title: 'Tournaments',
            href: '/manage/tournaments',
        },
        {
            title: tournament.name,
            href: `/manage/tournaments/${tournament.id}/registrations`,
        },
        {
            title: 'Matches',
            href: `/manage/tournaments/${tournament.id}/matches`,
        },
    ];

    const handleGenerateBracket = () => {
        router.post(route('tournaments.matches.generate', tournament.id));
    };

    const handleMatchClick = (matchId: string) => {
        const match = findMatchById(bracket, matchId);
        if (match) {
            setSelectedMatch(match);
            setDialogOpen(true);
        }
    };

    const findMatchById = (
        bracket: BracketStructure | null,
        matchId: string
    ): Match | null => {
        if (!bracket) return null;

        for (const rounds of Object.values(bracket.brackets)) {
            for (const round of rounds) {
                const match = round.matches.find((m) => m.id === matchId);
                if (match) return match;
            }
        }
        return null;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Matches: ${tournament.name}`} />

            <div className="px-4 py-6">
                <TournamentHeader tournament={tournament} />
                <TournamentTabs tournamentId={tournament.id} activeTab="matches" />

                {!bracket ? (
                    <div className="space-y-4">
                        <Alert>
                            <Info className="size-4" />
                            <AlertDescription>
                                No bracket has been generated yet. Click the button below to
                                generate matches based on registered players.
                            </AlertDescription>
                        </Alert>
                        <Button onClick={handleGenerateBracket}>Generate Bracket</Button>
                    </div>
                ) : (
                    <div className="space-y-6">
                        <div className="flex items-center justify-between">
                            <h2 className="text-2xl font-bold">Tournament Bracket</h2>
                            <div className="text-sm text-muted-foreground">
                                Click on any match to record results or schedule
                            </div>
                        </div>

                        <BracketContainer bracket={bracket} onMatchClick={handleMatchClick} />
                    </div>
                )}
            </div>

            <MatchDetailDialog
                match={selectedMatch}
                tournamentId={tournament.id}
                open={dialogOpen}
                onOpenChange={setDialogOpen}
            />
        </AppLayout>
    );
}
