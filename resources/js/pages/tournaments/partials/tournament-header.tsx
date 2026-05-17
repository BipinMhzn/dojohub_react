import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tournament } from '@/pages/tournaments';
import { formatUTCDate } from '@/utils/date';

interface Props {
    tournament: Tournament;
}

const levelLabel: Record<number, string> = {
    1: 'Local',
    2: 'Regional',
    3: 'National',
    4: 'International',
};

const statusLabel: Record<number, string> = {
    0: 'Scheduled',
    1: 'In Progress',
    2: 'Completed',
    3: 'Cancelled',
};

export default function TournamentHeader({ tournament }: Props) {
    return (
        <Card className="mb-6">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="text-xl">{tournament.name}</CardTitle>
                        {tournament.description && <CardDescription>{tournament.description}</CardDescription>}
                    </div>
                    {tournament.logo_url && <img src={tournament.logo_url} alt="Tournament Logo" className="h-16 w-16 object-contain" />}
                </div>
            </CardHeader>
            <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <strong>Level:</strong> {levelLabel[Number(tournament.level)] ?? 'Unknown'}
                </div>
                <div>
                    <strong>Duration:</strong> {formatUTCDate(tournament.start_at)} – {tournament.end_at ? formatUTCDate(tournament.end_at) : 'TBD'}
                </div>
                <div>
                    <strong>Status:</strong> {statusLabel[Number(tournament.status)] ?? 'Unknown'}
                </div>
            </CardContent>
        </Card>
    );
}
