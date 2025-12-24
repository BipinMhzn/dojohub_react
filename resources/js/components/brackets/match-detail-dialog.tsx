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
import { Match } from '@/pages/tournaments';
import { useForm } from '@inertiajs/react';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { CalendarIcon } from 'lucide-react';
import { format } from 'date-fns';
import { cn } from '@/lib/utils';
import { useState } from 'react';

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
    const [scheduleDate, setScheduleDate] = useState<Date | undefined>(
        match?.scheduled_at ? new Date(match.scheduled_at) : undefined
    );

    const resultForm = useForm({
        winner_id: '',
        player1_score: '',
        player2_score: '',
    });

    if (!match) return null;

    const handleSubmitResult = (e: React.FormEvent) => {
        e.preventDefault();
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

    const handleSchedule = (date: Date | undefined) => {
        if (!date) return;
        setScheduleDate(date);

        // Submit schedule immediately
        const formData = new FormData();
        formData.append('scheduled_at', date.toISOString());

        // Use router.patch for the schedule update
        const { router } = require('@inertiajs/react');
        router.patch(
            route('tournaments.matches.update-schedule', [tournamentId, match.id]),
            { scheduled_at: date.toISOString() },
            {
                onSuccess: () => {
                    // Dialog stays open
                },
            }
        );
    };

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
                        <div className="flex items-center justify-between p-3 border rounded-md">
                            <span className="font-medium">
                                {match.player1?.name || 'TBD'}
                            </span>
                            {match.player1?.seed && (
                                <span className="text-sm text-muted-foreground">
                                    #{match.player1.seed}
                                </span>
                            )}
                        </div>
                        <div className="text-center text-xs text-muted-foreground">vs</div>
                        <div className="flex items-center justify-between p-3 border rounded-md">
                            <span className="font-medium">
                                {match.player2?.name || 'TBD'}
                            </span>
                            {match.player2?.seed && (
                                <span className="text-sm text-muted-foreground">
                                    #{match.player2.seed}
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Schedule Match */}
                    <div className="space-y-2">
                        <Label>Schedule Match</Label>
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    className={cn(
                                        'w-full justify-start text-left font-normal',
                                        !scheduleDate && 'text-muted-foreground'
                                    )}
                                >
                                    <CalendarIcon className="mr-2 size-4" />
                                    {scheduleDate
                                        ? format(scheduleDate, 'PPP p')
                                        : 'Pick a date and time'}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-auto p-0" align="start">
                                <Calendar
                                    mode="single"
                                    selected={scheduleDate}
                                    onSelect={handleSchedule}
                                    initialFocus
                                />
                            </PopoverContent>
                        </Popover>
                    </div>

                    {/* Record Result */}
                    {match.player1 && match.player2 && (
                        <form onSubmit={handleSubmitResult} className="space-y-4">
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
                                        className="flex-1"
                                        onClick={() =>
                                            resultForm.setData('winner_id', match.player1!.id)
                                        }
                                    >
                                        {match.player1.name}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={
                                            resultForm.data.winner_id === match.player2.id
                                                ? 'default'
                                                : 'outline'
                                        }
                                        className="flex-1"
                                        onClick={() =>
                                            resultForm.setData('winner_id', match.player2!.id)
                                        }
                                    >
                                        {match.player2.name}
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
                                    />
                                </div>
                            </div>

                            <DialogFooter>
                                <Button
                                    type="submit"
                                    disabled={
                                        !resultForm.data.winner_id || resultForm.processing
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
