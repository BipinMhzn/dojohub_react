import { BracketRound } from '@/pages/tournaments';
import MatchCard from './match-card';
import { cn } from '@/lib/utils';

interface SingleEliminationBracketProps {
    rounds: BracketRound[];
    onMatchClick?: (matchId: string) => void;
    viewOnly?: boolean;
}

export default function SingleEliminationBracket({
    rounds,
    onMatchClick,
    viewOnly = false,
}: SingleEliminationBracketProps) {
    return (
        <div className="flex gap-6 p-4">
            {rounds.map((round, roundIndex) => (
                <div key={roundIndex} className="flex flex-col gap-3 min-w-[240px]">
                    {/* Round Header */}
                    <div className="text-center sticky top-0 bg-card/95 backdrop-blur-sm z-10 py-2 rounded-md border shadow-sm">
                        <h3 className="font-semibold text-base">{round.round_name}</h3>
                        <p className="text-xs text-muted-foreground">
                            Round {round.round_number}
                        </p>
                    </div>

                    {/* Matches */}
                    <div
                        className={cn('flex flex-col justify-around gap-3')}
                        style={{
                            gap:
                                roundIndex > 0
                                    ? `${Math.pow(2, roundIndex) * 0.75}rem`
                                    : '0.75rem',
                        }}
                    >
                        {round.matches.map((match) => (
                            <MatchCard
                                key={match.id}
                                match={match}
                                onClick={() => onMatchClick?.(match.id)}
                                viewOnly={viewOnly}
                            />
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}
