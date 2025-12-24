import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Match, MatchStatus } from '@/pages/tournaments';
import { cn } from '@/lib/utils';
import { Calendar, Trophy } from 'lucide-react';
import { format } from 'date-fns';

interface MatchCardProps {
    match: Match;
    onClick?: () => void;
    viewOnly?: boolean;
}

export default function MatchCard({ match, onClick, viewOnly = false }: MatchCardProps) {
    const isClickable = !viewOnly && match.status !== MatchStatus.COMPLETED;

    return (
        <Card
            className={cn(
                'transition-all',
                isClickable && 'cursor-pointer hover:shadow-md hover:border-primary',
                match.status === MatchStatus.COMPLETED && 'bg-muted/30'
            )}
            onClick={isClickable ? onClick : undefined}
        >
            <CardContent className="p-4 space-y-3">
                {/* Player 1 */}
                <div
                    className={cn(
                        'flex items-center justify-between p-2 rounded-md border',
                        match.winner_id === match.player1?.id &&
                            'bg-primary/10 border-primary font-semibold'
                    )}
                >
                    <div className="flex items-center gap-2 flex-1 min-w-0">
                        {match.player1 ? (
                            <>
                                {match.player1.seed && (
                                    <Badge variant="outline" className="text-xs shrink-0">
                                        #{match.player1.seed}
                                    </Badge>
                                )}
                                <span className="truncate">{match.player1.name}</span>
                            </>
                        ) : (
                            <span className="text-muted-foreground italic">TBD</span>
                        )}
                    </div>
                    <div className="flex items-center gap-2 shrink-0">
                        {match.player1 && match.player1.score !== null && (
                            <span className="font-bold">{match.player1.score}</span>
                        )}
                        {match.winner_id === match.player1?.id && (
                            <Trophy className="size-4 text-primary" />
                        )}
                    </div>
                </div>

                {/* VS or BYE */}
                <div className="text-center text-xs text-muted-foreground font-medium">
                    {match.has_bye ? 'BYE' : 'VS'}
                </div>

                {/* Player 2 */}
                <div
                    className={cn(
                        'flex items-center justify-between p-2 rounded-md border',
                        match.winner_id === match.player2?.id &&
                            'bg-primary/10 border-primary font-semibold'
                    )}
                >
                    <div className="flex items-center gap-2 flex-1 min-w-0">
                        {match.player2 ? (
                            <>
                                {match.player2.seed && (
                                    <Badge variant="outline" className="text-xs shrink-0">
                                        #{match.player2.seed}
                                    </Badge>
                                )}
                                <span className="truncate">{match.player2.name}</span>
                            </>
                        ) : (
                            <span className="text-muted-foreground italic">TBD</span>
                        )}
                    </div>
                    <div className="flex items-center gap-2 shrink-0">
                        {match.player2 && match.player2.score !== null && (
                            <span className="font-bold">{match.player2.score}</span>
                        )}
                        {match.winner_id === match.player2?.id && (
                            <Trophy className="size-4 text-primary" />
                        )}
                    </div>
                </div>

                {/* Match Info */}
                <div className="flex items-center justify-between text-xs text-muted-foreground pt-2 border-t">
                    <div className="flex items-center gap-1">
                        {match.scheduled_at ? (
                            <>
                                <Calendar className="size-3" />
                                <span>{format(new Date(match.scheduled_at), 'MMM d, h:mm a')}</span>
                            </>
                        ) : (
                            <span>Not scheduled</span>
                        )}
                    </div>
                    <Badge
                        variant={
                            match.status === MatchStatus.COMPLETED
                                ? 'secondary'
                                : match.status === MatchStatus.ONGOING
                                  ? 'default'
                                  : 'outline'
                        }
                        className="text-xs"
                    >
                        {MatchStatus[match.status]}
                    </Badge>
                </div>
            </CardContent>
        </Card>
    );
}
