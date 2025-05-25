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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Venue } from '@/pages/venues';
import { convertLocalDateToUTCString } from '@/utils/date';
import { Head, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import React, { useState } from 'react';
import { TournamentGender, TournamentLevel } from './index';

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

const ageCategories = [
    { label: 'Preparation', min: 1, max: 6 },
    { label: 'Super Mini', min: 7, max: 8 },
    { label: 'Mini', min: 9, max: 10 },
    { label: 'Pupils', min: 11, max: 12 },
    { label: 'Juveniles', min: 13, max: 14 },
    { label: 'Cadets', min: 15, max: 17 },
    { label: 'Juniors', min: 18, max: 20 },
    { label: 'Competitors under 23', min: 15, max: 22 },
    { label: 'Seniors', min: 21, max: 30 },
    { label: 'Veterans', min: 31, max: 34 },
];

const weightCategories = [
    { label: 'Lightweight', min: 40, max: 50 },
    { label: 'Middleweight', min: 51, max: 65 },
    { label: 'Heavyweight', min: 66, max: 80 },
    { label: 'Super Heavyweight', min: 81, max: 100 },
];

interface Props {
    venues: Venue[];
}

export default function CreateTournament({ venues }: Props) {
    const [date, setDate] = useState<Date>();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        level: '',
        start_at: '',
        weight_category: '',
        min_weight: '',
        max_weight: '',
        age_category: '',
        min_age: '',
        max_age: '',
        gender: TournamentGender.MALE,
        venue_id: '',
        logo: null as File | null,
    });

    const handleAgeCategoryChange = (selectedLabel: string) => {
        const selectedCategory = ageCategories.find((cat) => cat.label === selectedLabel);
        if (selectedCategory) {
            setData((prevData) => ({
                ...prevData,
                age_category: selectedCategory.label,
                min_age: selectedCategory.min.toString(),
                max_age: selectedCategory.max.toString(),
            }));
        } else {
            // Reset if no category is found (e.g., placeholder deselected)
            setData((prevData) => ({
                ...prevData,
                age_category: '',
                min_age: '',
                max_age: '',
            }));
        }
    };

    const handleWeightCategoryChange = (selectedLabel: string) => {
        const selectedCategory = weightCategories.find((cat) => cat.label === selectedLabel);
        if (selectedCategory) {
            setData((prevData) => ({
                ...prevData,
                weight_category: selectedCategory.label,
                min_weight: selectedCategory.min.toString(),
                max_weight: selectedCategory.max.toString(),
            }));
        } else {
            setData((prevData) => ({
                ...prevData,
                weight_category: '',
                min_weight: '',
                max_weight: '',
            }));
        }
    };

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
                                                    if (newDate) {
                                                        setData('start_at', convertLocalDateToUTCString(newDate));
                                                    } else {
                                                        setData('start_at', '');
                                                    }
                                                }}
                                                disabled={(date) => date < new Date()}
                                                initialFocus
                                            />
                                        </PopoverContent>
                                    </Popover>
                                    <InputError message={errors.start_at} />
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

                            {/* Level and Gender Row */}
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <div className="w-full md:w-1/2">
                                    <Label htmlFor="level">Level</Label>
                                    <Select value={data.level} onValueChange={(value) => setData('level', value)}>
                                        <SelectTrigger className="mt-1 w-full" id="level">
                                            <SelectValue placeholder="Select a level" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(TournamentLevel)
                                                .filter(([key, value]) => typeof value === 'number')
                                                .map(([key, value]) => (
                                                    <SelectItem key={value.toString()} value={value.toString()}>
                                                        {key.charAt(0).toUpperCase() + key.slice(1).toLowerCase()}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.level} />
                                </div>
                                <div className="w-full md:w-1/2">
                                    <Label>Gender of Competitors</Label>
                                    <RadioGroup
                                        value={data.gender.toString()}
                                        onValueChange={(value) => setData('gender', parseInt(value))}
                                        className="mt-2 flex w-full items-center gap-4"
                                    >
                                        <div className="flex items-center space-x-2">
                                            <RadioGroupItem value={TournamentGender.MALE.toString()} id="male" />
                                            <Label htmlFor="male" className="font-normal">
                                                Male
                                            </Label>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <RadioGroupItem value={TournamentGender.FEMALE.toString()} id="female" />
                                            <Label htmlFor="female" className="font-normal">
                                                Female
                                            </Label>
                                        </div>
                                    </RadioGroup>
                                    <InputError message={errors.gender} />
                                </div>
                            </div>

                            {/* Age Category Row */}
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <div className="w-full md:w-2/4">
                                    {/* Takes 2/4 (half) width on md screens */}
                                    <Label htmlFor="age_category">Age Category</Label>
                                    <Select value={data.age_category} onValueChange={handleAgeCategoryChange}>
                                        <SelectTrigger className="mt-1 w-full" id="age_category">
                                            <SelectValue placeholder="Select an age category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ageCategories.map((category) => (
                                                <SelectItem key={category.label} value={category.label}>
                                                    {category.label} ({category.min}-{category.max} yrs)
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.age_category} />
                                </div>
                                <div className="w-full md:w-1/4">
                                    {/* Takes 1/4 width on md screens */}
                                    <Label htmlFor="min_age">Min Age</Label>
                                    <Input
                                        type="number"
                                        id="min_age"
                                        value={data.min_age}
                                        onChange={(e) => setData('min_age', e.target.value)}
                                        className="mt-1 block w-full"
                                        disabled={data.age_category === ''}
                                    />
                                    <InputError message={errors.min_age} />
                                </div>
                                <div className="w-full md:w-1/4">
                                    {/* Takes 1/4 width on md screens */}
                                    <Label htmlFor="max_age">Max Age</Label>
                                    <Input
                                        type="number"
                                        id="max_age"
                                        value={data.max_age}
                                        onChange={(e) => setData('max_age', e.target.value)}
                                        className="mt-1 block w-full"
                                        disabled={data.age_category === ''}
                                    />
                                    <InputError message={errors.max_age} />
                                </div>
                            </div>

                            {/* Weight Category Row */}
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <div className="w-full md:w-2/4">
                                    {/* Takes 2/4 (half) width on md screens */}
                                    <Label htmlFor="weight_category">Weight Category</Label>
                                    <Select value={data.weight_category} onValueChange={handleWeightCategoryChange}>
                                        <SelectTrigger className="mt-1 w-full" id="weight_category">
                                            <SelectValue placeholder="Select a weight category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {weightCategories.map((category) => (
                                                <SelectItem key={category.label} value={category.label}>
                                                    {category.label} ({category.min}-{category.max} kg)
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.weight_category} />
                                </div>
                                <div className="w-full md:w-1/4">
                                    {/* Takes 1/4 width on md screens */}
                                    <Label htmlFor="min_weight">Min Weight</Label>
                                    <Input
                                        type="number"
                                        id="min_weight"
                                        value={data.min_weight}
                                        onChange={(e) => setData('min_weight', e.target.value)}
                                        className="mt-1 block w-full"
                                        disabled={data.weight_category === ''}
                                    />
                                    <InputError message={errors.min_weight} />
                                </div>
                                <div className="w-full md:w-1/4">
                                    {/* Takes 1/4 width on md screens */}
                                    <Label htmlFor="max_weight">Max Weight</Label>
                                    <Input
                                        type="number"
                                        id="max_weight"
                                        value={data.max_weight}
                                        onChange={(e) => setData('max_weight', e.target.value)}
                                        className="mt-1 block w-full"
                                        disabled={data.weight_category === ''}
                                    />
                                    <InputError message={errors.max_weight} />
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
                                    maxSize={1024 * 1024 * 5} // 5MB
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
