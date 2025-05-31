import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tournament, TournamentGender, TournamentLevel, TournamentStatus } from '@/pages/tournaments';
import { formatUTCDate } from '@/utils/date';
import { lowerCase, upperFirst } from 'lodash';

interface Props {
    tournament: Tournament;
}

export default function TournamentHeader({ tournament }: Props) {
    return (
        <Card className="mb-6">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="text-xl">{tournament.name}</CardTitle>
                        <CardDescription>{tournament.description}</CardDescription>
                    </div>
                    {tournament.logo_url && <img src={tournament.logo_url} alt="Tournament Logo" className="h-16 w-16 object-contain" />}
                </div>
            </CardHeader>
            <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <strong>Level:</strong> {upperFirst(lowerCase(TournamentLevel[tournament.level]))}
                </div>
                <div>
                    <strong>Gender:</strong> {upperFirst(lowerCase(TournamentGender[tournament.gender]))}
                </div>
                <div>
                    <strong>Age Category:</strong> {tournament.age_category} ({tournament.min_age}–{tournament.max_age} years)
                </div>
                <div>
                    <strong>Weight Category:</strong> {tournament.weight_category} ({tournament.min_weight}–{tournament.max_weight} Kg)
                </div>
                <div>
                    <strong>Duration:</strong> {formatUTCDate(tournament.start_at)} – {tournament.end_at ? formatUTCDate(tournament.end_at) : 'TBD'}
                </div>
                <div>
                    <strong>Status:</strong> {upperFirst(lowerCase(TournamentStatus[tournament.status]))}
                </div>
            </CardContent>
        </Card>
    );
}
