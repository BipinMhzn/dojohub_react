import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { FileUploader } from '@/components/ui/file-uploader';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BeltRankOption, CountryOption, Player, PlayerGender } from '@/pages/players/index';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import React, { useState } from 'react';

interface Props {
    player: Player;
    countries: CountryOption[];
    beltRanks: BeltRankOption[];
}

export default function EditPlayer({ player, countries, beltRanks }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Players', href: '/manage/players' },
        { title: player.name, href: `/manage/players/${player.id}/edit` },
    ];

    const [date, setDate] = useState<Date | undefined>(player.dob ? new Date(player.dob) : undefined);

    const { data, setData, post, processing, errors } = useForm({
        name: player.name,
        gender: player.gender,
        dob: player.dob ? format(new Date(player.dob), 'yyyy-MM-dd') : '',
        weight: player.weight.toString(),
        nationality_code: player.nationality_code ?? '',
        current_belt_rank_id: player.current_belt_rank_id ?? '',
        email: player.email ?? '',
        phone_number: player.phone_number ?? '',
        profile_photo: null as File | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/manage/players/${player.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Player: ${player.name}`} />
            <div className="px-4 py-6">
                <Heading title="Edit Player" description={`Edit ${player.name}`} />

                <form onSubmit={handleSubmit} className="mt-6 max-w-xl space-y-4">
                    <div>
                        <Label>Photo <span className="text-xs font-light">(Optional)</span></Label>
                        <FileUploader accept="image/*" maxSize={1024 * 1024 * 5} onFileSelect={(file) => setData('profile_photo', file)} className="mt-1 block w-full" initialPreview={player.profile_photo_url ?? undefined} />
                        <InputError message={errors.profile_photo} />
                    </div>

                    <div>
                        <Label htmlFor="name">Name</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1 block w-full" disabled={processing} />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <Label>Gender</Label>
                        <RadioGroup value={data.gender.toString()} onValueChange={(v) => setData('gender', parseInt(v))} className="mt-1 flex gap-4">
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value={PlayerGender.Male.toString()} id="male" />
                                <Label htmlFor="male">Male</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value={PlayerGender.Female.toString()} id="female" />
                                <Label htmlFor="female">Female</Label>
                            </div>
                        </RadioGroup>
                        <InputError message={errors.gender} />
                    </div>

                    <div>
                        <Label>Date of Birth</Label>
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button variant="outline" className={cn('mt-1 w-full justify-start text-left font-normal', !date && 'text-muted-foreground')}>
                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                    {date ? format(date, 'PPP') : <span>Pick a date</span>}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-auto p-0">
                                <Calendar mode="single" selected={date} onSelect={(d) => { setDate(d); setData('dob', d ? format(d, 'yyyy-MM-dd') : ''); }} disabled={(d) => d > new Date() || d < new Date('1900-01-01')} initialFocus />
                            </PopoverContent>
                        </Popover>
                        <InputError message={errors.dob} />
                    </div>

                    <div>
                        <Label htmlFor="weight">Weight (kg)</Label>
                        <Input id="weight" type="number" step="0.01" value={data.weight} onChange={(e) => setData('weight', e.target.value)} className="mt-1 block w-full" disabled={processing} />
                        <InputError message={errors.weight} />
                    </div>

                    <div>
                        <Label htmlFor="nationality_code">Nationality <span className="text-xs font-light">(Optional)</span></Label>
                        <Select value={data.nationality_code} onValueChange={(v) => setData('nationality_code', v)} disabled={processing}>
                            <SelectTrigger className="mt-1 w-full"><SelectValue placeholder="Select nationality" /></SelectTrigger>
                            <SelectContent>
                                {countries.map((c) => <SelectItem key={c.code} value={c.code}>{c.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.nationality_code} />
                    </div>

                    <div>
                        <Label htmlFor="current_belt_rank_id">Belt Rank <span className="text-xs font-light">(Optional)</span></Label>
                        <Select value={data.current_belt_rank_id} onValueChange={(v) => setData('current_belt_rank_id', v)} disabled={processing}>
                            <SelectTrigger className="mt-1 w-full"><SelectValue placeholder="Select belt rank" /></SelectTrigger>
                            <SelectContent>
                                {beltRanks.map((b) => <SelectItem key={b.id} value={b.id}>{b.code} — {b.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.current_belt_rank_id} />
                    </div>

                    <div>
                        <Label htmlFor="email">Email <span className="text-xs font-light">(Optional)</span></Label>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="mt-1 block w-full" disabled={processing} />
                        <InputError message={errors.email} />
                    </div>

                    <div>
                        <Label htmlFor="phone_number">Phone Number <span className="text-xs font-light">(Optional)</span></Label>
                        <Input id="phone_number" value={data.phone_number} onChange={(e) => setData('phone_number', e.target.value)} className="mt-1 block w-full" disabled={processing} />
                        <InputError message={errors.phone_number} />
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>{processing ? 'Saving...' : 'Save'}</Button>
                        <Button type="button" variant="outline" asChild><a href="/manage/players">Cancel</a></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
