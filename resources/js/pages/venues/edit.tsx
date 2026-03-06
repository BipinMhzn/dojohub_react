import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Venue } from '@/pages/venues/index';
import type { BreadcrumbItem } from '@/types';
import countries from '@/utils/countries';
import { Head, useForm } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';

interface Props {
    venue: Venue;
}

export default function EditVenue({ venue }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Venues',
            href: '/manage/venues',
        },
        {
            title: venue.name,
            href: `/manage/venues/${venue.id}/edit`,
        },
    ];

    const { data, setData, put, processing, errors } = useForm({
        name: venue.name,
        mat_count: venue.mat_count,
        address: venue.address?.address ?? '',
        city: venue.address?.city ?? '',
        state: venue.address?.state ?? '',
        country: venue.address?.country ?? '',
    });

    const [availableStates, setAvailableStates] = useState<{ name: string }[]>([]);
    const [availableCities, setAvailableCities] = useState<string[]>([]);

    // Initialize states and cities based on initial venue data
    useEffect(() => {
        const venueCountry = venue.address?.country;
        const venueState = venue.address?.state;

        if (venueCountry) {
            const country = countries.find((c) => c.name === venueCountry);
            if (country) {
                setAvailableStates(country.states);

                if (venueState) {
                    const state = country.states.find((s) => s.name === venueState);
                    if (state) {
                        setAvailableCities(state.cities);
                    }
                }
            }
        }
    }, [venue]);

    // Update states when country changes
    useEffect(() => {
        if (data.country) {
            const country = countries.find((c) => c.name === data.country);
            if (country) {
                setAvailableStates(country.states);
                if (data.country !== venue.address?.country) {
                    setData((prev) => ({ ...prev, state: '', city: '' }));
                }
            }
        }
    }, [data.country, setData, venue.address?.country]);

    // Update cities when state changes
    useEffect(() => {
        if (data.state) {
            const country = countries.find((c) => c.name === data.country);
            const state = country?.states.find((s) => s.name === data.state);
            if (state) {
                setAvailableCities(state.cities);
                if (data.state !== venue.address?.state) {
                    setData((prev) => ({ ...prev, city: '' }));
                }
            }
        }
    }, [data.country, data.state, setData, venue.address?.state]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        put(`/manage/venues/${venue.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Venue: ${venue.name}`} />

            <div className="px-4 py-6">
                <div className="flex items-center">
                    <Heading title="Venues" description={`Edit ${venue.name}`} />
                </div>

                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            type="text"
                            value={data.name}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('name', e.target.value)}
                            disabled={processing}
                        />
                        <InputError message={errors.name} />
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

                    <div>
                        <Label htmlFor="mat_count">Number of Mats</Label>
                        <Input
                            id="mat_count"
                            type="number"
                            min={1}
                            max={99}
                            value={data.mat_count}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('mat_count', Number(e.target.value))}
                            disabled={processing}
                        />
                        <InputError message={errors.mat_count} />
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/manage/venues">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
