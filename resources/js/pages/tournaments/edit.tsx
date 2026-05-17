import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { FileUploader } from '@/components/ui/file-uploader';
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
import React, { useState } from 'react';
import { Tournament, TournamentLevel } from './index';

interface Props {
    venues: Venue[];
    tournament: Tournament;
}

export default function EditTournament({ venues, tournament }: Props) {
    const breadcrumbs = [
        {
            title: 'Tournaments',
            href: '/manage/tournaments',
        },
        {
            title: 'Edit Tournament',
            href: `/manage/tournaments/${tournament.id}/edit`,
        },
    ];

    const [startDate, setStartDate] = useState<Date>(new Date(tournament.start_at));
    const [endDate, setEndDate] = useState<Date | undefined>(tournament.end_at ? new Date(tournament.end_at) : undefined);

    const { data, setData, post, processing, errors } = useForm({
        name: tournament.name,
        description: tournament.description ?? '',
        level: tournament.level.toString(),
        start_at: tournament.start_at,
        end_at: tournament.end_at ?? '',
        venue_id: tournament.venue_id,
        logo: null as File | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/manage/tournaments/${tournament.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Tournament" />

            <div className="px-4 py-6">
                <Heading title="Edit Tournament" description="Update tournament details" />
                <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                    <div className="flex flex-col gap-4 md:flex-row">
                        {/* Left Column */}
                        <div className="flex-1 space-y-4">
                            <div>
                                <Label htmlFor="name">Name</Label>
                                <input
                                    id="name"
                                    className="mt-1 block w-full rounded-md border px-3 py-2 text-sm"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                />
                                <InputError message={errors.name} />
                            </div>

                            {/* Start/End Date Row */}
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
                                                    setStartDate(newDate!);
                                                    setData('start_at', newDate ? convertLocalDateToUTCString(newDate) : '');
                                                }}
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
                                            {venues.map((venue) => (
                                                <SelectItem key={venue.id} value={venue.id}>
                                                    {venue.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.venue_id} />
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
                                    initialPreview={tournament.logo_url ?? undefined}
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
