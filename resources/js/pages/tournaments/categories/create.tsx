import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { AgeCategory, Gender, Tournament, WeightCategory } from '@/pages/tournaments';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

interface Props {
    tournament: Tournament;
}

const ageCategories = [
    { value: AgeCategory.Preparation, label: 'Preparation', min: 1, max: 6 },
    { value: AgeCategory.SuperMini, label: 'Super Mini', min: 7, max: 8 },
    { value: AgeCategory.Mini, label: 'Mini', min: 9, max: 10 },
    { value: AgeCategory.Pupils, label: 'Pupils', min: 11, max: 12 },
    { value: AgeCategory.Juveniles, label: 'Juveniles', min: 13, max: 14 },
    { value: AgeCategory.Cadets, label: 'Cadets', min: 15, max: 17 },
    { value: AgeCategory.Juniors, label: 'Juniors', min: 18, max: 20 },
    { value: AgeCategory.Under23, label: 'Under 23', min: 15, max: 22 },
    { value: AgeCategory.Seniors, label: 'Seniors', min: 21, max: 30 },
    { value: AgeCategory.Veterans, label: 'Veterans', min: 31, max: 99 },
];

const weightCategories = [
    { value: WeightCategory.Female48, label: '-48 kg', min: 0, max: 48 },
    { value: WeightCategory.Female52, label: '-52 kg', min: 49, max: 52 },
    { value: WeightCategory.Female57, label: '-57 kg', min: 53, max: 57 },
    { value: WeightCategory.Male60, label: '-60 kg', min: 0, max: 60 },
    { value: WeightCategory.Female63, label: '-63 kg', min: 58, max: 63 },
    { value: WeightCategory.Male66, label: '-66 kg', min: 61, max: 66 },
    { value: WeightCategory.Female70, label: '-70 kg', min: 64, max: 70 },
    { value: WeightCategory.Male73, label: '-73 kg', min: 67, max: 73 },
    { value: WeightCategory.Female78, label: '-78 kg', min: 71, max: 78 },
    { value: WeightCategory.Male81, label: '-81 kg', min: 74, max: 81 },
    { value: WeightCategory.Male90, label: '-90 kg', min: 82, max: 90 },
    { value: WeightCategory.Male100, label: '-100 kg', min: 91, max: 100 },
    { value: WeightCategory.Male100Plus, label: '+100 kg', min: 101, max: 999 },
    { value: WeightCategory.Female78Plus, label: '+78 kg', min: 79, max: 999 },
];

export default function CreateCategory({ tournament }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tournaments', href: '/manage/tournaments' },
        { title: tournament.name, href: `/manage/tournaments/${tournament.id}/categories` },
        { title: 'Categories', href: `/manage/tournaments/${tournament.id}/categories` },
        { title: 'Add Category', href: `/manage/tournaments/${tournament.id}/categories/create` },
    ];

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        gender: Gender.Male.toString(),
        age_category: '',
        min_age: '',
        max_age: '',
        weight_category: '',
        min_weight: '',
        max_weight: '',
    });

    const handleAgeCategoryChange = (value: string) => {
        const cat = ageCategories.find((c) => c.value.toString() === value);
        if (cat) {
            setData((prev) => ({ ...prev, age_category: value, min_age: cat.min.toString(), max_age: cat.max.toString() }));
        }
    };

    const handleWeightCategoryChange = (value: string) => {
        const cat = weightCategories.find((c) => c.value.toString() === value);
        if (cat) {
            setData((prev) => ({ ...prev, weight_category: value, min_weight: cat.min.toString(), max_weight: cat.max.toString() }));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/manage/tournaments/${tournament.id}/categories`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Category" />
            <div className="px-4 py-6">
                <Heading title="Add Category" description={`Create a new competition category for ${tournament.name}`} />
                <form onSubmit={handleSubmit} className="mt-6 max-w-2xl space-y-6">
                    <div>
                        <Label htmlFor="name">Custom Name <span className="text-xs font-light">(Optional – auto-generated if blank)</span></Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" placeholder="e.g. Male Cadets -73kg" />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <Label>Gender</Label>
                        <RadioGroup
                            value={data.gender}
                            onValueChange={(value) => setData('gender', value)}
                            className="mt-2 flex gap-6"
                        >
                            <div className="flex items-center gap-2">
                                <RadioGroupItem value={Gender.Male.toString()} id="male" />
                                <Label htmlFor="male" className="font-normal">Male</Label>
                            </div>
                            <div className="flex items-center gap-2">
                                <RadioGroupItem value={Gender.Female.toString()} id="female" />
                                <Label htmlFor="female" className="font-normal">Female</Label>
                            </div>
                        </RadioGroup>
                        <InputError message={errors.gender} />
                    </div>

                    <div className="flex flex-col gap-4 sm:flex-row">
                        <div className="flex-1">
                            <Label htmlFor="age_category">Age Category</Label>
                            <Select value={data.age_category} onValueChange={handleAgeCategoryChange}>
                                <SelectTrigger className="mt-1 w-full" id="age_category">
                                    <SelectValue placeholder="Select age category" />
                                </SelectTrigger>
                                <SelectContent>
                                    {ageCategories.map((cat) => (
                                        <SelectItem key={cat.value} value={cat.value.toString()}>
                                            {cat.label} ({cat.min}–{cat.max} yrs)
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.age_category} />
                        </div>
                        <div className="w-full sm:w-1/4">
                            <Label htmlFor="min_age">Min Age</Label>
                            <Input type="number" id="min_age" value={data.min_age} onChange={(e) => setData('min_age', e.target.value)} className="mt-1" />
                            <InputError message={errors.min_age} />
                        </div>
                        <div className="w-full sm:w-1/4">
                            <Label htmlFor="max_age">Max Age</Label>
                            <Input type="number" id="max_age" value={data.max_age} onChange={(e) => setData('max_age', e.target.value)} className="mt-1" />
                            <InputError message={errors.max_age} />
                        </div>
                    </div>

                    <div className="flex flex-col gap-4 sm:flex-row">
                        <div className="flex-1">
                            <Label htmlFor="weight_category">Weight Category</Label>
                            <Select value={data.weight_category} onValueChange={handleWeightCategoryChange}>
                                <SelectTrigger className="mt-1 w-full" id="weight_category">
                                    <SelectValue placeholder="Select weight category" />
                                </SelectTrigger>
                                <SelectContent>
                                    {weightCategories.map((cat) => (
                                        <SelectItem key={cat.value} value={cat.value.toString()}>
                                            {cat.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.weight_category} />
                        </div>
                        <div className="w-full sm:w-1/4">
                            <Label htmlFor="min_weight">Min Weight (kg)</Label>
                            <Input type="number" id="min_weight" value={data.min_weight} onChange={(e) => setData('min_weight', e.target.value)} className="mt-1" />
                            <InputError message={errors.min_weight} />
                        </div>
                        <div className="w-full sm:w-1/4">
                            <Label htmlFor="max_weight">Max Weight (kg)</Label>
                            <Input type="number" id="max_weight" value={data.max_weight} onChange={(e) => setData('max_weight', e.target.value)} className="mt-1" />
                            <InputError message={errors.max_weight} />
                        </div>
                    </div>

                    <div className="flex gap-4 pt-2">
                        <Button type="submit" disabled={processing}>{processing ? 'Saving...' : 'Save Category'}</Button>
                        <Button type="button" variant="outline" asChild>
                            <a href={`/manage/tournaments/${tournament.id}/categories`}>Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
