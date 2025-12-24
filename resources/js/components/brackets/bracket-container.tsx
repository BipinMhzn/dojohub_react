import { BracketStructure, TournamentType } from '@/pages/tournaments';
import SingleEliminationBracket from './single-elimination-bracket';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Info } from 'lucide-react';

interface BracketContainerProps {
    bracket: BracketStructure;
    onMatchClick?: (matchId: string) => void;
    viewOnly?: boolean;
}

export default function BracketContainer({
    bracket,
    onMatchClick,
    viewOnly = false,
}: BracketContainerProps) {
    switch (bracket.type) {
        case TournamentType.SINGLE_ELIMINATION:
            return (
                <SingleEliminationBracket
                    rounds={bracket.brackets.main}
                    onMatchClick={onMatchClick}
                    viewOnly={viewOnly}
                />
            );

        case TournamentType.DOUBLE_ELIMINATION:
            // For now, show single elimination (will implement full double elim later)
            return (
                <div className="space-y-4">
                    <Alert>
                        <Info className="size-4" />
                        <AlertDescription>
                            Double Elimination bracket display is coming soon. Showing main bracket
                            for now.
                        </AlertDescription>
                    </Alert>
                    <SingleEliminationBracket
                        rounds={bracket.brackets.main}
                        onMatchClick={onMatchClick}
                        viewOnly={viewOnly}
                    />
                </div>
            );

        case TournamentType.ROUND_ROBIN:
            return (
                <Alert>
                    <Info className="size-4" />
                    <AlertDescription>
                        Round Robin bracket display is coming soon.
                    </AlertDescription>
                </Alert>
            );

        default:
            return <div>Unknown bracket type</div>;
    }
}
