import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { FileUploader } from '@/components/ui/file-uploader';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Head, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import React, { useState } from 'react';
import { Player, PlayerGender } from './index';

interface Props {
    player: Player;
}

export default function EditPlayer({ player }: Props) {
    const breadcrumbs = [
        {
            title: 'Players',
            href: '/manage/players',
        },
        {
            title: player.name,
            href: `/manage/players/${player.id}/edit`,
        },
    ];

    const [date, setDate] = useState<Date | undefined>(player.dob ? new Date(player.dob) : undefined);

    const { data, setData, post, processing, errors } = useForm({
        name: player.name,
        gender: player.gender,
        dob: player.dob ? format(new Date(player.dob), 'yyyy-MM-dd') : '',
        weight: player.weight.toString(),
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
                        <Label>
                            Photo <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <FileUploader
                            accept="image/*"
                            maxSize={1024 * 1024 * 5} // 5MB
                            onFileSelect={(file) => setData('profile_photo', file)}
                            className="mt-1 block w-full"
                            initialPreview={player.profile_photo_url ?? undefined}
                        />
                        <InputError message={errors.profile_photo} />
                    </div>

                    <div>
                        <Label htmlFor="name">Name</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1 block w-full" />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <Label>Gender</Label>
                        <RadioGroup
                            value={data.gender.toString()}
                            onValueChange={(value) => setData('gender', parseInt(value))}
                            className="mt-1 flex w-full gap-4"
                        >
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value={PlayerGender.MALE.toString()} id="male" />
                                <Label htmlFor="male">Male</Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value={PlayerGender.FEMALE.toString()} id="female" />
                                <Label htmlFor="female">Female</Label>
                            </div>
                        </RadioGroup>
                        <InputError message={errors.gender} />
                    </div>

                    <div>
                        <Label>Date of Birth</Label>
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    className={cn('mt-1 w-full justify-start text-left font-normal', !date && 'text-muted-foreground')}
                                >
                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                    {date ? format(date, 'PPP') : <span>Pick a date</span>}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-auto p-0">
                                <Calendar
                                    mode="single"
                                    selected={date}
                                    onSelect={(newDate) => {
                                        setDate(newDate);
                                        setData('dob', newDate ? format(newDate, 'yyyy-MM-dd') : '');
                                    }}
                                    initialFocus
                                />
                            </PopoverContent>
                        </Popover>
                        <InputError message={errors.dob} />
                    </div>

                    <div>
                        <Label htmlFor="weight">Weight in Kg</Label>
                        <Input
                            id="weight"
                            type="number"
                            step="0.01"
                            value={data.weight}
                            onChange={(e) => setData('weight', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.weight} />
                    </div>

                    <div>
                        <Label htmlFor="phone">
                            Phone Number <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <Input
                            id="phone"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.phone_number} />
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/manage/players">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
