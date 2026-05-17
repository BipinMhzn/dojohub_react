import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Match, MatchStatus } from '@/pages/tournaments';
import { cn } from '@/lib/utils';
import { Calendar, Trophy } from 'lucide-react';
import { format } from 'date-fns';
import { useInitials } from '@/hooks/use-initials';

interface MatchCardProps {
    match: Match;
    onClick?: () => void;
    viewOnly?: boolean;
}

export default function MatchCard({ match, onClick, viewOnly = false }: MatchCardProps) {
    const isClickable = !viewOnly && match.status !== MatchStatus.COMPLETED;
    const getInitials = useInitials();

    return (
        <Card
            className={cn(
                'transition-all shadow-sm',
                isClickable && 'cursor-pointer hover:shadow-md hover:border-primary',
                match.status === MatchStatus.COMPLETED && 'bg-muted/30'
            )}
            onClick={isClickable ? onClick : undefined}
        >
            <CardContent className="p-3 space-y-2">
                {/* Player 1 */}
                <div
                    className={cn(
                        'flex items-center justify-between px-2 py-1.5 rounded border bg-background',
                        match.winner_id === match.player1?.id &&
                            'bg-primary/10 border-primary font-semibold'
                    )}
                >
                    <div className="flex items-center gap-1.5 flex-1 min-w-0">
                        {match.player1 ? (
                            <>
                                <Avatar className="size-6">
                                    <AvatarImage src={match.player1.profile_photo || undefined} />
                                    <AvatarFallback className="text-[10px]">
                                        {getInitials(match.player1.name)}
                                    </AvatarFallback>
                                </Avatar>
                                {match.player1.seed && (
                                    <Badge variant="outline" className="text-[10px] h-4 px-1">
                                        #{match.player1.seed}
                                    </Badge>
                                )}
                                <span className="truncate text-sm">{match.player1.name}</span>
                            </>
                        ) : (
                            <span className="text-muted-foreground italic text-sm">TBD</span>
                        )}
                    </div>
                    <div className="flex items-center gap-1.5 shrink-0">
                        {match.player1 && match.player1.score !== null && (
                            <span className="font-bold text-sm">{match.player1.score}</span>
                        )}
                        {match.winner_id === match.player1?.id && (
                            <Trophy className="size-3.5 text-primary" />
                        )}
                    </div>
                </div>

                {/* VS or BYE */}
                <div className="text-center text-[10px] text-muted-foreground font-medium -my-0.5">
                    {match.has_bye ? 'BYE' : 'VS'}
                </div>

                {/* Player 2 */}
                <div
                    className={cn(
                        'flex items-center justify-between px-2 py-1.5 rounded border bg-background',
                        match.winner_id === match.player2?.id &&
                            'bg-primary/10 border-primary font-semibold'
                    )}
                >
                    <div className="flex items-center gap-1.5 flex-1 min-w-0">
                        {match.player2 ? (
                            <>
                                <Avatar className="size-6">
                                    <AvatarImage src={match.player2.profile_photo || undefined} />
                                    <AvatarFallback className="text-[10px]">
                                        {getInitials(match.player2.name)}
                                    </AvatarFallback>
                                </Avatar>
                                {match.player2.seed && (
                                    <Badge variant="outline" className="text-[10px] h-4 px-1">
                                        #{match.player2.seed}
                                    </Badge>
                                )}
                                <span className="truncate text-sm">{match.player2.name}</span>
                            </>
                        ) : (
                            <span className="text-muted-foreground italic text-sm">TBD</span>
                        )}
                    </div>
                    <div className="flex items-center gap-1.5 shrink-0">
                        {match.player2 && match.player2.score !== null && (
                            <span className="font-bold text-sm">{match.player2.score}</span>
                        )}
                        {match.winner_id === match.player2?.id && (
                            <Trophy className="size-3.5 text-primary" />
                        )}
                    </div>
                </div>

                {/* Match Info */}
                {(match.scheduled_at || match.status !== MatchStatus.PENDING) && (
                    <div className="flex items-center justify-between text-[10px] text-muted-foreground pt-1.5 border-t">
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
                            className="text-[10px] h-4 px-1.5"
                        >
                            {MatchStatus[match.status]}
                        </Badge>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
