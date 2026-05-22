import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Country } from '@/pages/venues';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Venues', href: '/manage/venues' },
    { title: 'New Venue', href: '/manage/venues/create' },
];

interface Props {
    countries: Country[];
}

export default function CreateVenue({ countries }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        address_line_1: '',
        address_line_2: '',
        city: '',
        state: '',
        postal_code: '',
        country_code: '',
        latitude: '',
        longitude: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/manage/venues', { preserveScroll: true, onSuccess: () => reset() });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Venue" />
            <div className="px-4 py-6">
                <Heading title="Venues" description="Create a new venue" />

                <form onSubmit={handleSubmit} className="mt-6 max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="name">Venue Name</Label>
                        <Input id="name" value={data.name} className="mt-1 block w-full" onChange={(e) => setData('name', e.target.value)} disabled={processing} />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <Label htmlFor="country_code">Country</Label>
                        <Select value={data.country_code} onValueChange={(v) => setData('country_code', v)} disabled={processing}>
                            <SelectTrigger className="mt-1 w-full">
                                <SelectValue placeholder="Select a country" />
                            </SelectTrigger>
                            <SelectContent>
                                {countries.map((c) => (
                                    <SelectItem key={c.code} value={c.code}>{c.name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.country_code} />
                    </div>

                    <div>
                        <Label htmlFor="state">State / Province</Label>
                        <Input id="state" value={data.state} className="mt-1 block w-full" onChange={(e) => setData('state', e.target.value)} disabled={processing} placeholder="Optional" />
                        <InputError message={errors.state} />
                    </div>

                    <div>
                        <Label htmlFor="city">City</Label>
                        <Input id="city" value={data.city} className="mt-1 block w-full" onChange={(e) => setData('city', e.target.value)} disabled={processing} />
                        <InputError message={errors.city} />
                    </div>

                    <div>
                        <Label htmlFor="address_line_1">Address Line 1</Label>
                        <Input id="address_line_1" value={data.address_line_1} className="mt-1 block w-full" onChange={(e) => setData('address_line_1', e.target.value)} disabled={processing} />
                        <InputError message={errors.address_line_1} />
                    </div>

                    <div>
                        <Label htmlFor="address_line_2">Address Line 2</Label>
                        <Input id="address_line_2" value={data.address_line_2} className="mt-1 block w-full" onChange={(e) => setData('address_line_2', e.target.value)} disabled={processing} placeholder="Optional" />
                        <InputError message={errors.address_line_2} />
                    </div>

                    <div>
                        <Label htmlFor="postal_code">Postal Code</Label>
                        <Input id="postal_code" value={data.postal_code} className="mt-1 block w-full" onChange={(e) => setData('postal_code', e.target.value)} disabled={processing} placeholder="Optional" />
                        <InputError message={errors.postal_code} />
                    </div>

                    <div className="flex gap-4">
                        <Button type="submit" disabled={processing}>{processing ? 'Saving...' : 'Save'}</Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/manage/venues">Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
