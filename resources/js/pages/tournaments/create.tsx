import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { FileUploader } from '@/components/ui/file-uploader';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Venue } from '@/pages/venues';
import { convertLocalDateToUTCString } from '@/utils/date';
import { Head, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import React, { useMemo, useState } from 'react';
import { TournamentLevel, TournamentRuleSet } from './index';

const breadcrumbs = [
    {
        title: 'Tournaments',
        href: '/manage/tournaments',
    },
    {
        title: 'Create Tournament',
        href: '/manage/tournaments/create',
    },
];

interface Props {
    venues: Venue[];
    ruleSets: TournamentRuleSet[];
}

// Some browsers/OSes return deprecated IANA timezone aliases. Map them to their
// canonical equivalents so both the display label and the submitted value are correct.
const DEPRECATED_TZ_ALIASES: Record<string, string> = {
    'Asia/Katmandu': 'Asia/Kathmandu',
};
const normalizeTimezone = (tz: string): string => DEPRECATED_TZ_ALIASES[tz] ?? tz;

const timezones =
    typeof Intl !== 'undefined' && Intl.supportedValuesOf
        ? [...new Set(Intl.supportedValuesOf('timeZone').map(normalizeTimezone))].sort()
        : [];

export default function CreateTournament({ venues, ruleSets }: Props) {
    const [startDate, setStartDate] = useState<Date>();
    const [endDate, setEndDate] = useState<Date>();

    const detectedTimezone = useMemo(() => normalizeTimezone(Intl.DateTimeFormat().resolvedOptions().timeZone), []);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        level: '',
        start_at: '',
        end_at: '',
        venue_id: '',
        rule_set_id: '',
        timezone: detectedTimezone,
        logo: null as File | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/manage/tournaments');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Tournament" />

            <div className="px-4 py-6">
                <Heading title="New Tournament" description="Create a new tournament" />
                <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                    <div className="flex flex-col gap-4 md:flex-row">
                        {/* Left Column */}
                        <div className="flex-1 space-y-4">
                            <div>
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1 block w-full" />
                                <InputError message={errors.name} />
                            </div>

                            {/* Start Date and Venue Row */}
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <div className="w-full md:w-1/2">
                                    <Label>Start Date</Label>
                                    <Popover>
                                        <PopoverTrigger asChild>
                                            <Button
                                                variant="outline"
                                                className={cn('mt-1 w-full justify-start text-left font-normal', !startDate && 'text-muted-foreground')}
                                            >
                                                <CalendarIcon className="mr-2 h-4 w-4" />
                                                {startDate ? format(startDate, 'PPP') : <span>Pick a date</span>}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-auto p-0">
                                            <Calendar
                                                mode="single"
                                                selected={startDate}
                                                onSelect={(newDate) => {
                                                    setStartDate(newDate);
                                                    setData('start_at', newDate ? convertLocalDateToUTCString(newDate) : '');
                                                }}
                                                disabled={(date) => date < new Date()}
                                                initialFocus
                                            />
                                        </PopoverContent>
                                    </Popover>
                                    <InputError message={errors.start_at} />
                                </div>
                                <div className="w-full md:w-1/2">
                                    <Label>End Date <span className="text-xs font-light">(Optional)</span></Label>
                                    <Popover>
                                        <PopoverTrigger asChild>
                                            <Button
                                                variant="outline"
                                                className={cn('mt-1 w-full justify-start text-left font-normal', !endDate && 'text-muted-foreground')}
                                            >
                                                <CalendarIcon className="mr-2 h-4 w-4" />
                                                {endDate ? format(endDate, 'PPP') : <span>Pick a date</span>}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-auto p-0">
                                            <Calendar
                                                mode="single"
                                                selected={endDate}
                                                onSelect={(newDate) => {
                                                    setEndDate(newDate);
                                                    setData('end_at', newDate ? convertLocalDateToUTCString(newDate) : '');
                                                }}
                                                initialFocus
                                            />
                                        </PopoverContent>
                                    </Popover>
                                    <InputError message={errors.end_at} />
                                </div>
                            </div>

                            {/* Level and Venue Row */}
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <div className="w-full md:w-1/2">
                                    <Label htmlFor="level">Level</Label>
                                    <Select value={data.level} onValueChange={(value) => setData('level', value)}>
                                        <SelectTrigger className="mt-1 w-full" id="level">
                                            <SelectValue placeholder="Select a level" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(TournamentLevel)
                                                .filter(([, value]) => typeof value === 'number')
                                                .map(([key, value]) => (
                                                    <SelectItem key={value.toString()} value={value.toString()}>
                                                        {key}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.level} />
                                </div>
                                <div className="w-full md:w-1/2">
                                    <Label htmlFor="venue_id">Venue</Label>
                                    <Select value={data.venue_id} onValueChange={(value) => setData('venue_id', value)}>
                                        <SelectTrigger className="mt-1 w-full" id="venue_id">
                                            <SelectValue placeholder="Select a venue" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {venues.length > 0 ? (
                                                venues.map((venue) => (
                                                    <SelectItem key={venue.id} value={venue.id}>
                                                        {venue.name}
                                                    </SelectItem>
                                                ))
                                            ) : (
                                                <div className="text-muted-foreground p-2 text-center text-sm">No venues found.</div>
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.venue_id} />
                                </div>
                            </div>

                            {/* Rule Set and Timezone Row */}
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <div className="w-full md:w-1/2">
                                    <Label htmlFor="rule_set_id">
                                        Rule Set <span className="text-xs font-light">(Optional)</span>
                                    </Label>
                                    <Select value={data.rule_set_id} onValueChange={(value) => setData('rule_set_id', value)}>
                                        <SelectTrigger className="mt-1 w-full" id="rule_set_id">
                                            <SelectValue placeholder="Select a rule set" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ruleSets.length > 0 ? (
                                                ruleSets.map((ruleSet) => (
                                                    <SelectItem key={ruleSet.id} value={ruleSet.id}>
                                                        {ruleSet.name}
                                                    </SelectItem>
                                                ))
                                            ) : (
                                                <div className="text-muted-foreground p-2 text-center text-sm">No rule sets found.</div>
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.rule_set_id} />
                                </div>
                                <div className="w-full md:w-1/2">
                                    <Label htmlFor="timezone">
                                        Timezone <span className="text-xs font-light">(Optional)</span>
                                    </Label>
                                    <Select value={data.timezone} onValueChange={(value) => setData('timezone', value)}>
                                        <SelectTrigger className="mt-1 w-full" id="timezone">
                                            <SelectValue placeholder="Select a timezone" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {timezones.map((tz) => (
                                                <SelectItem key={tz} value={tz}>
                                                    {tz.replace(/_/g, ' ')}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.timezone} />
                                </div>
                            </div>
                        </div>

                        {/* Right Column */}
                        <div className="flex-1 space-y-4">
                            <div>
                                <Label>
                                    Logo <span className="text-xs font-light">(Optional)</span>
                                </Label>
                                <FileUploader
                                    accept="image/*"
                                    maxSize={1024 * 1024 * 5}
                                    onFileSelect={(file) => setData('logo', file)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.logo} />
                            </div>

                            <div>
                                <Label htmlFor="description">
                                    Description <span className="text-xs font-light">(Optional)</span>
                                </Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="mt-1 block min-h-32 w-full"
                                />
                                <InputError message={errors.description} />
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-4 pt-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/manage/tournaments">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
