import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { FileUploader } from '@/components/ui/file-uploader';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import countries from '@/utils/countries';
import { Head, useForm } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
import { Club } from './index';

interface Props {
    club: Club;
}

export default function EditClub({ club }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Clubs',
            href: '/manage/clubs',
        },
        {
            title: club.name,
            href: `/manage/clubs/${club.id}/edit`,
        },
    ];

    const { data, setData, post, processing, errors } = useForm({
        name: club.name,
        abbreviation: club.abbreviation,
        phone_number: club.phone_number ?? '',
        logo: null as File | null,
        country: club.address?.country ?? '',
        state: club.address?.state ?? '',
        city: club.address?.city ?? '',
        address: club.address?.address ?? '',
    });

    const [availableStates, setAvailableStates] = useState<{ name: string }[]>([]);
    const [availableCities, setAvailableCities] = useState<string[]>([]);

    // Initialize states and cities based on initial club data
    useEffect(() => {
        const clubCountry = club.address?.country;
        const clubState = club.address?.state;

        if (clubCountry) {
            const country = countries.find((c) => c.name === clubCountry);
            if (country) {
                setAvailableStates(country.states);

                if (clubState) {
                    const state = country.states.find((s) => s.name === clubState);
                    if (state) {
                        setAvailableCities(state.cities);
                    }
                }
            }
        }
    }, [club]);

    // Update states when country changes
    useEffect(() => {
        if (data.country) {
            const country = countries.find((c) => c.name === data.country);
            if (country) {
                setAvailableStates(country.states);
                if (data.country !== club.address?.country) {
                    setData((prev) => ({ ...prev, state: '', city: '' }));
                }
            }
        }
    }, [data.country, setData, club.address?.country]);

    // Update cities when state changes
    useEffect(() => {
        if (data.state) {
            const country = countries.find((c) => c.name === data.country);
            const state = country?.states.find((s) => s.name === data.state);
            if (state) {
                setAvailableCities(state.cities);
                if (data.state !== club.address?.state) {
                    setData((prev) => ({ ...prev, city: '' }));
                }
            }
        }
    }, [data.country, data.state, setData, club.address?.state]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/manage/clubs/${club.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Club: ${club.name}`} />

            <div className="px-4 py-6">
                <Heading title="Edit Club" description={`Edit ${club.name}`} />

                <form onSubmit={handleSubmit} className="mt-6 max-w-xl space-y-4">
                    <div>
                        <Label>
                            Photo <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <FileUploader
                            accept="image/*"
                            maxSize={1024 * 1024 * 5} // 5MB
                            onFileSelect={(file) => setData('logo', file)}
                            className="mt-1 block w-full"
                            initialPreview={club.logo_url ?? undefined}
                        />
                        <InputError message={errors.logo} />
                    </div>

                    <div>
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1 block w-full"
                            disabled={processing}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <Label htmlFor="abbreviation">Abbreviation</Label>
                        <Input
                            id="abbreviation"
                            type="text"
                            value={data.abbreviation}
                            onChange={(e) => setData('abbreviation', e.target.value)}
                            className="mt-1 block w-full"
                            disabled={processing}
                        />
                        <InputError message={errors.abbreviation} />
                    </div>

                    <div>
                        <Label htmlFor="phone">
                            Phone Number <span className="text-xs font-light">(Optional)</span>
                        </Label>
                        <Input
                            id="phone"
                            type="text"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            className="mt-1 block w-full"
                            disabled={processing}
                        />
                        <InputError message={errors.phone_number} />
                    </div>

                    <div>
                        <Label htmlFor="country">Country</Label>
                        <Select value={data.country} onValueChange={(value) => setData('country', value)} disabled={processing}>
                            <SelectTrigger className="mt-1 w-full">
                                <SelectValue placeholder="Select a country" />
                            </SelectTrigger>
                            <SelectContent>
                                {countries.map((country) => (
                                    <SelectItem key={country.name} value={country.name}>
                                        {country.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.country} />
                    </div>

                    <div>
                        <Label htmlFor="state">State</Label>
                        <Select value={data.state} onValueChange={(value) => setData('state', value)} disabled={processing || !data.country}>
                            <SelectTrigger className="mt-1 w-full">
                                <SelectValue placeholder="Select a state" />
                            </SelectTrigger>
                            <SelectContent>
                                {availableStates.map((state) => (
                                    <SelectItem key={state.name} value={state.name}>
                                        {state.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.state} />
                    </div>

                    <div>
                        <Label htmlFor="city">City</Label>
                        <Select value={data.city} onValueChange={(value) => setData('city', value)} disabled={processing || !data.state}>
                            <SelectTrigger className="mt-1 w-full">
                                <SelectValue placeholder="Select a city" />
                            </SelectTrigger>
                            <SelectContent>
                                {availableCities.map((city) => (
                                    <SelectItem key={city} value={city}>
                                        {city}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.city} />
                    </div>

                    <div>
                        <Label htmlFor="address">Address</Label>
                        <Input
                            id="address"
                            type="text"
                            value={data.address}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('address', e.target.value)}
                            disabled={processing}
                        />
                        <InputError message={errors.address} />
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/manage/clubs">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
