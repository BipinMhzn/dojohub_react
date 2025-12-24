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
        <div className="flex gap-8 overflow-x-auto pb-6">
            {rounds.map((round, roundIndex) => (
                <div key={roundIndex} className="flex flex-col gap-4 min-w-[280px]">
                    {/* Round Header */}
                    <div className="text-center">
                        <h3 className="font-semibold text-lg">{round.round_name}</h3>
                        <p className="text-sm text-muted-foreground">
                            Round {round.round_number}
                        </p>
                    </div>

                    {/* Matches */}
                    <div
                        className={cn('flex flex-col justify-around gap-4')}
                        style={{
                            gap:
                                roundIndex > 0
                                    ? `${Math.pow(2, roundIndex) * 1}rem`
                                    : '1rem',
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
