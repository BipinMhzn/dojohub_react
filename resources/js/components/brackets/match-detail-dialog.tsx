import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Match } from '@/pages/tournaments';
import { useForm, router } from '@inertiajs/react';
import { CalendarIcon, AlertCircle } from 'lucide-react';
import { useState, useEffect } from 'react';
import { useInitials } from '@/hooks/use-initials';

interface MatchDetailDialogProps {
    match: Match | null;
    tournamentId: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function MatchDetailDialog({
    match,
    tournamentId,
    open,
    onOpenChange,
}: MatchDetailDialogProps) {
    const [scheduleDateTimeLocal, setScheduleDateTimeLocal] = useState<string>('');
    const getInitials = useInitials();

    const resultForm = useForm({
        winner_id: '',
        player1_score: '',
        player2_score: '',
    });

    // Update schedule date when match changes
    useEffect(() => {
        if (match?.scheduled_at) {
            // Convert ISO string to datetime-local format (YYYY-MM-DDTHH:mm)
            const date = new Date(match.scheduled_at);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            setScheduleDateTimeLocal(`${year}-${month}-${day}T${hours}:${minutes}`);
        } else {
            setScheduleDateTimeLocal('');
        }
    }, [match?.scheduled_at]);

    if (!match) return null;

    const handleSubmitResult = (e: React.FormEvent) => {
        e.preventDefault();

        // Prevent submission if no schedule date
        if (!match.scheduled_at && !scheduleDateTimeLocal) {
            return;
        }

        resultForm.patch(
            route('tournaments.matches.update-result', [tournamentId, match.id]),
            {
                onSuccess: () => {
                    onOpenChange(false);
                    resultForm.reset();
                },
            }
        );
    };

    const handleScheduleDateChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setScheduleDateTimeLocal(value);

        if (value) {
            // Convert datetime-local to ISO string
            const date = new Date(value);

            router.patch(
                route('tournaments.matches.update-schedule', [tournamentId, match.id]),
                { scheduled_at: date.toISOString() },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Dialog stays open
                    },
                }
            );
        }
    };

    const isScheduled = !!match.scheduled_at || !!scheduleDateTimeLocal;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Match Details</DialogTitle>
                    <DialogDescription>
                        Record the result or schedule this match
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6">
                    {/* Match Info */}
                    <div className="space-y-2">
                        <div className="flex items-center gap-3 p-3 border rounded-md bg-muted/30">
                            {match.player1 ? (
                                <>
                                    <Avatar className="size-10">
                                        <AvatarImage
                                            src={match.player1.profile_photo || undefined}
                                        />
                                        <AvatarFallback>
                                            {getInitials(match.player1.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="flex-1">
                                        <div className="font-medium">{match.player1.name}</div>
                                        {match.player1.seed && (
                                            <Badge variant="outline" className="text-xs mt-1">
                                                Seed #{match.player1.seed}
                                            </Badge>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <span className="text-muted-foreground italic">TBD</span>
                            )}
                        </div>
                        <div className="text-center text-xs text-muted-foreground font-medium">
                            vs
                        </div>
                        <div className="flex items-center gap-3 p-3 border rounded-md bg-muted/30">
                            {match.player2 ? (
                                <>
                                    <Avatar className="size-10">
                                        <AvatarImage
                                            src={match.player2.profile_photo || undefined}
                                        />
                                        <AvatarFallback>
                                            {getInitials(match.player2.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="flex-1">
                                        <div className="font-medium">{match.player2.name}</div>
                                        {match.player2.seed && (
                                            <Badge variant="outline" className="text-xs mt-1">
                                                Seed #{match.player2.seed}
                                            </Badge>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <span className="text-muted-foreground italic">TBD</span>
                            )}
                        </div>
                    </div>

                    {/* Schedule Match */}
                    <div className="space-y-2">
                        <Label htmlFor="schedule">Schedule Match *</Label>
                        <div className="relative">
                            <CalendarIcon className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none" />
                            <Input
                                id="schedule"
                                type="datetime-local"
                                value={scheduleDateTimeLocal}
                                onChange={handleScheduleDateChange}
                                className="pl-9"
                                required
                            />
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Match schedule is required before recording results
                        </p>
                    </div>

                    {/* Record Result */}
                    {match.player1 && match.player2 && (
                        <form onSubmit={handleSubmitResult} className="space-y-4">
                            {!isScheduled && (
                                <Alert variant="destructive">
                                    <AlertCircle className="size-4" />
                                    <AlertDescription>
                                        Please schedule the match before recording results
                                    </AlertDescription>
                                </Alert>
                            )}
                            <div className="space-y-2">
                                <Label>Winner</Label>
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        variant={
                                            resultForm.data.winner_id === match.player1.id
                                                ? 'default'
                                                : 'outline'
                                        }
                                        className="flex-1 flex items-center gap-2 justify-center"
                                        onClick={() =>
                                            resultForm.setData('winner_id', match.player1!.id)
                                        }
                                        disabled={!isScheduled}
                                    >
                                        <Avatar className="size-5">
                                            <AvatarImage
                                                src={match.player1.profile_photo || undefined}
                                            />
                                            <AvatarFallback className="text-[9px]">
                                                {getInitials(match.player1.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span className="truncate">{match.player1.name}</span>
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={
                                            resultForm.data.winner_id === match.player2.id
                                                ? 'default'
                                                : 'outline'
                                        }
                                        className="flex-1 flex items-center gap-2 justify-center"
                                        onClick={() =>
                                            resultForm.setData('winner_id', match.player2!.id)
                                        }
                                        disabled={!isScheduled}
                                    >
                                        <Avatar className="size-5">
                                            <AvatarImage
                                                src={match.player2.profile_photo || undefined}
                                            />
                                            <AvatarFallback className="text-[9px]">
                                                {getInitials(match.player2.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span className="truncate">{match.player2.name}</span>
                                    </Button>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="player1_score">
                                        {match.player1.name} Score
                                    </Label>
                                    <Input
                                        id="player1_score"
                                        type="number"
                                        min="0"
                                        value={resultForm.data.player1_score}
                                        onChange={(e) =>
                                            resultForm.setData('player1_score', e.target.value)
                                        }
                                        disabled={!isScheduled}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="player2_score">
                                        {match.player2.name} Score
                                    </Label>
                                    <Input
                                        id="player2_score"
                                        type="number"
                                        min="0"
                                        value={resultForm.data.player2_score}
                                        onChange={(e) =>
                                            resultForm.setData('player2_score', e.target.value)
                                        }
                                        disabled={!isScheduled}
                                    />
                                </div>
                            </div>

                            <DialogFooter>
                                <Button
                                    type="submit"
                                    disabled={
                                        !isScheduled ||
                                        !resultForm.data.winner_id ||
                                        resultForm.processing
                                    }
                                >
                                    Record Result
                                </Button>
                            </DialogFooter>
                        </form>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
