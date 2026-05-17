import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { FileUploader } from '@/components/ui/file-uploader';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import countries from '@/utils/countries';
import { Head, useForm } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';

const breadcrumbs = [
    {
        title: 'Clubs',
        href: '/manage/clubs',
    },
    {
        title: 'New Club',
        href: '/manage/clubs/create',
    },
];

export default function CreateClub() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        abbreviation: '',
        phone_number: '',
        logo: null as File | null,
        country: '',
        state: '',
        city: '',
        address: '',
    });

    const [availableStates, setAvailableStates] = useState<{ name: string }[]>([]);
    const [availableCities, setAvailableCities] = useState<string[]>([]);

    // Update states when country changes
    useEffect(() => {
        if (data.country) {
            const country = countries.find((c) => c.name === data.country);
            if (country) {
                setAvailableStates(country.states);
                setData((data) => ({
                    ...data,
                    state: '',
                    city: '',
                }));
            }
        }
    }, [data.country, setData]);

    // Update cities when state changes
    useEffect(() => {
        if (data.state) {
            const country = countries.find((c) => c.name === data.country);
            const state = country?.states.find((s) => s.name === data.state);
            if (state) {
                setAvailableCities(state.cities);
                setData((data) => ({
                    ...data,
                    city: '',
                }));
            }
        }
    }, [data.country, data.state, setData]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/manage/clubs', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Club" />

            <div className="px-4 py-6">
                <Heading title="New Club" description="Create a new club" />

                <form onSubmit={handleSubmit} className="mt-6 max-w-xl space-y-4">
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
