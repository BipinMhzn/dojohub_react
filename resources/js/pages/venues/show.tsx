import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import VenueLayout from '@/layouts/venue/layout';
import { Country, Venue } from '@/pages/venues/index';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

interface Props {
    venue: Venue;
    countries: Country[];
}

export default function VenueShow({ venue, countries }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Venues', href: '/manage/venues' },
        { title: venue.name, href: `/manage/venues/${venue.id}` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        name: venue.name,
        address_line_1: venue.address?.address_line_1 ?? '',
        address_line_2: venue.address?.address_line_2 ?? '',
        city: venue.address?.city ?? '',
        state: venue.address?.state ?? '',
        postal_code: venue.address?.postal_code ?? '',
        country_code: venue.address?.country_code ?? '',
        latitude: '',
        longitude: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/manage/venues/${venue.id}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={venue.name} />

            <VenueLayout venue={venue} activeKey="overview">
                <div className="max-w-xl space-y-6">
                    <HeadingSmall title="Venue details" description="Update the venue name, location, and address." />

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Venue Name</Label>
                            <Input id="name" value={data.name} className="mt-1 block w-full" onChange={(e) => setData('name', e.target.value)} disabled={processing} />
                            <InputError message={errors.name} />
                        </div>

                        <fieldset className="space-y-3 rounded-md border p-4 dark:border-neutral-700">
                            <legend className="px-1 text-sm font-medium dark:text-neutral-300">Address</legend>

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
                            </div>

                            <div>
                                <Label htmlFor="postal_code">Postal Code</Label>
                                <Input id="postal_code" value={data.postal_code} className="mt-1 block w-full" onChange={(e) => setData('postal_code', e.target.value)} disabled={processing} placeholder="Optional" />
                            </div>
                        </fieldset>

                        <div className="flex gap-4 pt-2">
                            <Button type="submit" disabled={processing}>{processing ? 'Saving...' : 'Save Changes'}</Button>
                        </div>
                    </form>
                </div>
            </VenueLayout>
        </AppLayout>
    );
}
